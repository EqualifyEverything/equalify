import { Dot } from "recharts";
import themeVariables from "../global-styles/variables.module.scss"

export const CustomizedDot = (props: any) => {
    const { cx, cy, dataKey, payload } = props;
    if (payload.timestamp) {
      return (
        <Dot
          key={payload.timestamp}
          cx={cx}
          cy={cy}
          r={4}
          stroke={themeVariables.white}
          fill={themeVariables.white}
          strokeWidth={4}
        ></Dot>
      );
    } else {
      return <div key={Math.random()}></div>; //this is a hack :/
    }
  };

export const CustomizedActiveDot = (props: any) => {
  const { cx, cy, payload } = props;
  return (
    <Dot
      cx={cx}
      cy={cy}
      r={4}
      stroke={themeVariables.white}
      fill={payload.timestamp ? themeVariables.white : themeVariables.black}
      strokeWidth={payload.timestamp ? 4 : 2}/* 
      opacity={payload.timestamp ? 1 : 0.5} */
    />
  );
};