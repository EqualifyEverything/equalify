<?php
// Require helpers and DB
require_once '../init.php';
require_once '../helpers/validate_url.php';

// Initialize arrays to hold properties and validation results
$properties = [];
$validationResults = [];

// Return content as JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == 0) {
        // Handle CSV file upload
        $file = $_FILES['csvFile']['tmp_name'];
        $handle = fopen($file, "r");
        $header = fgetcsv($handle); // Assuming the first row contains column headers

        // Validate column names
        if ($header !== ['Property Name', 'Property URL', 'Discovery Type']) {
            echo json_encode(["error" => "Invalid CSV format"]);
            exit;
        }

        while ($row = fgetcsv($handle)) {
            $properties[] = [
                "property_name" => $row[0],
                "property_url" => $row[1],
                "discovery_type" => strtolower($row[2]),
            ];
        }
        fclose($handle);

        // First, validate all properties without saving to the database
        foreach ($properties as $property) {
            $validationResult = validateUrl($property['property_url'], $property['discovery_type'], $pdo);
            $validationResult['property_name'] = $property['property_name']; // Include property name in validation result for reference
            $validationResults[] = $validationResult;
        }

        // Check if any property failed validation
        $allValid = array_reduce($validationResults, function ($carry, $item) {
            return $carry && $item['valid'];
        }, true);

        // Proceed with DB insertion only if all properties are valid
        $response = ['success' => [], 'failed' => []];
        if ($allValid) {
            foreach ($properties as $property) {
                // All properties are previously validated, so we can directly insert them into the database
                $query = $pdo->prepare("INSERT INTO properties (property_name, property_url, property_discovery) VALUES (:name, :url, :discovery)");
                $query->execute([
                    ':name' => $property['property_name'],
                    ':url' => $property['property_url'],
                    ':discovery' => $property['discovery_type'],
                ]);
                $response['success'][] = $property['property_name'];
            }
        } else {
            // If not all properties are valid, add all to failed list without inserting any into the database
            foreach ($validationResults as $result) {
                if (!$result['valid']) {
                    $response['failed'][] = [
                        'property_name' => $result['property_name'],
                        'log' => $result['log']
                    ];
                }
            }
        }

        // Return response about valid and invalid properties
        echo json_encode($response);
    } else {
        echo json_encode(["error" => "CSV file is required and must be uploaded correctly."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
