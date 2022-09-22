<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's save a label!
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// We're going to use the DB in this document.
require_once '../config.php';
require_once '../models/db.php';

// First let's create an array that we'll use to update 
// the meta from.
$updated_meta = array();

// The array is populated with URL parameters.
if(!empty($_POST['status'])){
    array_push(
        $updated_meta,
        array(
            'name' => 'status',
            'value' => $_POST['status']
        )
    );
}
if(!empty($_POST['type'])){
    array_push(
        $updated_meta,
        array(
            'name' => 'type',
            'value' => $_POST['type']
        )
    );
}
if(!empty($_POST['title'])){
    array_push(
        $updated_meta,
        array(
            'name' => 'title',
            'value' => strip_tags($_POST['title'])
        )
    );
}

// Depending on if the name is present, we'll either save
// or update the label.
if(empty($_POST['name'])){

    // No ID means we need to generate an id by counting
    // all the rows in meta 
    $name = 'label_'.DataAccess::count_db_rows('meta');

    // Now we can create the meta.
    $fields = array(
        array(
            'name' => 'meta_name',
            'value' => $name
        ),
        array(
            'name' => 'meta_value',
            'value' => serialize($updated_meta)
        )
    );
    DataAccess::add_db_entry('meta', $fields);

}else{

    // Otherwise we can update the fields.
    $fields = array(
        array(
            'name' => 'meta_value',
            'value' => serialize($updated_meta)
        )
    );

    // All fields are filtered to the current post.
    $filtered_to_label = array(
        array(
            'name' => 'meta_name',
            'value' => $_POST['name']
        )
    );
    DataAccess::update_db_rows(
        'meta', $fields, $filtered_to_label
    );

    // And let's set the name with the post variable.
    $name = $_POST['name'];

}

// When done, we can checkout the saved label.
header('Location: ../index.php?view=alerts&status=success&label='.$name);

/**
 * Prepare Meta.
 * @param string input
 * @param string name
 */
function submeta($input, $name){

    // Our string can't use spaces.
    str_replace(
        ' ', '', $input
    );

    // Now lets create an array.
    $items = explode(',', $input);

    // We turn the array into a subfilter.
    $subfilter = array();
    foreach($items  as $item){
        array_push(
            $subfilter,
            array(
                'name' => $name,
                'value' => $item,
                'condition' => 'OR'
            )
        );
    }

    // Let's return the subfilter.
    return $subfilter;

}
?>