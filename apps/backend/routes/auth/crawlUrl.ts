import { event } from "#src/utils";
import { LambdaClient, InvokeCommand } from "@aws-sdk/client-lambda";

const lambda = new LambdaClient();

//
// Invokes the crawler lambda to discover URLs from a site's sitemap
//

export const crawlUrl = async () => {
  const url = (event.body as any)?.url;

  if (!url) {
    return {
      statusCode: 400,
      body: JSON.stringify({ error: "url is required" }),
    };
  }

  const response = await lambda.send(
    new InvokeCommand({
      FunctionName: "aws-lambda-crawler",
      InvocationType: "RequestResponse",
      Payload: JSON.stringify({
        body: JSON.stringify({ url }),
        requestContext: { http: { method: "POST" } },
      }),
    })
  );

  const payload = JSON.parse(new TextDecoder().decode(response.Payload));
  const body = JSON.parse(payload.body);

  return {
    statusCode: payload.statusCode,
    body: JSON.stringify(body),
  };
};
