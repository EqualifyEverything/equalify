import { ReactNode } from "react";
import { IGNORE_STATUSES, ignoreStatusLabel } from "../utils";
import style from "./IgnoreStatusSelect.module.scss";

interface IgnoreStatusSelectProps {
  /** Current status of the ignored blocker; null/undefined = none set */
  status?: string | null;
  /** Short id shown to screen readers, e.g. "Ignore status for blocker AB12" */
  blockerLabel: string;
  /** Omit (or pass readOnly) to show the status as plain text */
  onChange?: (status: string | null) => void;
  readOnly?: boolean;
  disabled?: boolean;
}

/**
 * Wraps the Ignore toggle (and the status picker under it) so both share one
 * width, and so Active and Ignored rows line up down the column.
 */
export const IgnoreCell = ({ children }: { children: ReactNode }) => (
  <div className={style["ignore-stack"]}>{children}</div>
);

/**
 * Status picker for an ignored blocker, shown under the Ignore toggle in the
 * Detailed table and the Recommendations view. Read-only (past scans, shared
 * reports) it renders the label as text, or nothing when no status is set.
 */
export const IgnoreStatusSelect = ({ status, blockerLabel, onChange, readOnly, disabled }: IgnoreStatusSelectProps) => {
  if (readOnly || !onChange) {
    const label = ignoreStatusLabel(status);
    return label ? <span className={style["status-text"]}>{label}</span> : null;
  }
  const known = !status || IGNORE_STATUSES.some((s) => s.value === status);
  return (
    <div className={style["status-wrap"]}>
    <select
      aria-label={`Ignore status for blocker ${blockerLabel}`}
      value={status ?? ""}
      disabled={disabled}
      onChange={(e) => onChange(e.target.value || null)}
    >
      <option value="">No status</option>
      {IGNORE_STATUSES.map((s) => (
        <option key={s.value} value={s.value}>{s.label}</option>
      ))}
      {!known && <option value={status!}>{status}</option>}
    </select>
    </div>
  );
};
