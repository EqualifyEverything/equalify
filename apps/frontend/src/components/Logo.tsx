import { Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import styles from "./Logo.module.scss";

const GRAPHQL_URL = import.meta.env.VITE_GRAPHQL_URL + "/v1/graphql";

const fetchCobranding = async () => {
  const res = await fetch(GRAPHQL_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      query: `query {
        options(where: { key: { _in: ["cobranding_logo_url", "cobranding_logo_alt_text", "cobranding_logo_size"] } }) {
          key
          value
        }
      }`,
    }),
  });
  const json = await res.json();
  const rows: { key: string; value: string }[] = json?.data?.options ?? [];
  return Object.fromEntries(rows.map(({ key, value }) => [key, value]));
};

export const Logo = () => {
  const { data } = useQuery({
    queryKey: ["cobranding"],
    queryFn: fetchCobranding,
    staleTime: 5 * 60 * 1000,
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
