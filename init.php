<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * These configs are used to setup Equalify's database
 * and execution.
 * 
 * By default, configuration that works for ddev is added.
 * Find out more about setup up Equalify on ddev here:
 * https://github.com/bbertucc/equalify/issues/40
 **********************************************************/
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '.env');
$dotenv->safeLoad(); // Safeload so we don't get an error when there's no .env file on production

$managed_mode = false;
if (array_key_exists('MODE', $_ENV) &&  $_ENV['MODE'] == 'managed') { 
    $managed_mode = true;
};

if($managed_mode){ // if we're in managed mode, initialize auth0

    define('ROUTE_URL_INDEX', rtrim($_ENV['AUTH0_BASE_URL'], '/'));
    define('ROUTE_URL_LOGIN', ROUTE_URL_INDEX . '/?view=login');
    define('ROUTE_URL_CALLBACK', ROUTE_URL_INDEX . '/?view=auth_callback');
    define('ROUTE_URL_LOGOUT', ROUTE_URL_INDEX . '/?view=logout');

    $auth0 = new \Auth0\SDK\Auth0([
        'domain' => $_ENV['AUTH0_DOMAIN'],
        'clientId' => $_ENV['AUTH0_CLIENT_ID'],
        'clientSecret' => $_ENV['AUTH0_CLIENT_SECRET'],
        'cookieSecret' => $_ENV['AUTH0_COOKIE_SECRET']
    ]);

    $session = $auth0->getCredentials();

    if ($session === null) {  // The user isn't logged in.      
        echo '<p>Please <a href="/?view=login">log in</a>.</p>';
    } else {
        echo '<pre>';
        print_r($session->user);
        echo '</pre>';
      
        echo '<p>You can now <a href="/?view=logout">log out</a>.</p>';
    }
}
