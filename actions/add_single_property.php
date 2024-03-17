<?php
// Require helpers and DB
require_once '../init.php';
require_once '../helpers/validate_url.php';

// Return content as JSON
header('Content-Type: application/json');

// Initialize an array to hold the single property
$property = [];

// Check if the request is a POST request and the required fields are present
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['propertyName']) && isset($_POST['propertyUrl']) && isset($_POST['discoveryProcess'])) {
    // Assign form data to variables
    $propertyName = $_POST['propertyName'];
    $propertyUrl = $_POST['propertyUrl'];
    $discoveryType = $_POST['discoveryProcess'];

    // Validate URL
    $validationResult = validateUrl($propertyUrl, $discoveryType, $pdo);

    if ($validationResult['valid']) {
        // The URL is valid, attempt to insert the property into the database
        try {
            $query = $pdo->prepare("INSERT INTO properties (property_name, property_url, property_discovery) VALUES (:name, :url, :discovery)");
            $query->execute([
                ':name' => $propertyName,
                ':url' => $propertyUrl,
                ':discovery' => $discoveryType,
            ]);

            // Prepare success response
            $response = [
                "success" => [$propertyName],
                "message" => "Property successfully added.",
            ];
        } catch (PDOException $e) {
            // Handle potential database errors gracefully
            $response = [
                "success" => false,
                "error" => "Failed to add property to the database.",
                "log" => $e->getMessage(),
            ];
        }
    } else {
        // URL validation failed, prepare error response
        $response = [
            "success" => false,
            "error" => $validationResult['log'],
        ];
    }
} else {
    // Missing required fields, prepare error response
    $response = [
        "success" => [$propertyName],
        "error" => "All fields are required.",
    ];
}

echo json_encode($response);
?>
