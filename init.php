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
$dotenv->load();

if (array_key_exists('MODE', $_ENV) &&  $_ENV['MODE'] == 'managed') { // if we're in managed mode, initialize auth0
    $auth0 = new \Auth0\SDK\Auth0([
        'domain' => $_ENV['AUTH0_DOMAIN'],
        'clientId' => $_ENV['AUTH0_CLIENT_ID'],
        'clientSecret' => $_ENV['AUTH0_CLIENT_SECRET'],
        'cookieSecret' => $_ENV['AUTH0_COOKIE_SECRET']
    ]);

     $session = $auth0->getCredentials();

    if ($session === null) {
        // The user isn't logged in.
        echo '<p>Please <a href="/login">log in</a>.</p>';
        return;
    } 

}
