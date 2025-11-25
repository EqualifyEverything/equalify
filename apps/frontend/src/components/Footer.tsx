import { FaGithub, FaNewspaper } from "react-icons/fa";
import styles from "./Footer.module.scss";
import { Link } from "react-router-dom";
import * as Separator from "@radix-ui/react-separator";

export const Footer = () => {

  return (

      <div className={styles.footer}>
          <Link to="https://it.uic.edu/accessibility/engineering">
          Subscribe to our newsletter <FaNewspaper className="icon-small"/>
          </Link>
          <Separator.Root orientation="vertical"/>
          <Link to="https://github.com/equalifyEverything/equalify">
           Star or contribute on GitHub
           <FaGithub className="icon-small"/>
           </Link>
      </div>
  );
};
