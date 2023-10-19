<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document includes a function to help users add 
 * notices.
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
function register_tags(array $tags){

    // We don't know where helpers are being called, so we
    // have to set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');

    // To make sure all the slugs are unique, we need to 
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

    // Now let's see if any of those slugs exist.
    $existing_tags = DataAccess::get_db_rows(
        'tags', $filters 
    );

    // We can't go on if a slug already exists.
    if(!empty($existing_tags['content']))
        throw new Exception(
            "A tag's slug is already use. All slugs must be unique"
        );

    // Let's prepare the tags to be added to the db.
    $data = array();
    if(!empty($tags)){
        foreach($tags as $tag):
            array_push( 
                $data, array(
                    'slug' => $tag['slug'],
                    'title' => $tag['title'],
                    'description' => $tag['description'],
                    'category' => $tag['category']
                )
            );
        endforeach;
    }

    // Lets add tags to the tag table.
    DataAccess::add_db_rows('tags', $data);

}