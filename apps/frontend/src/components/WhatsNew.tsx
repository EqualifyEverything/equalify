import { useId } from "react";
import * as Collapsible from "@radix-ui/react-collapsible";
import { LuChevronDown, LuSparkles, LuX } from "react-icons/lu";
import { useWhatsNew } from "#src/queries/useWhatsNew.ts";
import { useGlobalStore } from "#src/utils/useGlobalStore.ts";
import { StyledButton } from "./StyledButton";
import styles from "./WhatsNew.module.scss";

export const WhatsNew = () => {
  const headingId = useId();
  const { data: entry } = useWhatsNew();
  const {
    whatsNewDismissedId,
    setWhatsNewDismissedId,
    whatsNewExpandedId,
    setWhatsNewExpandedId,
  } = useGlobalStore();

  if (!entry || whatsNewDismissedId === entry.id) return null;

  // collapsed by default; expanding is remembered only for the current entry
  const open = whatsNewExpandedId === entry.id;

  const handleDismiss = () => {
    setWhatsNewDismissedId(entry.id);
    // the dismiss button is about to unmount, so move focus back to the page heading
    (document.getElementsByClassName("initial-focus-element")[0] as HTMLElement | undefined)?.focus();
  };

  return (
    <section className={styles.WhatsNew} aria-labelledby={headingId}>
      <Collapsible.Root
        open={open}
        onOpenChange={(isOpen) => setWhatsNewExpandedId(isOpen ? entry.id : null)}
      >
        <div className={styles.header}>
          <LuSparkles className={styles.icon} aria-hidden="true" />
          <div className={styles.titles}>
            <h2 id={headingId}>
              <Collapsible.Trigger className={styles["title-trigger"]}>
                <span className={styles.eyebrow}>What's new:</span> {entry.title}
              </Collapsible.Trigger>
            </h2>
            <time dateTime={entry.date}>{prettyDate(entry.date)}</time>
          </div>
          <div className={styles.actions}>
            {/* Duplicate of the headline button for pointer users, so it's hidden from keyboard and screen readers */}
            <Collapsible.Trigger asChild>
              <StyledButton
                onClick={undefined}
                label="Show update details"
                showLabel={false}
                variant="dark"
                tabIndex={-1}
                aria-hidden="true"
                icon={<LuChevronDown className={styles.chevron} />}
              />
            </Collapsible.Trigger>
            <StyledButton
              onClick={handleDismiss}
              label="Dismiss what's new"
              showLabel={false}
              variant="dark"
              icon={<LuX />}
            />
          </div>
        </div>
        <Collapsible.Content>
          <div
            className={styles.body}
            dangerouslySetInnerHTML={{ __html: entry.html }}
          />
        </Collapsible.Content>
      </Collapsible.Root>
    </section>
  );
};

// Dates in WHATS_NEW.md are calendar dates, so format in UTC to avoid shifting a day
function prettyDate(date: string) {
  const parsed = new Date(date);
  if (isNaN(parsed.getTime())) return date;
  return parsed.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
    timeZone: "UTC",
  });
}
