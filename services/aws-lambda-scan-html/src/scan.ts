import AxePuppeteer from "@axe-core/puppeteer";
import puppeteer, { Browser } from "puppeteer";
import { emptyAltTagRule } from "./rules/empty-alt-tag/empty-alt-tag-rule.ts";
//import path from "path";
import { pdfLinkRule } from "./rules/pdf-link/pdf-link-rule.ts";
import chromium from "@sparticuz/chromium-min";
import { logger } from "./telemetry.ts";
import { AxeResults } from "axe-core";

const BROWSER_LOAD_TIMEOUT = 25000;
import {SqsScanJob} from '../../../shared/types/sqsScanJob.ts'

// disable webgl
chromium.setGraphicsMode = false;

export default async function (job: SqsScanJob) {

  // vars we'll be populating
  let status = "";
  let message = "";

  logger.info(`HTML Scanner: Job [auditId: ${job.auditId}, urlId: ${job.urlId}](${job.url}) started.`);
  const viewport = {
    deviceScaleFactor: 1,
    hasTouch: false,
    height: 1080,
    isLandscape: true,
    isMobile: false,
    width: 1920,
  };
  
  // launch the browser
  const browser = await puppeteer.launch({
    args: puppeteer.defaultArgs({ args: chromium.args, headless: "shell" }),
    defaultViewport: viewport,
    executablePath: await chromium.executablePath(
      "/opt/nodejs/node_modules/@sparticuz/chromium/bin"
    ),
    headless: "shell",
  });

  const page = await browser.newPage();

  // Add listener to detect PDF files
  /* page.on("response", async (response) => {
    const contentType = response.headers()["content-type"]; // MIME Type
    if (contentType.includes("application/pdf")) {
      console.log("PDF Detected, aborting...");

      if (browser.connected) {
        await shutdown(browser).then(function () {
          throw new Error(`PDF Detected`);
        });
      }
    }
  }); */

  // attempt to visit the page
  try {
    await page.goto(job.url, { timeout: BROWSER_LOAD_TIMEOUT }).then(() => {
      logger.info(`HTML Scanner: Job [auditId: ${job.auditId}, urlId: ${job.urlId}](${job.url}) loaded.`);
    });
  } catch (error) {
    logger.error(`HTML Scanner: Job [auditId: ${job.auditId}, urlId: ${job.urlId}] page.goto error`, error as string);
    // error opening page: return the status and the error
    status = "failed";
    message = error as string;
    const readyToExit = await shutdown(browser).then((val)=>{
      logger.info(`HTML Scanner: Job [auditId: ${job.auditId}, urlId: ${job.urlId}] - Browser shutdown.`);
      return val;
    });
    if(readyToExit) return;
  }

  // run axe
  const results:AxeResults|void = await new AxePuppeteer(page)
    .configure({ rules: [emptyAltTagRule, pdfLinkRule] })
    .options({ resultTypes: ["violations", "incomplete"] }) // we only need violations, ignore passing to save disk/transfer space (see: https://github.com/dequelabs/axe-core/blob/master/doc/API.md#options-parameter)
    .analyze()
    .then((results) => {
      logger.info(`HTML Scanner: Scan complete for [${job.url}]!`);
      status = "complete";
      return results;
    })
    .catch((error) => {
      logger.error(`HTML Scanner: Error: [${job.url}]`, error);
      status = "failed";
      message = `Error: [${job.url}] ${error}`;
      return;
    });

  // Editoria11y injection
  /* 
  let editoria11yResults: unknown;
  try {
    await page.addScriptTag({
      path: path.join(__dirname, "..", "scanners", "editoria11y.min.js"),
    });

    await page.evaluate(`const ed11y = new Ed11y();`);
    // extract the data we want from the Editoria11ly results.
    // we're doing this within the puppeteer context as NodeLists aren't serializable by evaluate();
    editoria11yResults = await page.evaluate(`
         (async() => {
           const results = Ed11y.results;
           const ed11yResults = [];
           results.forEach(item => {
             const ed11yResult = {
               content: "",
               test: "",
               node: ""
             };
             ed11yResult.content = item.content;
             ed11yResult.test = item.test;
             ed11yResult.node = item.element.innerHTML;
             ed11yResults.push(ed11yResult);
           });
           return ed11yResults;
         })()
         `);
  } catch (e) {
    await shutdown(browser).then(function () {
      throw new Error(`Editoria11y error: ${e}`);
    });
  } 
  */

  const readyToExit = await shutdown(browser).then((val) => {
    logger.info(`HTML Scanner: Job [auditId: ${job.auditId}, urlId: ${job.urlId}] - Browser shutdown.`);
    return val;
  }).catch((error) => {
    logger.error(`HTML Scanner: Browser shutdown error for [auditId: ${job.auditId}, urlId: ${job.urlId}]`, error);
    return false;
  });
  
  if (readyToExit) {
    return {
      createdDate: new Date(), // record to add
      axeresults: results,
      jobID: job.urlId,
      message,
      status
      //editoria11yResults: editoria11yResults,
    };
  }

  async function shutdown(browser: Browser) {
    try {
      logger.info(`HTML Scanner: Starting browser cleanup`);
      
      // Try to close pages first, but don't fail if it errors
      try {
        const pages = await browser.pages();
        logger.info(`HTML Scanner: Closing ${pages.length} browser pages`);
        await Promise.allSettled(pages.map(page => page.close()));
      } catch (pageError) {
        logger.warn(`HTML Scanner: Error closing pages (continuing cleanup)`, pageError as Error);
      }
      
      // Always try to close the browser with timeout
      logger.info(`HTML Scanner: Closing browser`);
      const BROWSER_CLOSE_TIMEOUT = 5000;
      await Promise.race([
        browser.close(),
        new Promise((_, reject) => 
          setTimeout(() => reject(new Error('Browser close timeout')), BROWSER_CLOSE_TIMEOUT)
        )
      ]);
      logger.info(`HTML Scanner: Browser closed successfully`);
      return true;
    } catch (error) {
      logger.error(`HTML Scanner: Error during browser shutdown`, error as Error);
      
      // Try force kill if normal close fails
      try {
        const browserProcess = browser.process();
        if (browserProcess) {
          logger.info(`HTML Scanner: Force killing browser process`);
          browserProcess.kill('SIGKILL');
        }
      } catch (killError) {
        logger.error(`HTML Scanner: Failed to force kill browser`, killError as Error);
      }
      
      return true; // Still return true so we can send results even if cleanup fails
    }
  }
}
