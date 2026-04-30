import { Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";
import styles from "./Logo.module.scss";

const apiClient = API.generateClient();

export const Logo = () => {
  const { data } = useQuery({
    queryKey: ["cobranding"],
    queryFn: async () => {
      const response = await apiClient.graphql({
        query: `query {
          options(where: { key: { _in: ["cobranding_logo_url", "cobranding_logo_alt_text", "cobranding_logo_size"] } }) {
            key
            value
          }
        }`,
      });
      const rows: { key: string; value: string }[] = (response as any)?.data?.options ?? [];
      return Object.fromEntries(rows.map(({ key, value }) => [key, value]));
    },
  });

  const cobrandUrl = data?.cobranding_logo_url;
  const cobrandAlt = data?.cobranding_logo_alt_text ?? "";
  const cobrandSize = Number(data?.cobranding_logo_size) || 150;

  return (
    <div className={styles.logo}>
      {cobrandUrl && (
        <img
          src={cobrandUrl}
          alt={cobrandAlt}
          style={{ maxWidth: cobrandSize }}
          className={styles.cobrandLogo}
        />
      )}
      <Link to="/">
        <img className={styles.logoImg} src="/logo.svg" alt="Equalify" />
      </Link>
    </div>
  );
};
