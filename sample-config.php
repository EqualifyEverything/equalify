<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * These configs are used to setup Equalify's database
 * and execution.
 * 
 * By default, configuration that works for ddev is added.
 * Find out more about setup up Equalify on ddev here:
 * https://github.com/bbertucc/equalify/issues/40
**********************************************************/

// Configure database.
$GLOBALS['DB_HOST'] = 'ddev-equalify-db';
$GLOBALS['DB_USERNAME'] = 'root';
$GLOBALS['DB_PASSWORD'] = 'root';
$GLOBALS['DB_NAME'] = 'equalify';
$GLOBALS['DB_PORT'] = '3306';
$GLOBALS['DB_SOCKET'] = '/var/run/mysqld/mysqld.sock';

// Configure PHP path, which you can find by running 
//`which php` in a terminal.
$GLOBALS['PHP_PATH'] = '/usr/bin/php';

// Visit https://wave.webaim.org/api/ to get a WAVE key.
$GLOBALS['wave_key'] = '';

// Visit https://github.com/bbertucc/equalify-automated_scan for more info.
$GLOBALS['axe_uri'] = '';

// Additional options.
$GLOBALS['page_limit'] = '2222';
$GLOBALS['scan_concurrency'] = '6';
$GLOBALS['scan_timeout'] = '33';
