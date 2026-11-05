import { useId, useState } from "react";
import * as VisuallyHidden from "@radix-ui/react-visually-hidden";
import { Link } from "react-router-dom";
import { formatId } from "../utils";
import { AuditTag } from "../queries";
import styles from "./AuditTagList.module.scss";

interface AuditTagListProps {
  tags: AuditTag[];
  label?: string;
  className?: string;
  /** Show at most this many chips until "View all N tags" is selected */
  maxVisible?: number;
  /** Make the chips links to each tag's report (signed-in views only) */
  linkToReport?: boolean;
  /** Horizontal alignment of the "View all N tags" toggle below the tags */
  toggleAlign?: "start" | "center" | "end";
}

// Read-only tag chips (audit cards and table, shared reports)
export const AuditTagList = ({ tags, label = "Tags", className = "", maxVisible, linkToReport = false, toggleAlign = "start" }: AuditTagListProps) => {
  const [expanded, setExpanded] = useState(false);
  const listId = useId();
  if (!tags.length) return null;

  const isTruncated = maxVisible !== undefined && tags.length > maxVisible;
  const shownTags = isTruncated && !expanded ? tags.slice(0, maxVisible) : tags;

  return (
    <div className={styles.AuditTagList + " " + className}>
      <ul id={listId} aria-label={label}>
        {shownTags.map((tag) => (
          <li key={tag.id} className={linkToReport ? styles.linked : undefined}>
            {linkToReport ? (
              <Link to={`/tags/${formatId(tag.id)}`}>
                {tag.name}
                <VisuallyHidden.Root> tag report</VisuallyHidden.Root>
              </Link>
            ) : tag.name}
          </li>
        ))}
      </ul>
      {isTruncated && (
        // A disclosure button rather than a tooltip, so the extra tags render
        // in the same list (and can be links). Focus stays on the button.
        <button
          type="button"
          className={styles.toggle + (toggleAlign === "start" ? "" : " " + styles[`align-${toggleAlign}`])}
          aria-expanded={expanded}
          aria-controls={listId}
          onClick={() => setExpanded((value) => !value)}
        >
          {expanded ? "Show fewer tags" : `View all ${tags.length} tags`}
        </button>
      )}
    </div>
  );
};
