import { cognitoRouter, publicRouter, authRouter, internalRouter, scheduledRouter, hasuraRouter } from "#src/routes";
import { setEvent } from "#src/utils";

export const handler = async (rawEvent) => {
  const event = setEvent(rawEvent);

  if (event.triggerSource) {
    return cognitoRouter();
  }
  else if (event.path.startsWith("/public")) {
    return publicRouter();
  }
  else if (event.path.startsWith("/auth")) {
    return authRouter();
  }
  else if (event.path.startsWith("/internal")) {
    return internalRouter();
  }
  else if (event.path.startsWith("/scheduled")) {
    return scheduledRouter();
  }
  else if (event.path.startsWith("/hasura")) {
    return hasuraRouter();
  }
};
