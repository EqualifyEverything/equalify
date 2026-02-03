import styles from "./Skeleton.module.scss";

interface SkeletonProps {
  width?: string | number;
  height?: string | number;
  variant?: "text" | "title" | "circle" | "rounded" | "rect";
  className?: string;
  style?: React.CSSProperties;
}

/**
 * Base skeleton component
 */
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

/**
 * Inline text skeleton - great for replacing text content
 */
export const SkeletonText = ({ 
  width = "100%", 
  lines = 1 
}: { 
  width?: string | number; 
  lines?: number;
}) => (
  <div className={styles.skeletonTextGroup} aria-hidden="true">
    {Array.from({ length: lines }).map((_, i) => (
      <Skeleton 
        key={i} 
        variant="text" 
        width={i === lines - 1 && lines > 1 ? "70%" : width} 
      />
    ))}
  </div>
);

/**
 * Skeleton for data rows (key-value pairs)
 */
export const SkeletonDataRow = ({ isFirst = false }: { isFirst?: boolean }) => (
  <div
    className={styles.skeletonDataRow}
    style={isFirst ? { borderBottom: "2px solid var(--gray, #e0dcd0)" } : {}}
  >
    <Skeleton className={styles.skeletonKey} />
    <Skeleton className={styles.skeletonValue} />
  </div>
);

/**
 * Skeleton that matches Audit Card layout
 */
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

/**
 * Grid of skeleton audit cards
 */
export const SkeletonAuditGrid = ({ count = 6 }: { count?: number }) => (
  <>
    {Array.from({ length: count }).map((_, i) => (
      <SkeletonAuditCard key={i} />
    ))}
  </>
);

/**
 * Table row skeleton
 */
export const SkeletonTableRow = ({ columns = 4 }: { columns?: number }) => (
  <tr className={styles.skeletonTableRow}>
    {Array.from({ length: columns }).map((_, i) => (
      <td key={i}>
        <Skeleton height="1em" width={i === 0 ? "60%" : i === columns - 1 ? "80px" : "80%"} />
      </td>
    ))}
  </tr>
);

/**
 * Full table skeleton with header and rows
 */
export const SkeletonTable = ({ 
  columns = 4, 
  rows = 5,
  headers = [],
}: { 
  columns?: number; 
  rows?: number;
  headers?: string[];
}) => (
  <div className="table-container skeleton" role="status" aria-label="Loading table data">
    <table className="w-full border-collapse border border-gray-300" style={{width: '100%'}} aria-hidden="true">
      <thead>
        <tr className="bg-gray-100">
          {(headers.length > 0 ? headers : Array.from({ length: columns })).map((header, i) => (
            <th key={i} className="border border-gray-300 px-4 py-2 text-left font-semibold">
              {typeof header === "string" ? header : <Skeleton height="1em" width="60%" />}
            </th>
          ))}
        </tr>
      </thead>
      <tbody>
        {Array.from({ length: rows }).map((_, i) => (
          <SkeletonTableRow key={i} columns={headers.length || columns} />
        ))}
      </tbody>
    </table>
  </div>
);

/**
 * Account page skeleton
 */
export const SkeletonAccount = () => (
  <div style={{ marginBottom: '1rem' }} aria-hidden="true">
    <p><strong>Name:</strong> <Skeleton width={150} height="1em" style={{ display: "inline-block", verticalAlign: "middle" }} /></p>
    <p><strong>Email:</strong> <Skeleton width={200} height="1em" style={{ display: "inline-block", verticalAlign: "middle" }} /></p>
  </div>
);

/**
 * Blockers table skeleton with realistic column widths
 */
export const SkeletonBlockersTable = ({ rows = 5 }: { rows?: number }) => (
  <div className="table-container skeleton" role="status" aria-label="Loading blockers">
    <table className="w-full border-collapse border border-gray-300" aria-hidden="true">
      <thead>
        <tr className="bg-gray-100">
          <th className="border border-gray-300 px-4 py-2 text-left font-semibold" style={{ width: "60px" }}>Type</th>
          <th className="border border-gray-300 px-4 py-2 text-left font-semibold">URL</th>
          <th className="border border-gray-300 px-4 py-2 text-left font-semibold">Issue</th>
          <th className="border border-gray-300 px-4 py-2 text-left font-semibold" style={{ width: "100px" }}>Code</th>
          <th className="border border-gray-300 px-4 py-2 text-left font-semibold" style={{ width: "120px" }}>Tags</th>
          <th className="border border-gray-300 px-4 py-2 text-left font-semibold" style={{ width: "120px" }}>Category</th>
        </tr>
      </thead>
      <tbody>
        {Array.from({ length: rows }).map((_, i) => (
          <tr key={i} className={i % 2 === 0 ? "bg-white" : "bg-gray-50"}>
            <td className="border border-gray-300 px-4 py-2">
              <Skeleton width={24} height={24} variant="rounded" />
            </td>
            <td className="border border-gray-300 px-4 py-2">
              <Skeleton height="1em" width="90%" />
            </td>
            <td className="border border-gray-300 px-4 py-2">
              <Skeleton height="1em" width="85%" />
              <Skeleton height="0.8em" width="100px" style={{ marginTop: "4px" }} />
            </td>
            <td className="border border-gray-300 px-4 py-2">
              <Skeleton height="1.5em" width="80px" variant="rounded" />
            </td>
            <td className="border border-gray-300 px-4 py-2">
              <Skeleton height="1.5em" width="80px" variant="rounded" />
            </td>
            <td className="border border-gray-300 px-4 py-2">
              <Skeleton height="1.5em" width="90px" variant="rounded" />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
);

/**
 * Audit header skeleton
 */
export const SkeletonAuditHeader = () => (
  <div style={{ marginBottom: "16px", display: "flex", justifyContent: "space-between", alignItems: "flex-end" }} aria-hidden="true">
    <div style={{ display: "flex", alignItems: "flex-end", gap: "8px" }}>
      <div>
        <Skeleton width={40} height="0.8em" style={{ marginBottom: "4px" }} />
        <Skeleton width={200} height="1.5em" />
      </div>
    </div>
    <div style={{ display: "flex", gap: "16px" }}>
      <Skeleton width={100} height={40} variant="rounded" />
      <Skeleton width={80} height={40} variant="rounded" />
    </div>
  </div>
);

/**
 * Chart skeleton for audit page
 */
export const SkeletonChart = () => (
  <div style={{ padding: "24px", background: "#171717", borderRadius: "8px" }} aria-hidden="true">
    <div style={{ display: "flex", justifyContent: "space-between", marginBottom: "16px" }}>
      <div>
        <Skeleton width={180} height="1.25em" style={{ marginBottom: "8px", background: "rgba(255,255,255,0.1)" }} />
        <Skeleton width={80} height="0.8em" style={{ background: "rgba(255,255,255,0.1)" }} />
      </div>
      <Skeleton width={100} height={32} variant="rounded" style={{ background: "rgba(255,255,255,0.1)" }} />
    </div>
    <Skeleton width="100%" height={150} style={{ background: "rgba(255,255,255,0.05)" }} />
  </div>
);

export { styles as skeletonStyles };
