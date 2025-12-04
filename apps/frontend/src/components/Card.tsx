import { ReactNode } from "react";
import styles from "./Card.module.scss";

interface CardProps extends React.PropsWithChildren {
  variant?: string;
  className?: string;
}

export const Card = ({ variant = "dark", className="", children }: CardProps) => {
  return (
    <div className={styles.card + " card " + variant + " " + styles[variant]+" "+className}>
      {children}
    </div>
  );
};
