/*
/* Converts Axe-core axeResult results to Equalify v2 Blockers format (formerly stream2)
/* Taken from https://github.com/EqualifyEverything/scan.equalify.app/blob/dev/src/convertors/streamtwo.ts 
/*
/* See equalifyv2 format types in shared/types/
*/

import { Blocker, StreamResults } from "../types/streamResults.equalifyV2_format";
import { AxeResults } from 'axe-core'

// Rules whose "incomplete" (needs review) results are NOT reported as blockers.
// axe reports an inconclusive contrast check most often when it can't determine
// the background (text over an image, gradient or overlapping element), and
// treating those as failures produced thousands of false contrast blockers on
// image-heavy sites. Every other incomplete result is still reported on purpose:
// the custom pdf-link and empty-alt-tag rules are declared reviewOnFail and only
// ever report through that channel.
const DROP_INCOMPLETE_FOR = new Set(["color-contrast", "color-contrast-enhanced", "link-in-text-block"]);

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function convertToEqualifyV2(axeResult: AxeResults, job: any):StreamResults {
    const blockers: Blocker[] = [];

    if (axeResult) {

        // convert the axe results: violations plus incomplete (needs review)
        // results, minus the inconclusive contrast checks (see DROP_INCOMPLETE_FOR)
        const axeResults = [
            axeResult.incomplete?.filter((el) => !DROP_INCOMPLETE_FOR.has(el.id)),
            axeResult.violations,
        ]
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
        scanId: job.scanId,
        urlId: job.urlId,
        url: job.url,
        blockers,
        date,
        message,
        status: "complete"
    };

    return out;
}

export default convertToEqualifyV2;