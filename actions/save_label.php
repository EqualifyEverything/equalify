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

// If there's an ID, we'll update an existing label.
if(!empty($_GET['name'])){
    $name = $_GET['name'];
}

// Now let's create an array that we'll use to post to.
$updated_label = array();

// The array is populated with Url parameters.
if(!empty($_POST['integration'])){
    $updated_label['integration'] = $_POST['integration'];    
}else{
    throw new Exception('Integration missing');
}
if(!empty($_POST['source'])){
    $updated_label['source'] = $_POST['source'];    
}else{
    throw new Exception('Source missing');
}
if(!empty($_POST['type'])){
    $updated_label['type'] = $_POST['type'];    
}else{
    throw new Exception('Type missing');
}
if(!empty($_POST['title'])){
    $updated_label['title'] = $_POST['title'];    
}else{
    throw new Exception('Title missing');
}

// Save label data with data that MySQL understands.
$updated_label = serialize($updated_label);

// Depending on if the ID is present, we'll either save
// or update the label.
if(empty($id)){

    // No ID means we need to generate an id by counting
    // all the rows in meta 
    $meta_count = DataAccess::count_db_rows('meta');

    // Now we can create the meta.
    $fields = array(
        array(
            'name' => 'meta_name',
            'value' => 'label_'.$meta_count
        ),
        array(
            'name' => 'meta_value',
            'value' => $update_label
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
    DataAccess::update_db_rows('meta', $updated_label);

}

// When done, we can checkout the saved label.
header('Location: ../index.php?view=alerts&id='.$id);
?>