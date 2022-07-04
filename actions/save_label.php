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
if(!empty($_GET['id'])){
    $id = $_GET['id'];
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
if(!empty($_POST['name'])){
    $updated_label['name'] = $_POST['name'];    
}else{
    throw new Exception('Name missing');
}

// Save label data with data that MySQL understands.
$updated_label = serialize($updated_label);

// Depending on if the ID is present, we'll either save or
// update the label.
if(empty($id)){

    // No ID means we need to save the label.
    $id = DataAccess::add_label($updated_label);

}else{

    // Otherwise we can update the meta.
    DataAccess::update_meta('label_'.$id, $updated_label);

}

// When done, we can checkout the saved label.
header('Location: ../index.php?view=alerts&id='.$id);
?>