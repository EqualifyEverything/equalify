import { ReactNode } from "react";
import styles from "./DataRow.module.scss";
import React from "react";

interface DataRowProps extends React.PropsWithChildren {
  variant?: string;
  the_key: string;
  the_value:string | ReactNode;
  className?: string;
}

export const DataRow = ({ variant = "light", the_key, the_value, className="" }:DataRowProps) => {
    return (
        <div className={styles.dataRow +" dataRow "+ styles[variant] + " "+className}>
            <div className={styles["key"]}>{the_key}</div>
            <div className={styles["value"]}>{the_value}</div>
        </div>
    )}