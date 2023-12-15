<?php

    $auth0->clear();

    // Finally, set up the local application session, and redirect the user to the Auth0 Universal Login Page to authenticate.
    header( "Location: " . $auth0->login( ROUTE_URL_CALLBACK ) );
    exit;

?>
