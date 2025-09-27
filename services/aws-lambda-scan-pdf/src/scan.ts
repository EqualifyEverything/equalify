import { logger } from "./telemetry.ts";
//import * as path from "path";
//import * as fspromises from "fs/promises";
//import * as fs from "fs";
//import fetch from "node-fetch";
//import { exec } from "child_process";

//const BROWSER_LOAD_TIMEOUT = 25000;
import { SqsScanJob } from "../../../shared/types/sqsScanJob.ts";
import { InvocationType, InvokeCommand, InvokeCommandOutput, LambdaClient } from "@aws-sdk/client-lambda";

export default async function (job: SqsScanJob) {
  logger.info(`PDF Scanner: UrlId [${job.urlId}](${job.url}) started.`);
  
  let veraPdfReport = "";

  // TODO validate URL
  const url = job.url;

  // invoke java scannning lambda
  const lambdaClient = new LambdaClient({ region: "us-east-2" });
  const invokeParams = {
    FunctionName: "aws-lambda-verapdf-interface", 
    InvocationType: InvocationType.RequestResponse, 
    Payload: url,
  };

  try {
    const command = new InvokeCommand(invokeParams);
    const response: InvokeCommandOutput = await lambdaClient.send(command);

    const resultPayload = response.Payload ? JSON.parse(new TextDecoder().decode(response.Payload)) : null;
    logger.info(resultPayload);
    veraPdfReport = resultPayload;
    
  } catch (error) {
    logger.error(error as string);
  }

  return veraPdfReport;
}
