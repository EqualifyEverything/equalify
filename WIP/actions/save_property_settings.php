<?php
// Start the session
session_start();

// Include dependencies
require_once '../db.php';

try {
    // Check if property_name is set and not empty
    if (!isset($_POST['property_name']) || trim($_POST['property_name']) === '')
        throw new Exception('Property title is required');

    // Check if property_url is set and not empty
    if (!isset($_POST['property_url']) || trim($_POST['property_url']) === '') 
        throw new Exception('Sitemap URL is required');

    // Setup archive status
    if (!isset($_POST['property_archived']) || trim($_POST['property_archived']) === '') {
        $property_archived = '';
    }else{
        $property_archived = $_POST['property_archived'];
    }

    // Existing properties will be set via session
    if(!empty($_SESSION['property_id'])){
        $property_id = $_SESSION['property_id'];

        // Existing property SQL statement
        $stmt = $pdo->prepare("UPDATE properties SET property_name = :property_name, property_url = :property_url, property_archived = :property_archived WHERE property_id = :property_id");

        // Bind the parameters
        $stmt->bindParam(':property_name', $_POST['property_name'], PDO::PARAM_STR);
        $stmt->bindParam(':property_url', $_POST['property_url'], PDO::PARAM_STR);
        $stmt->bindParam(':property_archived', $property_archived, PDO::PARAM_INT);
        $stmt->bindParam(':property_id', $property_id, PDO::PARAM_INT);

    }else{

        // New property statement
        $stmt = $pdo->prepare("INSERT INTO properties (property_name, property_url, property_archived) VALUES (:property_name, :property_url, :property_archived)");

        // Bind the parameters
        $stmt->bindParam(':property_name', $_POST['property_name'], PDO::PARAM_STR);
        $stmt->bindParam(':property_url', $_POST['property_url'], PDO::PARAM_STR);
        $stmt->bindParam(':property_archived', $property_archived, PDO::PARAM_INT);

    }

    // Execute the statement
    $stmt->execute();

    // Redirect on success
    header("Location: ../index.php?view=settings&property_id=" . urlencode($property_id) . "&success=" .urlencode('"'.$_POST['property_name'].'" property updated.'));
    exit;

} catch (Exception $e) {
    // Handle any errors
    header("Location: ../index.php?view=settings&property_id=" . urlencode($property_id) . "&error=" . urlencode($e->getMessage()));

    exit;
}
?>
