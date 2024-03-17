<?php
// Add initialization info
require_once('../init.php');

// Helpers
require_once '../helpers/get_property.php';

try {
    // Check if property_name is set and not empty
    if (!isset($_POST['property_name']) || trim($_POST['property_name']) === '')
        throw new Exception('Property title is required');

    // Check if property_url is set and not empty
    if (!isset($_POST['property_url']) || trim($_POST['property_url']) === '') 
        throw new Exception('Property URL is required');

    // Check if property_discovery is set and not empty
    if (!isset($_POST['property_discovery']) || trim($_POST['property_discovery']) === '') 
    throw new Exception('Property Discovery settings are required');

    // Setup archive status
    if (!isset($_POST['property_archived']) || trim($_POST['property_archived']) === '') {
        $property_archived = '';
    }else{
        $property_archived = $_POST['property_archived'];
    }

    // Existing properties are set via session
    $property_id = $_SESSION['property_id'];

    // If URL is updated, force processing.
    $existing_property_url = get_property($property_id)['property_url'];
    if($existing_property_url !== $_POST['property_url']){
        // We can force processing by removing the timestamp
        $property_processed = NULL;
    }else{
        // Keep the timestamp for old properties
        $property_processed = get_property($property_id)['property_processed'];
    }

    // Existing property SQL statement
    $stmt = $pdo->prepare("UPDATE properties SET property_name = :property_name, property_url = :property_url, property_discovery = :property_discovery, property_archived = :property_archived, property_processed = :property_processed WHERE property_id = :property_id");

    // Bind the parameters
    $stmt->bindParam(':property_name', $_POST['property_name'], PDO::PARAM_STR);
    $stmt->bindParam(':property_url', $_POST['property_url'], PDO::PARAM_STR);
    $stmt->bindParam(':property_discovery', $_POST['property_discovery'], PDO::PARAM_STR);
    $stmt->bindParam(':property_archived', $property_archived, PDO::PARAM_INT);
    $stmt->bindParam(':property_processed', $property_processed, PDO::PARAM_STR);
    $stmt->bindParam(':property_id', $property_id, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Remove session token to prevent unintended submissions.
    $_SESSION['property_id'] = '';

    // Redirect on success
    $_SESSION['success'] = '"'.$_POST['property_name'].'" property saved.';
    header("Location: ../index.php?view=property_settings&property_id=$property_id");
    exit;

} catch (Exception $e) {

    // Remove session token to prevent unintended submissions.
    $_SESSION['property_id'] = '';

    // Handle any errors
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../index.php?view=property_settings&property_id=$property_id");

    exit;
}
?>
