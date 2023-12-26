<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document helps us delete tags.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Delete Tags
 * @param string tags [ array('slug' => $value, 'name' => 
 * * $value, 'description' => $value) ]
 */
function delete_tags(array $tags){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/init.php');
    require_once(__ROOT__.'/models/db.php');

    // To make sure select the entries to delete, we must 
    // reformat the tags for a db filter.
    // [ array ('name' => $name, 'value' => $value, 
    // 'operator' => '=', 'condition' => 'AND' ) ]
    $filters = array();
    if(!empty($tags)){
        foreach($tags as $tag):
            array_push( 
                $filters, array(
                    'name' => 'slug',
                    'value' => $tag['slug'],
                    'condition' => 'OR'
                )
            );
        endforeach;
    }

    // Lets add tags to the tag table.
    DataAccess::delete_db_entries('tags', $filters);

}