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

// Set Variables and fallbacks
$site_unreachable_alert = $_POST['site_unreachable_alert'];
if(!is_numeric($site_unreachable_alert)){
    throw new Exception('"Site Unreachable" alert options are improperly specified.');
}
$wcag_2_1_page_error_alert = $_POST['wcag_2_1_page_error_alert'];
if(!is_numeric($wcag_2_1_page_error_alert)){
    throw new Exception('"WCAG 2.1 Page Error" alert options are improperly specified.');
}
$email_site_owner = $_POST['email_site_owner'];
if(!is_numeric($wcag_2_1_page_error_alert)){
    throw new Exception('"Email Site Owner" enforcement options are improperly specified.');
}
$scan_frequency = $_POST['scan_frequency'];
if(empty($scan_frequency)){
    throw new Exception('No testing frequency is specified.');
}
$accessibility_testing_service = $_POST['accessibility_testing_service'];
if(empty($accessibility_testing_service)){
    throw new Exception('No accessibility testing service is specified.');
}
$wave_key = $_POST['wave_key'];
if(empty($wave_key)){
    throw new Exception('Wave Key is missing.');
}

// Create Record
$record = [
    'site_unreachable_alert' => $site_unreachable_alert,
    'wcag_2_1_page_error_alert' => $wcag_2_1_page_error_alert,
    'email_site_owner' => $email_site_owner,
    'scan_frequency' => $scan_frequency,
    'accessibility_testing_service' => $accessibility_testing_service,
    'wave_key' => $wave_key
];

update_account($db, $record);

// Redirect
header("Location: ../index.php?view=account&status=success");
die();