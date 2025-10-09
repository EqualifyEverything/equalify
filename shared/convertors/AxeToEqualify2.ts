/*
/* Converts Axe-core axeResult results to Equalify v2 Blockers format (formerly stream2)
/* Taken from https://github.com/EqualifyEverything/scan.equalify.app/blob/dev/src/convertors/streamtwo.ts 
/*
/* See equalifyv2 format types in shared/types/
*/

import { Blocker, StreamResults } from "../types/streamResults.equalifyV2_format";
import { AxeResults } from 'axe-core'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function convertToEqualifyV2(axeResult: AxeResults, job: any):StreamResults {
    const blockers: Blocker[] = [];

    if (axeResult) {

        // convert the axe results
        const axeResults = [axeResult.incomplete, axeResult.violations]
        axeResults?.forEach(path => {
            if (path)
                path.forEach((el) => {
                    const testName = el.id;
                    const tags = el.tags;
                    const description = el.description + ". " + el.help
                    el.nodes?.forEach((node) => {
                        const out: Blocker = {
                            source: "axe-core",
                            test: testName,
                            tags,
                            description,
                            summary: node.failureSummary ?? "",
                            node: node.html
                        }
                        blockers.push(out);
                    });
                });
        })


        // TODO Move editoria11y and PDF conversions to separate functions
        // convert the editoria11y results
        /* axeResult.result?.editoria11yResults?.forEach((el: { test: string; content: string; node: string; }) => {
            const out: Blocker = {
                source: "editoria11y",
                test: el.test,
                tags: [],
                description: el.content,
                summary: "",
                node: el.node
            }
            blockers.push(out);
        }); */

        // convert the pdf results
        /* if (axeResult && axeResult.result && axeResult.result.PDFresults && axeResult.result.PDFresults["Detailed Report"]) {
            const detailedReportAreas = ["Document", "Page Content", "Forms", "Alternate Text", "Tables", "Lists", "Headings"];
            detailedReportAreas.forEach(area => {
                if (area in axeResult.result.PDFresults["Detailed Report"]) {
                    axeResult.result.PDFresults["Detailed Report"][area].forEach((adobeRule: { Rule: string; Description: string; Status: string }) => {
                        if (adobeRule.Status !== "Passed") {
                            const out: Blocker = {
                                source: "pdf-scan",
                                test: adobeRule.Rule,
                                tags: [],
                                description: adobeRule.Status + ": " + adobeRule.Description,
                                summary: "Error found in: " + area,
                                node: ""
                            }
                            blockers.push(out);
                        }
                    })
                }
            })
        } */


    }

    let date = "";

    if (axeResult.timestamp) {
        date = axeResult.timestamp;
    }

    let message = "";
    // if result.results is a string, it's a message, relay that
    if (typeof axeResult == "string") {
        message = axeResult;
    }

    const out: StreamResults = {
        auditId: job.auditId,
        urlId: job.urlId,
        blockers,
        date,
        message,
        status: "complete"
    };

    return out;
}

export default convertToEqualifyV2;