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
$GLOBALS['DB_NAME'] = 'equalify';
$GLOBALS['DB_PORT'] = '3306';

// Configure PHP path, which you can find by running 
// `which php` in a terminal.
$GLOBALS['PHP_PATH'] = '/usr/local/bin/php';

// Scanner settings.
$GLOBALS['wave_key'] = '';

// Additional options.
$GLOBALS['page_limit'] = '111';
$GLOBALS['scan_concurrency'] = '6';
$GLOBALS['scan_timeout'] = '33';