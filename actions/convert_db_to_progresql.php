<?php
// Built with ❤️ from ChatGPT here: https://chatgpt.com/share/e1d01a41-f014-44b0-bcfe-324679c7116b
?>

<?php
// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Import init.php file
require 'init.php';

// Create exports directory if it doesn't exist
if (!file_exists('exports')) {
    mkdir('exports', 0777, true);
}

// Define user details for the conversion
$user_id = '31bb8500-f091-70b1-9009-85d53a967818';
$email = 'blake@bertuccelli-booth.org';
$first_name = 'Blake';
$last_name = 'Bertuccelli-Booth';

// Function to convert MySQL datetime to PostgreSQL timestamptz
function convertDatetime($datetime) {
    return is_null($datetime) ? null : (new DateTime($datetime))->format(DateTime::ATOM);
}

// Function to export data from MySQL to a SQL file for PostgreSQL
function exportTable($mysql_table, $pg_table, $columns_map) {
    global $pdo, $user_id;

    $filePath = "exports/{$pg_table}.sql";
    $file = fopen($filePath, 'w');

    $stmt = $pdo->query("SELECT * FROM $mysql_table");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns = [];
        $values = [];
        
        foreach ($columns_map as $mysql_col => $pg_col) {
            if (strpos($pg_col, '::') !== false) {
                list($pg_col, $conversion) = explode('::', $pg_col);
                $value = $conversion($row[$mysql_col]);
            } else {
                $value = $row[$mysql_col];
            }
            
            $columns[] = $pg_col;
            $values[] = is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
        }
        
        $columns[] = 'user_id';
        $values[] = "'" . addslashes($user_id) . "'";
        
        $columns_str = implode(',', $columns);
        $values_str = implode(',', $values);
        
        fwrite($file, "INSERT INTO $pg_table ($columns_str) VALUES ($values_str);\n");
    }

    fclose($file);
}

// Export each table to a separate file
exportTable('message_nodes', 'message_nodes', [
    'message_id' => 'message_id',
    'node_id' => 'enode_id'
]);

exportTable('message_tags', 'message_tags', [
    'message_id' => 'message_id',
    'tag_id' => 'tag_id'
]);

exportTable('messages', 'messages', [
    'message_id' => 'id',
    'message' => 'message',
    'message_type' => 'messageType'
]);

exportTable('node_updates', 'enode_updates', [
    'update_id' => 'id',
    'update_date' => 'created_at::convertDatetime',
    'update_date' => 'updated_at::convertDatetime',
    'node_id' => 'enode_id',
    'node_equalified' => 'equalified'
]);

exportTable('nodes', 'enodes', [
    'node_id' => 'id',
    'node_equalified' => 'equalified',
    'node_html' => 'html',
    'node_url_id' => 'url_id',
    'node_targets' => 'targets'
]);

exportTable('properties', 'properties', [
    'property_id' => 'id',
    'property_name' => 'name',
    'property_archived' => 'archived',
    'property_processed' => 'processed::convertDatetime',
    'property_processing' => 'processed::convertDatetime',
    'property_url' => 'propertyUrl',
    'property_discovery' => 'discovery',
    'property_processed' => 'processed_at::convertDatetime',
    'property_processed' => 'lastProcessed::convertDatetime',
    'property_processed' => 'created_at::convertDatetime',
    'property_processed' => 'updated_at::convertDatetime'
]);

exportTable('queued_scans', 'scans', [
    'queued_scan_job_id' => 'job_id',
    'queued_scan_property_id' => 'property_id',
    'queued_scan_url_id' => 'url_id',
    'queued_scan_processing' => 'processing'
]);

exportTable('reports', 'reports', [
    'report_id' => 'id',
    'report_title' => 'name',
    'report_filters' => 'filters'
]);

exportTable('tags', 'tags', [
    'tag_id' => 'id',
    'tag' => 'tag'
]);

exportTable('urls', 'urls', [
    'url_id' => 'id',
    'url' => 'url',
    'url_property_id' => 'property_id'
]);

echo "Data export completed successfully. Check the /exports directory for the SQL files.";
?>