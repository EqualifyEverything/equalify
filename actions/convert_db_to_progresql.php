<?php
// Built with ❤️ from ChatGPT here: https://chatgpt.com/share/e1d01a41-f014-44b0-bcfe-324679c7116b

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Import init.php file
require 'init.php';

// PostgreSQL connection settings
$pg_host = 'your_postgresql_host';
$pg_port = 'your_postgresql_port';
$pg_dbname = 'your_postgresql_dbname';
$pg_user = 'your_postgresql_user';
$pg_pass = 'your_postgresql_password';

// Create PostgreSQL connection
$pg_pdo = new PDO("pgsql:host=$pg_host;port=$pg_port;dbname=$pg_dbname", $pg_user, $pg_pass);
$pg_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Define user details for the conversion
$user_id = '31bb8500-f091-70b1-9009-85d53a967818';
$email = 'blake@bertuccelli-booth.org';
$first_name = 'Blake';
$last_name = 'Bertuccelli-Booth';

// Function to convert MySQL datetime to PostgreSQL timestamptz
function convertDatetime($datetime) {
    return (new DateTime($datetime))->format(DateTime::ATOM);
}

// Insert user into PostgreSQL
$pg_pdo->exec("
    INSERT INTO users (id, email, first_name, last_name) VALUES 
    ('$user_id', '$email', '$first_name', '$last_name')
    ON CONFLICT (id) DO NOTHING;
");

// Function to export and insert data from MySQL to PostgreSQL
function exportTable($mysql_table, $pg_table, $columns_map) {
    global $pdo, $pg_pdo, $user_id;
    
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
            $values[] = $pg_pdo->quote($value);
        }
        
        $columns[] = 'user_id';
        $values[] = $pg_pdo->quote($user_id);
        
        $columns_str = implode(',', $columns);
        $values_str = implode(',', $values);
        
        $pg_pdo->exec("INSERT INTO $pg_table ($columns_str) VALUES ($values_str)");
    }
}

// Export and convert each table
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

echo "Data export and conversion completed successfully.";
?>
