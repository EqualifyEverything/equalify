<?php
// Require helpers and DB
require_once '../init.php';
require_once '../helpers/validate_url.php';

// Initialize arrays to hold properties and validation results
$properties = [];
$validationResults = [];

// Define the valid discovery options
$validDiscoveryOptions = ['single page import', 'sitemap import', 'crawl'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == 0) {
        // Handle CSV file upload
        $file = $_FILES['csvFile']['tmp_name'];
        $handle = fopen($file, "r");
        $header = fgetcsv($handle); // Read the first row as headers

        // Map column headers to their indices
        $columnIndices = array_flip($header); // Flip keys with values
        
        // Validate existence of required columns
        if (!isset($columnIndices['Name'], $columnIndices['URL'], $columnIndices['Discovery'])) {
            echo json_encode(["error" => "Invalid CSV format. Columns 'Name', 'URL', and 'Discovery' are required."]);
            exit;
        }

        $properties = [];
        while ($row = fgetcsv($handle)) {
            $discoveryType = strtolower(trim($row[$columnIndices['Discovery']]));
            // Check if the discovery type is valid
            if (!in_array($discoveryType, $validDiscoveryOptions)) {
                echo json_encode(["error" => "Invalid discovery type '$discoveryType'. Valid options are 'Single Page Import', 'Sitemap Import', or 'Crawl'."]);
                fclose($handle);
                exit;
            }

            $properties[] = [
                "property_name" => $row[$columnIndices['Name']],
                "property_url" => $row[$columnIndices['URL']],
                "property_discovery" => $discoveryType,
            ];
        }
        fclose($handle);

        // First, validate all properties without saving to the database
        foreach ($properties as $property) {
            $validationResult = validateUrl($property['property_url'], $property['property_discovery'], $pdo);
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
                    ':discovery' => $property['property_discovery'],
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
