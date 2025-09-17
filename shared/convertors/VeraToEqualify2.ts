/*
/* Converts Vera results to Equalify v2 Blockers format (formerly stream2)
/* Taken from https://github.com/EqualifyEverything/scan.equalify.app/blob/dev/src/convertors/streamtwo.ts 
/*
/* See equalifyv2 format types in shared/types/
*/

import { Blocker, StreamResults } from "../types/streamResults.equalifyV2_format";

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function convertToEqualifyV2(PDFresults: JSON):StreamResults {
    const blockers: Blocker[] = [];

    if (PDFresults) {

        // convert the pdf results
        if (PDFresults && PDFresults["Detailed Report"]) {
            const detailedReportAreas = ["Document", "Page Content", "Forms", "Alternate Text", "Tables", "Lists", "Headings"];
            detailedReportAreas.forEach(area => {
                if (area in PDFresults["Detailed Report"]) {
                    PDFresults["Detailed Report"][area].forEach((adobeRule: { Rule: string; Description: string; Status: string }) => {
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
        } 


    }

    let date = "";
    let id = "";

    /* if (PDFresults.createdDate) {
        date = json.result?.createdDate;
    } */

    let message = "";
    // if result.results is a string, it's a message, relay that
    /* if (typeof axeResult == "string") {
        message = axeResult;
    } */

    const out: StreamResults = {
        id,
        blockers,
        date,
        message
    };

    return out;
}

export default convertToEqualifyV2;