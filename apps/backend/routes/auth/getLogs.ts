import { db, event, graphqlQuery } from "#src/utils";

interface getLogsResponseLog {
    created_at: string,
    data: Object,
    message: string,
    LogToUser: { name: string, email: string },
    LogToAudit: { name: string } | null
}

interface getLogsResponse {
    logs: getLogsResponseLog[];
}

export const getLogs = async () => {
  const page = parseInt((event.queryStringParameters as any).page || "0", 10);
  const pageSize = parseInt(
    (event.queryStringParameters as any).pageSize || "50",
    10
  );

  const query = {
    query: `query($limit: Int!, $offset: Int!){
                logs(limit: $limit, offset: $offset, order_by: {created_at: desc}) {
                    created_at
                    data
                    message
                    LogToUser {
                        name
                        email
                    }
                    LogToAudit {
                        name
                    }
                }
            }`,
    variables: {
      limit: pageSize,
      offset: page * pageSize,
    },
  };
  const response = await graphqlQuery(query) as getLogsResponse;
  console.log(JSON.stringify(response));
  return response;
};
