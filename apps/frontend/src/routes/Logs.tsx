import { useQuery } from "@tanstack/react-query";
import * as API from "aws-amplify/api";


export const Logs = () => {

  const { data: logs } = useQuery({
    queryKey: ["logs"],
    queryFn: async () => {
      return await (
        await API.get({
          apiName: "auth",
          path: "/getLogs",
          options: { queryParams: { page: "1", pageSize: "10" } },
        }).response
      ).body.json();
    },
    refetchInterval: 5000,
  });

  console.log(logs);

  return (
    <>
      <h1 className="initial-focus-element">Logs</h1>
    </>
  );
};
