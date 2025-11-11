import * as API from "aws-amplify/api";
const apiClient = API.generateClient();

export const createLog = async (
  message: string = "",
  auditId: string | null = null,
  data: Object = {}
) => {
  await apiClient.graphql({
    query: `mutation ($audit_id: uuid, $message: String, $data: jsonb) {
                insert_logs_one(object: {audit_id: $audit_id, message: $message, data: $data}) {id}
            }`,
    variables: {
      audit_id: auditId,
      message: message,
      data: data,
    },
  });
};
