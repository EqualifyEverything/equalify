<?php
// Have the SDK complete the authentication flow:
$auth0->exchange(ROUTE_URL_CALLBACK);

// Finally, redirect our end user back to the / index route, to display their user profile:
header("Location: " . ROUTE_URL_INDEX);
exit;

?>