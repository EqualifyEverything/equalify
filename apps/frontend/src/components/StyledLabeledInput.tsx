import { ReactNode } from "react";
import styles from "./StyledLabeledInput.module.scss";

interface StyledLabledInputProps extends React.PropsWithChildren {
  variant?: string;
  className?: string;
}

export const StyledLabeledInput = ({ variant = "stacked", className = "", children }: StyledLabledInputProps) => {
  return (
    <div className={styles.styledLabeledInput +" " + className +" " + styles[variant]}>
      {children}
    </div>
  );
};
