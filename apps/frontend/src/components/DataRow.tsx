import styles from "./DataRow.module.scss";

interface DataRowProps extends React.PropsWithChildren {
  variant?: string;
  the_key: string;
  the_value:string;
}

export const DataRow = ({ variant = "light", the_key, the_value }:DataRowProps) => {
    return (
        <div className={styles.dataRow +" dataRow "+ styles[variant]}>
            <div className={styles["key"]}>{the_key}</div>
            <div className={styles["value"]}>{the_value}</div>
        </div>
    )}