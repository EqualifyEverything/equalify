export const logEvent = (event) => {
    console.log(JSON.stringify({
        triggerSource: event?.triggerSource,
        sub: event?.claims?.sub ?? event?.request?.userAttributes?.sub,
        email: event?.claims?.email ?? event?.request?.userAttributes?.email,
        path: event?.rawPath ?? event?.path,
        body: event?.body,
        queryStringParameters: event?.queryStringParameters,
        httpMethod: event?.httpMethod,
    }));
}