<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * These configs are used to setup Equalify's database
 * and execution.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Configure database.
$GLOBALS['DB_HOST'] = 'localhost';
$GLOBALS['DB_USERNAME'] = 'root';
$GLOBALS['DB_PASSWORD'] = 'root';
$GLOBALS['DB_NAME'] = 'equalify_open_source';
$GLOBALS['DB_PORT'] = '3306';
$GLOBALS['DB_SOCKET'] = '/Applications/MAMP/tmp/mysql/mysql.sock';

// Configure PHP path, which you can find by running 
// `which php` in a terminal.
$GLOBALS['PHP_PATH'] = '/usr/local/bin/php';

// Scanner settings.
$GLOBALS['wave_key'] = 'm5WgSFUj2753';

// Additional options.
$GLOBALS['page_limit'] = '2222';
$GLOBALS['scan_concurrency'] = '6';
$GLOBALS['scan_timeout'] = '33';
$GLOBALS['service_logo'] = 'assets/equalify/equalify-logo.svg';
$GLOBALS['service_name'] = 'Equalify';