<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document includes a function to help users add 
 * alerts.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Update Scan Log
 * @param string message
 */
function update_scan_log($message){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // Let's add the message to the db's scan log.
    DataAccess::update_meta_value( 'scan_log', $message, 
        $concatenate = true
    );

    // We return a message for CLI users.
    echo "$message";

}