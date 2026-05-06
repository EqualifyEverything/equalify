/*
/* Converts Vera results to Equalify v2 Blockers format (formerly stream2)
/*
/* See equalifyv2 format types in shared/types/
*/

import {
  Blocker,
  StreamResults,
} from "../types/streamResults.equalifyV2_format";

// Inferred Interfaces from results
interface CheckDetail {
  // Whether this individual check instance passed or failed — lowercase in actual output
  // e.g. "failed", "passed", "warning"
  status: string;
  
  // XPath-like path to the PDF object that triggered this check
  // e.g. "root/document[0]/pages[0]/page[0]/annots[0]/annot[0]"
  context: string;
  
  // Human-readable error message, with errorArguments substituted in
  errorMessage: string;
  
  // Arguments substituted into the error message template; entries may be null
  errorArguments: (string | null)[];
  
  // Present in some veraPDF versions — secondary location info
  location?: {
    level: string;
    context: string;
  };
}

interface RuleSummary {
  // Lowercase status matching CheckDetail — e.g. "failed", "passed"
  status: string;

  // Uppercase aggregate status for the rule as a whole - "FAILED" | "PASSED" | "WARNING";
  ruleStatus: string;
  
  // The standards body document — e.g. "ISO 14289-2:2024", "WCAG 2.1"
  specification: string;
  
  // Section/clause within the specification — e.g. "7.1", "Table 1", "Annex A"
  clause: string;
  
  // Test number within the clause — e.g. 1, 2
  testNumber: number;
  
  // Human-readable description of what the rule checks
  description: string;
  
  // The PDF object type this rule operates on
  // e.g. "PDDocument", "PDPage", "PDAnnot", "CosStream", "PDStructElem"
  object: string;
  
  // The actual validation test expression evaluated against the object
  // e.g. "hasTag" or a boolean XPath expression — often highly technical
  test: string;
  
  // Classification tags — e.g. ["PDF/UA-2"], ["WCAG21", "PDF/UA-2"]
  tags?: string[] | null;
  
  // Absent (not 0) when recordPasses=false and no passes were recorded
  passedChecks?: number;
  failedChecks: number;
  
  // Individual check instances — only present for FAILED rules when recordPasses=false
  checks?: CheckDetail[];
}

interface ValidationResult {
  details: {
    // Aggregate of all tags across all rule summaries in this validation result
    tags: string[];
    ruleSummaries: RuleSummary[];
  };
}

interface ReportJob {
  validationResult: ValidationResult[];
}

export interface ReportData {
  report: {
    jobs: ReportJob[];
  };
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function convertVeraToEqualifyV2(reportData: ReportData, job: any): StreamResults {
  const blockers: Blocker[] = [];

    try {
      // Ensure the path to jobs exists before proceeding
      if (!reportData?.report?.jobs) {
        /* console.warn(
          "Input JSON structure error!"
        ); */
        throw new Error;
      }

      // Traverse the jobs array
      for (const job of reportData.report.jobs) {
        if (!job.validationResult) continue;

        // Traverse the validation results array
        for (const validation of job.validationResult) {
          if (!validation.details?.ruleSummaries) continue;

          // Traverse the rule summaries array
          for (const ruleSummary of validation.details.ruleSummaries) {
            // Only process rules that have failed and have detailed check data
            if (ruleSummary.ruleStatus !== "FAILED" || !ruleSummary.checks) {
              continue;
            }

            // Map each individual check detail to the Blocker format
            for (const check of ruleSummary.checks) {
              const blocker: Blocker = {
                source: 'pdf-scan',
                tags: ruleSummary.tags || null,
                description: ruleSummary.description,

                // Fields specific to the individual CheckDetail instance
                test: ruleSummary.specification + " - " + ruleSummary.clause, // This ultimately maps to category
                summary: check.errorMessage,
                node: check.context,
              };
              blockers.push(blocker);
            }
          }
        }
      }
    } catch (error) {
      throw error;
    }
    const timeNow = new Date().toISOString();
    const out: StreamResults = {
      auditId: job.auditId,
      scanId: job.scanId,
      urlId: job.urlId,
      blockers,
      date: timeNow,
      message: `Vera scan of ${job.url} complete.`,
      status: "complete"
    };

    return out;
}

export default convertVeraToEqualifyV2;
