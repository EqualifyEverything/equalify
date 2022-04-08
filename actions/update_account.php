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

// Get Last View
if(empty($_POST['last_view'])){
    $last_view = 'account';
}else{
    $last_view = $_POST['last_view'];
}

// Create Record
$account_records = [];

// Equalify - Page Unreachable Alert
if(!empty($_POST['page_unreachable_alert'])){
    array_push(
        $account_records,
        array(
            'key'   => 'page_unreachable_alert',
            'value' => $_POST['page_unreachable_alert']
        )
    );
};

// Equalify - Scan Frequency
if(!empty($_POST['scan_frequency'])){
    array_push(
        $account_records,
        array(
            'key'   => 'scan_frequency',
            'value' => $_POST['scan_frequency']
        )
    );
};

// Equalify - Scan Frequency
if(!empty($_POST['scan_frequency'])){
    array_push(
        $account_records,
        array(
            'key'   => 'scan_frequency',
            'value' => $_POST['scan_frequency']
        )
    );
};

// Little Forrest - WCAG 2.1 Page Error Alert
if(!empty($_POST['little_forrest_wcag_2_1_page_error_alert'])){
    array_push(
        $account_records,
        array(
            'key'   => 'little_forrest_wcag_2_1_page_error_alert',
            'value' => $_POST['little_forrest_wcag_2_1_page_error_alert']
        )
    );
};

// Wave - WCAG 2.1 Page Error Alert
if(!empty($_POST['wave_wcag_2_1_page_error_alert'])){
    array_push(
        $account_records,
        array(
            'key'   => 'wave_wcag_2_1_page_error_alert',
            'value' => $_POST['wave_wcag_2_1_page_error_alert']
        )
    );
};

// Wave - Key
if(!empty($_POST['wave_key'])){
    array_push(
        $account_records,
        array(
            'key'   => 'wave_key',
            'value' => $_POST['wave_key']
        )
    );
};

// Update Accoount
update_account($db, $account_records);

// Redirect
header('Location: ../index.php?view='.$last_view.'&status=success');
die();