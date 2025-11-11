import { db, event, graphqlQuery } from "#src/utils";

export const getLogs = async () => {
  const page = parseInt((event.queryStringParameters as any).page || "0", 10);
  const pageSize = parseInt(
    (event.queryStringParameters as any).pageSize || "50",
    10
  );

  const query = {
    query: `query($limit: Int!, $offset: Int!){
                logs(limit: $limit, offset: $offset, order_by: {created_at: desc}) {
                    audit_id
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
  console.log(JSON.stringify({ query }));
  const response = await graphqlQuery(query);
  console.log(response);
  return;
};
