<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';

// We need the last view, so we can redirect on success.
if(!empty($_POST['last_view'])){
    $last_view = $_POST['last_view'];
}else{

    // If no view is specified, we'll return to Sites.
    $last_view = 'sites';

}

// TODO: update logic, so the $_POST parameters are
// set in the integrations file. See Github Issue #12.
$account_records = [];
if(!empty($_POST['wave_key'])){
    DataAccess::update_meta_value('wave_key', $_POST['wave_key']);
};

header('Location: ../index.php?view='.$last_view.'&status=success');