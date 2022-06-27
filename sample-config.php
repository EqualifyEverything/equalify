<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * These configs are used to setup Equalify's database
 * and execution.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Configure database.
$GLOBALS['DB_HOST'] = 'localhost';
$GLOBALS['DB_USERNAME'] = 'root';
$GLOBALS['DB_PASSWORD'] = 'root';
$GLOBALS['DB_NAME'] = 'equalify';
$GLOBALS['DB_PORT'] = '3306';
$GLOBALS['DB_SOCKET'] = '/Applications/MAMP/tmp/mysql/mysql.sock';

// Configure PHP path, which you can find by running 
// `which php` in a terminal.
$GLOBALS['PHP_PATH'] = '/usr/local/bin/php';