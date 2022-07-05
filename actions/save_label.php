<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's save a label!
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// First let's create an array that we'll use to post to.
$updated_fields = array();

// The array is populated with URL parameters.
if(!empty($_POST['integration'])){
    array_push(
        $updated_fields,
        array(
            'name' => 'integration',
            'value' => $_POST['integration']
        )
    );
}
if(!empty($_POST['source'])){
    array_push(
        $updated_fields,
        array(
            'name' => 'source',
            'value' => $_POST['source']
        )
    );
}
if(!empty($_POST['type'])){
    array_push(
        $updated_fields,
        array(
            'name' => 'type',
            'value' => $_POST['type']
        )
    );
}
if(!empty($_POST['title'])){
    array_push(
        $updated_fields,
        array(
            'name' => 'title',
            'value' => $_POST['title']
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
            'value' => serialize($updated_fields)
        )
    );
    DataAccess::add_db_entry('meta', $fields);

}else{

    // Otherwise we can update the meta.
    $filtered_to_label = array(
        array(
            'name' => 'meta_name',
            'value' => $name
        )
    );
    DataAccess::update_db_rows('meta', $updated_fields, $updated_label);

    // And let's set the name with the post variable.
    $name = $_POST['name'];

}

// When done, we can checkout the saved label.
header('Location: ../index.php?view=alerts&name='.$name);
?>