<?php
// These configs are used to setup Equalify's database and execution.
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '.env');
$dotenv->safeLoad();

$GLOBALS["managed_mode"] = false;
if (array_key_exists('MODE', $_ENV) &&  $_ENV['MODE'] == 'managed') { 
    $GLOBALS["managed_mode"] = true;
};

if($GLOBALS["managed_mode"]){ // if we're in managed mode, initialize auth0

    define('ROUTE_URL_INDEX', rtrim($_ENV['AUTH0_BASE_URL'], '/'));
    define('ROUTE_URL_LOGIN', ROUTE_URL_INDEX . '/?auth=login');
    define('ROUTE_URL_CALLBACK', ROUTE_URL_INDEX . '/?auth=auth_callback');
    define('ROUTE_URL_LOGOUT', ROUTE_URL_INDEX . '/?auth=logout');

    $auth0 = new \Auth0\SDK\Auth0([
        'domain' => $_ENV['AUTH0_DOMAIN'],
        'clientId' => $_ENV['AUTH0_CLIENT_ID'],
        'clientSecret' => $_ENV['AUTH0_CLIENT_SECRET'],
        'cookieSecret' => $_ENV['AUTH0_COOKIE_SECRET']
    ]);

    $session = $auth0->getCredentials();

    if (!empty($_GET['auth'])){ // Router for auth endpoints
        require_once 'auth/'.$_GET['auth'].'.php';
    }
    
    if ($session === null) {  // The user isn't logged in.      
        //echo '<p>Please <a href="/?auth=login">log in</a>.</p>';
        require_once 'auth/login.php';
    } else {
        
        $GLOBALS["ACTIVE_DB"] = $session->user['equalify_databases'][0]; // TODO: currently just takes first from DBs array, should be switchable
        $user_title = $session->user['title'];
        $user_name =  $session->user['name'];
        $user_email =  $session->user['email'];
        $user_nickname = $session->user['nickname'];
        $user_picture = $session->user['picture'];
        $user_last_updated = $session->user['updated_at'];
        
        echo '<!--';
        print_r($session->user);
        echo '-->';
     
        /* echo '<p>You can now <a href="/?auth=logout">log out</a>.</p>';  */
    }

}

// Database connection
$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USERNAME'];
$db_pass = $_ENV['DB_PASSWORD']; 

$pdo = new PDO("mysql:host=$db_host;dbname=$db_name", "$db_user", "$db_pass");

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

