<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document includes a function to help users add 
 * alerts.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Register Tags
 * @param string tags [ array('slug' => $value, 'name' => 
 * * $value, 'description' => $value) ]
 */
function register_tags($tags){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // Let's prepare the tags for the db.
    $data = array();
    if(!empty($tags)){
        foreach($tags as $tag):
            array_push( 
                $data, array(
                    'slug' => $tag['slug'],
                    'name' => $tag['name'],
                    'description' => $tag['description']
                )
            );
        endforeach;
    }

    // Lets add tags to the tag table.
    DataAccess::add_db_rows('tags', $data);

}