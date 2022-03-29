<?php
// Get URL parameters.
$integration = $_GET['integration'];
if(empty($integration))
    throw new Exception('Integration is not specfied');
$old_status = $_GET['old_status'];
if(empty($old_status))
    throw new Exception('Status is not specfied for integration "'.$integration.'"');

// Integration file is going to be updated.
$integration_path = '../integrations/'.$integration.'/functions.php';
$integration_contents = file_get_contents($integration_path);

// Set new status to oppostite
if($old_status == 'Active'){
    $new_status = 'Disabled';
}elseif($old_status == 'Disabled'){
    $new_status = 'Active';
}else{
    throw new Exception('The status,"'.$old_status.'," is not allowed');
}

// Replace something in the file string.
$new_contents = str_replace($old_status, $new_status, $integration_contents);

// Cool kids don't use DBs for integration data.
file_put_contents($integration_path, $new_contents);

// Redirect
header('Location: ../index.php?view=integrations&id='.$id.'&status=success');