import { useRef, useState } from "react";
import * as Tooltip from "@radix-ui/react-tooltip";
import { AuditTag } from "../queries";
import styles from "./AuditTagList.module.scss";

interface AuditTagListProps {
  tags: AuditTag[];
  label?: string;
  className?: string;
  /** Show at most this many chips; the rest go in a "+N" tooltip */
  maxVisible?: number;
}

// Read-only tag chips (audit cards and table, shared reports)
export const AuditTagList = ({ tags, label = "Tags", className = "", maxVisible }: AuditTagListProps) => {
  const [moreOpen, setMoreOpen] = useState(false);
  const moreTriggerRef = useRef<HTMLButtonElement>(null);
  if (!tags.length) return null;

  const visibleTags = maxVisible === undefined ? tags : tags.slice(0, maxVisible);
  const hiddenTags = tags.slice(visibleTags.length);
  const moreLabel = `+${hiddenTags.length} more tag${hiddenTags.length === 1 ? "" : "s"}`;

  return (
    <div className={styles.AuditTagList + " " + className}>
      <ul aria-label={label}>
        {visibleTags.map((tag) => (
          <li key={tag.id}>{tag.name}</li>
        ))}
      </ul>
      {hiddenTags.length > 0 && (
        // Kept outside the list so screen readers' item count matches what's
        // shown. The trigger is announced as e.g. "+3 more tags, button",
        // followed by the hidden names as its description.
        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root open={moreOpen} onOpenChange={setMoreOpen}>
            <Tooltip.Trigger
              ref={moreTriggerRef}
              type="button"
              className={styles.more}
              aria-label={moreLabel}
              onClick={(e) => {
                // Radix tooltips only open on hover/focus; clicking (or
                // tapping on touch screens) toggles it instead of closing it
                e.preventDefault();
                setMoreOpen((open) => !open);
              }}
            >
              {`+${hiddenTags.length}`}
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="bottom"
                className={"tooltip " + styles.tooltip}
                collisionPadding={8}
                // Read as a comma-separated list rather than run-together chips
                aria-label={hiddenTags.map((tag) => tag.name).join(", ")}
                onPointerDownOutside={(e) => {
                  // let the trigger's own click toggle it closed
                  if (moreTriggerRef.current?.contains(e.target as Node)) e.preventDefault();
                }}
              >
                <ul>
                  {hiddenTags.map((tag) => (
                    <li key={tag.id}>{tag.name}</li>
                  ))}
                </ul>
                <Tooltip.Arrow className="tooltip-arrow" />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>
      )}
    </div>
  );
};
