import { Link } from "react-router-dom";
import styles from "./Logo.module.scss";

export const Logo = () => {
  return (
    <div className={styles.logo}>
      <Link to="/">
        <img className={styles.logo} src="/logo.svg" />
      </Link>
    </div>
  );
};
