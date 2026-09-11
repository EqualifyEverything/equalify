import { Dot } from "recharts";
import themeVariables from "../global-styles/variables.module.scss"

export const CustomizedDot = (props: any) => {
    const { cx, cy, dataKey, payload } = props;
    if (payload.timestamp) {
      // scan_timeout is the audit-level 15-minute stuck-scan error — flag it in red
      // so the user can spot a significant problem at a glance on the chart.
      const dotColor = payload.hasTimeoutError ? themeVariables.red : themeVariables.white;
      return (
        <Dot
          key={payload.timestamp}
          cx={cx}
          cy={cy}
          r={4}
          stroke={dotColor}
          fill={dotColor}
          strokeWidth={4}
        ></Dot>
      );
    } else {
      return <div key={Math.random()}></div>; //this is a hack :/
    }
  };

export const CustomizedActiveDot = (props: any) => {
  const { cx, cy, payload } = props;
  const fillColor = payload.hasTimeoutError
    ? themeVariables.red
    : payload.timestamp
      ? themeVariables.white
      : themeVariables.black;
  return (
    <Dot
      cx={cx}
      cy={cy}
      r={4}
      stroke={payload.hasTimeoutError ? themeVariables.red : themeVariables.white}
      fill={fillColor}
      strokeWidth={payload.timestamp ? 4 : 2}/*
      opacity={payload.timestamp ? 1 : 0.5} */
    />
  );
};