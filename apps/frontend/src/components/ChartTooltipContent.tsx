import styles from "./ChartTooltipContent.module.scss"

interface CustomTooltipProps {
  active?: boolean; // Optional, as it might be undefined when not active
  payload?: Array<any>; // Or a more specific type if your data structure is known
  label?: string | number; // Or a more specific type based on your label data
}

export const ChartTooltipContent = ({
  active,
  payload,
  label,
}: CustomTooltipProps) => {
  if (active && payload && payload.length) {
    const date = label
      ? new Date(label).toLocaleDateString("en-US", {
          weekday: "short",
          year: "numeric",
          month: "short",
          day: "numeric",
        })
      : null;
    let scannedTime = "";
    if (payload.length > 0 && payload[0].payload?.timestamp) {
      const scanTimeDate = new Date(payload[0].payload?.timestamp);
      scannedTime =
        "Scanned at " +
        scanTimeDate.toLocaleTimeString(navigator.language, {
          hour: "2-digit",
          minute: "2-digit",
        });
    }
    return (
      <div
        className={styles.tooltip}
      >
          <div className="block">{date}</div>
          <div className="block">
            Blockers: <b>{payload[0].payload.blockers}</b>
          </div>
          {scannedTime && (
            <div className="block">
              <b>{scannedTime}</b>
            </div>
          )}
      </div>
    );
  }

  return null;
};
