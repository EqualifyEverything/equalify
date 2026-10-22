import { AuditTag } from "../queries";
import styles from "./AuditTagList.module.scss";

interface AuditTagListProps {
  tags: AuditTag[];
  label?: string;
  className?: string;
}

// Read-only tag chips (audit cards and table, shared reports)
export const AuditTagList = ({ tags, label = "Tags", className = "" }: AuditTagListProps) => {
  if (!tags.length) return null;
  return (
    <ul className={styles.AuditTagList + " " + className} aria-label={label}>
      {tags.map((tag) => (
        <li key={tag.id}>{tag.name}</li>
      ))}
    </ul>
  );
};
