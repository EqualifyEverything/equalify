import { ReactNode } from "react";
import styles from "./StyledLabeledInput.module.scss";

interface StyledLabledInputProps extends React.PropsWithChildren {
  variant?: string;
}

export const StyledLabeledInput = ({ variant = "stacked", children }: StyledLabledInputProps) => {
  return (
    <div className={styles.styledLabeledInput + " " + styles[variant]}>
      {children}
    </div>
  );
};
