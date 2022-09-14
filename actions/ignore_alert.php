<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document ignores an alert.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Info on DB must be declared to use db.php models.
require_once '../config.php';
require_once '../models/db.php';

// Let's get the ID that powers this action.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if(empty($id))
    throw new Exception(
        'ID "'.$id.'" is invalid or missing'
    );

// We will redirect back to a specified to a preset.
$preset = '';
if (isset($_GET['preset']))
    $preset = '&preset='.$_GET['preset'];

// Set the entry to "ignored".
$alert_arguments = array(
    array(

        // What else? You can add arrays if you
        // want.
        'name' => 'status',
        'value'=> 'ignored'

    )
);
DataAccess::update_db_entry(
    'alerts', $id, $alert_arguments
);

// If a "referrer" session was create
header('Location:  ../index.php?view=alerts'.$preset);