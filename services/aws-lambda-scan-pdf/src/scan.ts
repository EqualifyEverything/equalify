import { logger } from "./telemetry.ts";
import * as path from "path";
import * as fspromises from "fs/promises";
import * as fs from "fs";
import fetch from "node-fetch";
import { exec } from "child_process";

//const BROWSER_LOAD_TIMEOUT = 25000;
import {SqsScanJob} from '../../../shared/types/sqsScanJob.ts'

export default async function (job: SqsScanJob) {
  logger.info(`PDF Scanner: Job [${job.id}](${job.url}) started.`);
  // download PDF from url
  const url = job.url;
  const fileName = url.split("/").pop();
  if(!fileName) 
    throw new Error(
        `Error with PDF url: ${url}`
      );

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
    veraPdfReport = JSON.parse(report);
    //console.log("Vera exec() output:\n", stdout);
  } catch (error) {
    console.error("Error in vera exec():", error);
  }

  // Delete the file
  try {
    fs.unlinkSync(filePath);
    console.log("PDF deleted successfully!");
  } catch (err) {
    console.error("Error deleting file:", err);
  }

  return veraPdfReport;
  
}

const execRun = (cmd:string) => {
  return new Promise((resolve, reject) => {
    exec(cmd, (error, stdout) => {
      if (error) {
        if (error.code === 1) {
          // leaks present
          resolve(stdout);
        } else {
          // gitleaks error
          reject(error);
        }
      } else {
        // no leaks
        resolve(stdout);
      }
    })
  })
}