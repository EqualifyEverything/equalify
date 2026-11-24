import styles from "./NotificationPip.module.scss";

interface NotificationPipProps {
  count: number;
  /** Screen reader text, e.g. (n) => `${n} pending access requests` */
  label: (count: number) => string;
}

/**
 * Small dot pinned to the top-right of its (position: relative) parent.
 * The dot is decorative; the count is exposed as visually hidden text so it
 * becomes part of the parent's accessible name, e.g. a link read as
 * "Account, 2 pending access requests".
 */
export const NotificationPip = ({ count, label }: NotificationPipProps) => {
  if (!count) return null;
  return (
    <>
      <span className="sr-only">, {label(count)}</span>
      <span className={styles.pip} aria-hidden="true" />
    </>
  );
};
