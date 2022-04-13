<?php
// Add DB Info
require_once '../config.php';
require_once '../models/db.php';
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);

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
    array_push(
        $account_records,
        array(
            'key'   => 'wave_key',
            'value' => $_POST['wave_key']
        )
    );
};

// Time to update meta and redirect!
update_meta($db, $account_records);
header('Location: ../index.php?view='.$last_view.'&status=success');