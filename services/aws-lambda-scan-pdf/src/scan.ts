import { logger } from "./telemetry.ts";
import * as path from "path";
import * as fspromises from "fs/promises";
//import * as fs from "fs";
import fetch from "node-fetch";
import { exec } from "child_process";

//const BROWSER_LOAD_TIMEOUT = 25000;
import { SqsScanJob } from "../../../shared/types/sqsScanJob.ts";

export default async function (job: SqsScanJob) {
  logger.info(`PDF Scanner: Job [${job.id}](${job.url}) started.`);
  // download PDF from url
  const url = job.url;
  const fileName = url.split("/").pop();
  if (!fileName) throw new Error(`Error with PDF url: ${url}`);

  let filePath = "";
  try {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(
        `Error downloading PDF: ${url}: ${response.status} ${response.statusText}`
      );
    }

    const arrayBuffer = await response.arrayBuffer();
    const buffer = Buffer.from(arrayBuffer);
    filePath = path.join("/tmp", fileName); // Saves in the same directory as the script
    await fspromises.writeFile(filePath, buffer);
    console.log(`PDF downloaded! Path: ${filePath}`);
  } catch (err) {
    console.log("PDF Download Error", err);
    throw err;
  }

  // Run the vera scan
  let veraPdfReport = "";
  try {
    const nonpdfextFlag = filePath.toLowerCase().endsWith(".pdf")
      ? ""
      : "--nonpdfext ";
    const stdout = await execRun(
      "/opt/bin/vera/verapdf -f ua2 --format json " + nonpdfextFlag + filePath
    );
    let report = stdout as string;
    logger.info("Vera exec() output:", report);
    veraPdfReport = JSON.parse(report);
  } catch (error) {
    console.error("Error in vera exec():", error);
  }

  // Delete the file
  /* try {
    fs.unlinkSync(filePath);
    console.log("PDF deleted successfully!");
  } catch (err) {
    console.error("Error deleting file:", err);
  } */

  return veraPdfReport;
}

function execRun(command: string): Promise<string> {
  return new Promise((resolve, reject) => {
    exec(command, (error, stdout, stderr) => {
      if (error) {
        logger.error(`exec error: ${error}`);
        reject(error);
        return;
      }
      if (stderr) {
        logger.warn(`stderr: ${stderr}`);
      }
      resolve(stdout.trim()); // Trim whitespace from stdout
    });
  });
}
