import type { APIGatewayProxyEventV2, APIGatewayProxyResultV2, Context } from 'aws-lambda'

type LambdaFunctionUrlEvent = APIGatewayProxyEventV2;
type LambdaFunctionUrlResult = APIGatewayProxyResultV2;

export async function handler(
  event: LambdaFunctionUrlEvent,
  context: Context,
): Promise<LambdaFunctionUrlResult> {
  console.log("beep boop")
  console.log(context.functionName)
  console.log(`${event.requestContext.http.method} ${event.rawPath}`)
  return {
    statusCode: 200, 
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify(event, null, 2),
  }
}