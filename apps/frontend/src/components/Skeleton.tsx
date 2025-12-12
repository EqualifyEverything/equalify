import styles from "./Skeleton.module.scss";

interface SkeletonProps {
  width?: string | number;
  height?: string | number;
  variant?: "text" | "title" | "circle" | "rounded" | "rect";
  className?: string;
  style?: React.CSSProperties;
}

export const Skeleton = ({
  width,
  height,
  variant = "rect",
  className = "",
  style = {},
}: SkeletonProps) => {
  const variantClass = variant === "rect" ? "" : styles[variant];

  return (
    <div
      className={`${styles.skeleton} ${variantClass} ${className}`}
      style={{
        width: typeof width === "number" ? `${width}px` : width,
        height: typeof height === "number" ? `${height}px` : height,
        ...style,
      }}
      aria-hidden="true"
    />
  );
};

export const SkeletonDataRow = ({ isFirst = false }: { isFirst?: boolean }) => (
  <div
    className={styles.skeletonDataRow}
    style={isFirst ? { borderBottom: "2px solid var(--gray, #e0dcd0)" } : {}}
  >
    <Skeleton className={styles.skeletonKey} />
    <Skeleton className={styles.skeletonValue} />
  </div>
);

export const SkeletonAuditCard = () => (
  <div className={`${styles.skeletonCard} card`}>
    <div className={styles.skeletonCardHeader}>
      <Skeleton className={`${styles.skeleton} ${styles.skeletonIcon}`} variant="rounded" />
      <Skeleton className={`${styles.skeleton} ${styles.skeletonTitle}`} />
    </div>
    <div>
      <SkeletonDataRow isFirst />
      <SkeletonDataRow />
      <SkeletonDataRow />
      <SkeletonDataRow />
      <SkeletonDataRow />
    </div>
  </div>
);

interface SkeletonAuditGridProps {
  count?: number;
}

export const SkeletonAuditGrid = ({ count = 6 }: SkeletonAuditGridProps) => (
  <>
    {Array.from({ length: count }).map((_, i) => (
      <SkeletonAuditCard key={i} />
    ))}
  </>
);

export { styles as skeletonStyles };
