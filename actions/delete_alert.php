<?php
// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Get URL parameters.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(empty($id))
    throw new Exception('ID "'.$id.'" is invalid format.');
if(!empty($_GET['site_details_redirect'])){
    $site_details_redirect = $_GET['site_details_redirect'];
}else{
    $site_details_redirect = NULL;
}

// Do the deletion.
$filters = [array(
    'name'  => 'id',
    'value' => $id
)];
DataAccess::delete_alerts($filters);

// When the work is done, we can triumphantly return to
// wherever we came from.
if(empty($site_details_redirect)){
    header('Location: ../index.php?view=alerts&status=success');
}else{
    header('Location: ../index.php?view=site_details&id='.$site_details_redirect.'&status=success');
}