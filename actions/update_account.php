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

// Set URL Variables and fallbacks
$wave_key = $_GET ['wave_key'];
if(empty($wave_key)){
    echo 'WAVE Key is missing.';
    die;
}
$accessibility_testing_service = $_GET["accessibility_testing_service"];
if(empty($accessibility_testing_service)){
    echo 'Accessibility Testing Service is missing.';
    die;
}

// Create Record
$record = [
    'wave_key' => $wave_key,
    'accessibility_testing_service' => $accessibility_testing_service
];

update_account($db, $record);

// Redirect
header("Location: ../index.php?view=account&status=success");
die();