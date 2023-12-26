<?php

// Directory containing the result files
$resultsDir = '/var/www/html/WIP/_temp/';

// Variables to store the total counts
$totalPasses = 0;
$totalIncomplete = 0;
$totalViolations = 0;
$filesCounted = 0;

// Scan the directory for .json files
$files = new DirectoryIterator($resultsDir);

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'json') {
        try {
            // Read the file and decode the JSON content
            $jsonData = file_get_contents($file->getPathname());
            $data = json_decode($jsonData, true);

            // Check if JSON decoding was successful and process the results
            if (is_array($data) && isset($data['result']['results'])) {
                $results = $data['result']['results'];

                // Use the null coalescing operator to simplify counting
                $totalPasses += count($results['passes'] ?? []);
                $totalIncomplete += count($results['incomplete'] ?? []);
                $totalViolations += count($results['violations'] ?? []);

                // Increment the files counted
                $filesCounted++;
            }
        } catch (Exception $e) {
            // Log error or simply skip the file
            echo "Error processing file " . $file->getFilename() . ": " . $e->getMessage() . "\n";
            continue;
        }
    }
}

// Display the total counts and number of files counted
echo "Total files counted: $filesCounted\n";
echo "Total Equalified: $totalPasses\n";
echo "Total Incomplete: $totalIncomplete\n";
echo "Total Violations: $totalViolations\n";

?>
