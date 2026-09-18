// Blockers carry a raw "tags" array sourced verbatim from whichever scanner
// produced them: axe-core tags rules with WCAG/Section 508/EN 301 549/Trusted
// Tester references (e.g. "wcag143", "section508.22.a") plus axe's own
// internal groupings ("cat.color", "best-practice"); veraPDF tags PDF/UA
// structural elements ("figure", "heading", "artifact") using the same
// "tags" column. Only the former are a recognized accessibility standard a
// user would meaningfully filter by — this is a strict allowlist (unmatched
// tags are treated as non-standard and excluded) rather than a denylist,
// since there's no complete enumeration of every possible veraPDF term.
//
// WCAG success criteria table sourced from the official W3C quick reference
// (https://www.w3.org/WAI/WCAG22/quickref/) — SC 4.1.1 Parsing is included
// even though WCAG 2.2 deprecated it, since it's still what older axe-core
// rule data (and audits run against WCAG 2.0/2.1) tags blockers with.
//
// Coverage cross-checked against every tag axe-core actually assigns, per
// https://raw.githubusercontent.com/dequelabs/axe-core/develop/doc/rule-descriptions.md
// (all WCAG/Section 508/EN 301 549/Trusted Tester tags there already matched
// this file; "experimental"/"deprecated" — axe's own rule-maturity flags,
// not standards references — correctly fall through to "not a standard").
const WCAG_SUCCESS_CRITERIA: Record<string, { number: string; name: string; level: string }> = {
  wcag111: { number: "1.1.1", name: "Non-text Content", level: "A" },
  wcag121: { number: "1.2.1", name: "Audio-only and Video-only (Prerecorded)", level: "A" },
  wcag122: { number: "1.2.2", name: "Captions (Prerecorded)", level: "A" },
  wcag123: { number: "1.2.3", name: "Audio Description or Media Alternative (Prerecorded)", level: "A" },
  wcag124: { number: "1.2.4", name: "Captions (Live)", level: "AA" },
  wcag125: { number: "1.2.5", name: "Audio Description (Prerecorded)", level: "AA" },
  wcag126: { number: "1.2.6", name: "Sign Language (Prerecorded)", level: "AAA" },
  wcag127: { number: "1.2.7", name: "Extended Audio Description (Prerecorded)", level: "AAA" },
  wcag128: { number: "1.2.8", name: "Media Alternative (Prerecorded)", level: "AAA" },
  wcag129: { number: "1.2.9", name: "Audio-only (Live)", level: "AAA" },
  wcag131: { number: "1.3.1", name: "Info and Relationships", level: "A" },
  wcag132: { number: "1.3.2", name: "Meaningful Sequence", level: "A" },
  wcag133: { number: "1.3.3", name: "Sensory Characteristics", level: "A" },
  wcag134: { number: "1.3.4", name: "Orientation", level: "AA" },
  wcag135: { number: "1.3.5", name: "Identify Input Purpose", level: "AA" },
  wcag136: { number: "1.3.6", name: "Identify Purpose", level: "AAA" },
  wcag141: { number: "1.4.1", name: "Use of Color", level: "A" },
  wcag142: { number: "1.4.2", name: "Audio Control", level: "A" },
  wcag143: { number: "1.4.3", name: "Contrast (Minimum)", level: "AA" },
  wcag144: { number: "1.4.4", name: "Resize Text", level: "AA" },
  wcag145: { number: "1.4.5", name: "Images of Text", level: "AA" },
  wcag146: { number: "1.4.6", name: "Contrast (Enhanced)", level: "AAA" },
  wcag147: { number: "1.4.7", name: "Low or No Background Audio", level: "AAA" },
  wcag148: { number: "1.4.8", name: "Visual Presentation", level: "AAA" },
  wcag149: { number: "1.4.9", name: "Images of Text (No Exception)", level: "AAA" },
  wcag1410: { number: "1.4.10", name: "Reflow", level: "AA" },
  wcag1411: { number: "1.4.11", name: "Non-text Contrast", level: "AA" },
  wcag1412: { number: "1.4.12", name: "Text Spacing", level: "AA" },
  wcag1413: { number: "1.4.13", name: "Content on Hover or Focus", level: "AA" },
  wcag211: { number: "2.1.1", name: "Keyboard", level: "A" },
  wcag212: { number: "2.1.2", name: "No Keyboard Trap", level: "A" },
  wcag213: { number: "2.1.3", name: "Keyboard (No Exception)", level: "AAA" },
  wcag214: { number: "2.1.4", name: "Character Key Shortcuts", level: "A" },
  wcag221: { number: "2.2.1", name: "Timing Adjustable", level: "A" },
  wcag222: { number: "2.2.2", name: "Pause, Stop, Hide", level: "A" },
  wcag223: { number: "2.2.3", name: "No Timing", level: "AAA" },
  wcag224: { number: "2.2.4", name: "Interruptions", level: "AAA" },
  wcag225: { number: "2.2.5", name: "Re-authenticating", level: "AAA" },
  wcag226: { number: "2.2.6", name: "Timeouts", level: "AAA" },
  wcag231: { number: "2.3.1", name: "Three Flashes or Below Threshold", level: "A" },
  wcag232: { number: "2.3.2", name: "Three Flashes", level: "AAA" },
  wcag233: { number: "2.3.3", name: "Animation from Interactions", level: "AAA" },
  wcag241: { number: "2.4.1", name: "Bypass Blocks", level: "A" },
  wcag242: { number: "2.4.2", name: "Page Titled", level: "A" },
  wcag243: { number: "2.4.3", name: "Focus Order", level: "A" },
  wcag244: { number: "2.4.4", name: "Link Purpose (In Context)", level: "A" },
  wcag245: { number: "2.4.5", name: "Multiple Ways", level: "AA" },
  wcag246: { number: "2.4.6", name: "Headings and Labels", level: "AA" },
  wcag247: { number: "2.4.7", name: "Focus Visible", level: "AA" },
  wcag248: { number: "2.4.8", name: "Location", level: "AAA" },
  wcag249: { number: "2.4.9", name: "Link Purpose (Link Only)", level: "AAA" },
  wcag2410: { number: "2.4.10", name: "Section Headings", level: "AAA" },
  wcag2411: { number: "2.4.11", name: "Focus Not Obscured (Minimum)", level: "AA" },
  wcag2412: { number: "2.4.12", name: "Focus Not Obscured (Enhanced)", level: "AAA" },
  wcag2413: { number: "2.4.13", name: "Focus Appearance", level: "AAA" },
  wcag251: { number: "2.5.1", name: "Pointer Gestures", level: "A" },
  wcag252: { number: "2.5.2", name: "Pointer Cancellation", level: "A" },
  wcag253: { number: "2.5.3", name: "Label in Name", level: "A" },
  wcag254: { number: "2.5.4", name: "Motion Actuation", level: "A" },
  wcag255: { number: "2.5.5", name: "Target Size (Enhanced)", level: "AAA" },
  wcag256: { number: "2.5.6", name: "Concurrent Input Mechanisms", level: "AAA" },
  wcag257: { number: "2.5.7", name: "Dragging Movements", level: "AA" },
  wcag258: { number: "2.5.8", name: "Target Size (Minimum)", level: "AA" },
  wcag311: { number: "3.1.1", name: "Language of Page", level: "A" },
  wcag312: { number: "3.1.2", name: "Language of Parts", level: "AA" },
  wcag313: { number: "3.1.3", name: "Unusual Words", level: "AAA" },
  wcag314: { number: "3.1.4", name: "Abbreviations", level: "AAA" },
  wcag315: { number: "3.1.5", name: "Reading Level", level: "AAA" },
  wcag316: { number: "3.1.6", name: "Pronunciation", level: "AAA" },
  wcag321: { number: "3.2.1", name: "On Focus", level: "A" },
  wcag322: { number: "3.2.2", name: "On Input", level: "A" },
  wcag323: { number: "3.2.3", name: "Consistent Navigation", level: "AA" },
  wcag324: { number: "3.2.4", name: "Consistent Identification", level: "AA" },
  wcag325: { number: "3.2.5", name: "Change on Request", level: "AAA" },
  wcag326: { number: "3.2.6", name: "Consistent Help", level: "A" },
  wcag331: { number: "3.3.1", name: "Error Identification", level: "A" },
  wcag332: { number: "3.3.2", name: "Labels or Instructions", level: "A" },
  wcag333: { number: "3.3.3", name: "Error Suggestion", level: "AA" },
  wcag334: { number: "3.3.4", name: "Error Prevention (Legal, Financial, Data)", level: "AA" },
  wcag335: { number: "3.3.5", name: "Help", level: "AAA" },
  wcag336: { number: "3.3.6", name: "Error Prevention (All)", level: "AAA" },
  wcag337: { number: "3.3.7", name: "Redundant Entry", level: "A" },
  wcag338: { number: "3.3.8", name: "Accessible Authentication (Minimum)", level: "AA" },
  wcag339: { number: "3.3.9", name: "Accessible Authentication (Enhanced)", level: "AAA" },
  wcag411: { number: "4.1.1", name: "Parsing", level: "A" },
  wcag412: { number: "4.1.2", name: "Name, Role, Value", level: "A" },
  wcag413: { number: "4.1.3", name: "Status Messages", level: "AA" },
};

const WCAG_LEVEL_TAGS: Record<string, string> = {
  wcag2a: "WCAG 2.0 Level A",
  wcag2aa: "WCAG 2.0 Level AA",
  wcag2aaa: "WCAG 2.0 Level AAA",
  wcag21a: "WCAG 2.1 Level A",
  wcag21aa: "WCAG 2.1 Level AA",
  wcag21aaa: "WCAG 2.1 Level AAA",
  wcag22a: "WCAG 2.2 Level A",
  wcag22aa: "WCAG 2.2 Level AA",
  wcag22aaa: "WCAG 2.2 Level AAA",
};

const SECTION508_PARAGRAPH_RE = /^section508\.22\.([a-p])$/i;
// EN 301 549 chapter 9 incorporates WCAG 2.1 by reference using identical
// success-criterion numbers under its own clause numbering (EN-9.1.4.3 is
// clause 9.1.4.3, which is WCAG SC 1.4.3) — so the WCAG equivalent can be
// looked up directly rather than needing a separate EN-specific table.
const EN_CLAUSE_RE = /^EN-9((?:\.\d+){2,3})$/i;
const TT_VERSION_RE = /^TTv(\d+)$/i;
const TT_TEST_RE = /^TT(\d+)\.([a-z])$/i;
// RGAA (Référentiel Général d'Amélioration de l'Accessibilité) — France's
// formal accessibility standard, axe-core's fourth "regulation" tag family
// alongside WCAG/Section 508/EN 301 549. Criterion numbering is
// Thème.Critère.Test (e.g. "RGAA-1.1.2"); no public source gives a short
// English name per criterion the way WCAG's does, so (like Section 508
// paragraphs and Trusted Tester tests) this just decorates the raw number
// rather than inventing a description.
const RGAA_VERSION_RE = /^RGAAv(\d+)$/i;
const RGAA_CRITERION_RE = /^RGAA-(\d+(?:\.\d+){1,3})$/i;

/** Group names, in the order they should appear in the UI. */
export const ACCESSIBILITY_STANDARD_GROUP_ORDER = [
  "WCAG",
  "Section 508",
  "EN 301 549",
  "RGAA",
  "Trusted Tester",
  "Best Practice",
] as const;

export interface AccessibilityStandardTagInfo {
  label: string;
  group: (typeof ACCESSIBILITY_STANDARD_GROUP_ORDER)[number];
  /** String-sortable key for ordering within `group`. Not meaningful across groups. */
  sortKey: string;
}

// Zero-pads each dotted segment of a WCAG- or RGAA-style number
// ("1.4.10" -> "01.04.10") so plain string comparison sorts criteria in
// numeric order rather than the lexicographic order that would otherwise put
// e.g. "1.4.10" before "1.4.2".
function padDottedNumber(number: string): string {
  return number.split(".").map((part) => part.padStart(2, "0")).join(".");
}

/**
 * Classifies a raw scanner tag as a recognized accessibility standard (WCAG,
 * Section 508, EN 301 549, DHS Trusted Tester, or axe-core's
 * "best-practice"), returning its human-readable label plus grouping/sort
 * info for presenting a list of tags — or null if it's something else (an
 * axe-core internal category like "cat.color", a PDF/UA structural term like
 * "figure", etc.) that shouldn't be surfaced as a "standard" at all.
 */
export function getAccessibilityStandardTagInfo(rawTag: string): AccessibilityStandardTagInfo | null {
  const tag = rawTag.trim();
  const lower = tag.toLowerCase();

  if (lower === "best-practice") {
    return { label: "Best Practice", group: "Best Practice", sortKey: "0" };
  }
  if (lower === "section508") {
    return { label: "Section 508", group: "Section 508", sortKey: "0" };
  }
  if (lower === "en-301-549") {
    return { label: "EN 301 549", group: "EN 301 549", sortKey: "0" };
  }

  // axe-core's own tag for a rule that was part of WCAG 2.0 Level A but has
  // since been superseded — worth surfacing as WCAG-related, but flagged so
  // it isn't mistaken for a current requirement.
  if (lower === "wcag2a-obsolete") {
    return { label: "WCAG 2.0 Level A (Obsolete)", group: "WCAG", sortKey: "0-02-9" };
  }

  const level = WCAG_LEVEL_TAGS[lower];
  if (level) {
    // Broad conformance-level tags sort before individual success criteria
    // ("0-" prefix), ordered by WCAG version then level (A < AA < AAA).
    const [, version, levelLetters] = lower.match(/^wcag(2\d?)(a+)$/i) ?? [];
    return { label: level, group: "WCAG", sortKey: `0-${(version ?? "2").padStart(2, "0")}-${levelLetters?.length ?? 1}` };
  }

  const sc = WCAG_SUCCESS_CRITERIA[lower];
  if (sc) {
    return {
      label: `WCAG ${sc.number} ${sc.name} (Level ${sc.level})`,
      group: "WCAG",
      sortKey: `1-${padDottedNumber(sc.number)}`,
    };
  }

  const section508Match = tag.match(SECTION508_PARAGRAPH_RE);
  if (section508Match) {
    const paragraph = section508Match[1].toLowerCase();
    return { label: `Section 508 §1194.22(${paragraph})`, group: "Section 508", sortKey: `1-${paragraph}` };
  }

  const enMatch = tag.match(EN_CLAUSE_RE);
  if (enMatch) {
    const clause = `9${enMatch[1]}`;
    const clauseNumber = enMatch[1].slice(1);
    const equivalentSc = Object.values(WCAG_SUCCESS_CRITERIA).find((s) => s.number === clauseNumber);
    return {
      label: equivalentSc ? `EN 301 549 §${clause} (WCAG ${equivalentSc.number})` : `EN 301 549 §${clause}`,
      group: "EN 301 549",
      sortKey: `1-${padDottedNumber(clauseNumber)}`,
    };
  }

  const rgaaVersionMatch = tag.match(RGAA_VERSION_RE);
  if (rgaaVersionMatch) {
    return { label: `RGAA ${rgaaVersionMatch[1]}`, group: "RGAA", sortKey: `0-${rgaaVersionMatch[1].padStart(2, "0")}` };
  }

  const rgaaCriterionMatch = tag.match(RGAA_CRITERION_RE);
  if (rgaaCriterionMatch) {
    return {
      label: `RGAA §${rgaaCriterionMatch[1]}`,
      group: "RGAA",
      sortKey: `1-${padDottedNumber(rgaaCriterionMatch[1])}`,
    };
  }

  const ttVersionMatch = tag.match(TT_VERSION_RE);
  if (ttVersionMatch) {
    return {
      label: `DHS Trusted Tester v${ttVersionMatch[1]}`,
      group: "Trusted Tester",
      sortKey: `0-${ttVersionMatch[1].padStart(2, "0")}`,
    };
  }

  const ttTestMatch = tag.match(TT_TEST_RE);
  if (ttTestMatch) {
    const testLetter = ttTestMatch[2].toLowerCase();
    return {
      label: `DHS Trusted Tester Test ${ttTestMatch[1]}.${testLetter}`,
      group: "Trusted Tester",
      sortKey: `1-${ttTestMatch[1].padStart(2, "0")}.${testLetter}`,
    };
  }

  return null;
}

export function getAccessibilityStandardLabel(rawTag: string): string | null {
  return getAccessibilityStandardTagInfo(rawTag)?.label ?? null;
}

export function isAccessibilityStandardTag(rawTag: string): boolean {
  return getAccessibilityStandardTagInfo(rawTag) !== null;
}
