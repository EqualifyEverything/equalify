import { ReactNode } from "react";
import styles from "./DataRow.module.scss";
import React from "react";

interface DataRowProps extends React.PropsWithChildren {
  variant?: string;
  the_key: string | ReactNode;
  the_value:string | ReactNode;
  className?: string;
  asTableRow?: boolean;
}

export const DataRow = ({ variant = "light", the_key, the_value, className="", asTableRow=false }:DataRowProps) => {
    const cellRole = asTableRow ? (variant === "highlight" ? "columnheader" : "cell") : undefined;
    return (
        <div role={asTableRow ? "row" : undefined} className={styles.dataRow +" dataRow "+ styles[variant] + " "+className}>
            <div role={cellRole} className={styles["key"] + " key"}>{the_key}</div>
            <div role={cellRole} className={styles["value"]+ " value"}>{the_value}</div>
        </div>
    )}