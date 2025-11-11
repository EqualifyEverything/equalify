import { db, event, graphqlQuery } from "#src/utils";
import getLogsResponse from "../../../../shared/types/logs"

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
                logs_aggregate {
                    aggregate {
                        count
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
