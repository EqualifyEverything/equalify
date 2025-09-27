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
  status: string;
  context: string;
  errorMessage: string;
  errorArguments: string[];
}

interface RuleSummary {
  ruleStatus: "FAILED" | "PASSED" | "WARNING";
  specification: string; // Maps to Blocker.source
  tags?: string[] | null; // Maps to Blocker.tags
  description: string; // Maps to Blocker.description
  checks?: CheckDetail[];
}

interface ValidationResult {
  details: {
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
        console.warn(
          "Input JSON structure error!"
        );
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
                test: ruleSummary.specification,
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
      urlId: job.urlId,
      blockers,
      date: timeNow,
      message: `Vera scan of ${job.url} complete.`,
    };

    return out;
}

export default convertVeraToEqualifyV2;
