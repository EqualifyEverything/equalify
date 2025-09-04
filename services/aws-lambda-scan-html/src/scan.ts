import AxePuppeteer from "@axe-core/puppeteer";
import puppeteer, { Browser } from "puppeteer";
//import { emptyAltTagRule } from "./rules/empty-alt-tag/empty-alt-tag-rule";
//import path from "path";
//import { pdfLinkRule } from "./rules/pdf-link/pdf-link-rule";
import chromium from "@sparticuz/chromium-min";
import { logger } from "./telemetry";

const BROWSER_LOAD_TIMEOUT = 25000;

// disable webgl
chromium.setGraphicsMode = false;

type SqsScanJob = {
  id: string;
  url: string;
  type: string;
};

export default async function (job: SqsScanJob) {
  logger.info(`HTML Scanner: Job "${job.id}" (${job.url}) started.`);
  /* const browser = await puppeteer.launch(
    {
      headless: true,
      // headless: true, // trying old version of headless
      args: [
        '--disk-cache-size=0',
        '–-media-cache-size=0',
        '--disable-dev-shm-usage'
      ]
    }
  ); */
  const viewport = {
    deviceScaleFactor: 1,
    hasTouch: false,
    height: 1080,
    isLandscape: true,
    isMobile: false,
    width: 1920,
  };
  const browser = await puppeteer.launch({
    args: puppeteer.defaultArgs({ args: chromium.args, headless: "shell" }),
    defaultViewport: viewport,
    executablePath: await chromium.executablePath("/opt/nodejs/node_modules/@sparticuz/chromium/bin"),
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

  //await page.setRequestInterception(false); // possible fix for memory leak (see https://github.com/puppeteer/puppeteer/issues/9186)

  try {
    await page.goto(job.url, { timeout: BROWSER_LOAD_TIMEOUT }).then(()=>{
      logger.info(`HTML Scanner: Job "${job.id}" - URL:${job.url} loaded.`);
    });
  } catch (e) {
    await shutdown(browser).then(function () {
      throw new Error(`Page timeout error: ${e}`);
    });
  }

  let results: unknown;
  try {
    results = await new AxePuppeteer(page)
      //.configure({ rules: [emptyAltTagRule, pdfLinkRule] })
      .options({ resultTypes: ["violations", "incomplete"] }) // we only need violations, ignore passing to save disk/transfer space (see: https://github.com/dequelabs/axe-core/blob/master/doc/API.md#options-parameter)
      .analyze();
    logger.info(`HTML Scanner: Axe scan of ${job.url} finished!`);
  } catch (e) {
    await shutdown(browser).then(function () {
      throw new Error(`Axe error: ${e}`);
    });
  }

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

  // TODO integrate equalify format conversion

  await shutdown(browser).then(()=>{
    logger.info(`HTML Scanner: Job "${job.id}" - Browser shutdown.`);
  });
  return {
    createdDate: new Date(), // record to add
    axeresults: results,
    jobID: job.id,
    //editoria11yResults: editoria11yResults,
  };

  async function shutdown(browser: Browser) {
    const pages = await browser.pages();
    for (let i = 0; i < pages.length; i++) {
      await pages[i].close();
    }
    await browser.close();
    return;
  }
}
