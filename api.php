<?php
// Set content type
header('Content-Type: application/json');

// Need to run init because we're outside index scope
require_once('init.php');

// Add helper functions
require_once('helpers/get_messages.php');
require_once('helpers/get_tags.php');
require_once('helpers/get_properties.php');


// Require content parameter
if( !isset($_GET['content']) || empty($_GET['content'])){
    http_response_code(400);
    echo json_encode(['error' => 'Content URL variable is required']);
    exit;
}
$content = $_GET['content'];

// Construct Function
$get_function = "get_" . $content;

// Validate Function
if (!function_exists($get_function)){
    http_response_code(400);
    echo json_encode(['error' => 'Function name '.$get_function.' does not exist.']);
    exit;
}

// Prepare the final structure
$output = $get_function();

// Return as JSON
echo json_encode($output);
?>