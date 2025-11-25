import { ReactNode } from "react";
import styles from "./Card.module.scss";

interface CardProps extends React.PropsWithChildren {
  variant?: string;
}

export const Card = ({ variant = "dark", children }: CardProps) => {
  return (
    <div className={styles.card + " card " + variant + " " + styles[variant]}>
      {children}
    </div>
  );
};
