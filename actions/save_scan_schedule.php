<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's save the scan schedule!
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// We're going to use the DB in this document.
require_once '../config.php';
require_once '../models/db.php';

// We require the 'scan_schedule' parameter.
if(empty($_POST['scan_schedule']))
    throw new Exception('No scan schedule is specified');

// Now let's update the db.
DataAccess::update_meta_value(
    'scan_schedule', $_POST['scan_schedule']
);

// When done, we can checkout the saved label.
header('Location: ../index.php?view=scan&status=success');