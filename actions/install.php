<?php
// Array of table creation queries
$tables = [
    "urls" => "CREATE TABLE urls (
        url_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        url TEXT NOT NULL,
        url_property_id BIGINT NOT NULL
    );",

    "nodes" => "CREATE TABLE nodes (
        node_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        node_equalified TINYINT(1),
        node_html TEXT NOT NULL,
        node_targets JSON
    );",

    "node_urls" => "CREATE TABLE node_urls (
        node_id BIGINT UNSIGNED NOT NULL,
        url_id BIGINT UNSIGNED NOT NULL, 
        PRIMARY KEY (node_id, url_id),
        FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON DELETE CASCADE,
        FOREIGN KEY (url_id) REFERENCES urls(url_id) ON DELETE CASCADE
    );",

    "node_updates" => "CREATE TABLE code_updates (
        update_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        update_date datetime NOT NULL,
        node_id BIGINT NOT NULL,
        node_equalified TINYINT(1) NOT NULL
    );",

    "tags" => "CREATE TABLE tags (
        tag_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        tag VARCHAR(220) NOT NULL
    );",

    "properties" => "CREATE TABLE properties (
        property_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        property_name TEXT NULL,
        property_archived TINYINT(1),
        property_processed DATETIME,
        property_processing TINYINT(1),
        property_url TEXT,
        property_discovery VARCHAR(220)
    );",

    "messages" => "CREATE TABLE messages (
        message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        message_type VARCHAR(220) NOT NULL
    );",
    
    "message_urls" => "CREATE TABLE message_urls (
        message_id BIGINT UNSIGNED NOT NULL,
        url_id BIGINT UNSIGNED NOT NULL, 
        PRIMARY KEY (message_id, url_id),
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (url_id) REFERENCES urls(url_id) ON DELETE CASCADE
    );",

    "message_nodes" => "CREATE TABLE message_nodes (
        message_id BIGINT UNSIGNED NOT NULL,
        node_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (message_id, node_id),
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (node_id) REFERENCES nodes(node_id) ON DELETE CASCADE
    );",

    "message_tags" => "CREATE TABLE message_tags (
        message_id BIGINT UNSIGNED NOT NULL,
        tag_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (message_id, tag_id),
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
    );",

    "message_properties" => "CREATE TABLE message_properties (
        message_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (message_id, property_id),
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE
    );",

    "reports" => "CREATE TABLE reports (
        report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        report_title TEXT NOT NULL,
        report_visibility VARCHAR(220),
        report_filters TEXT
    );",

    "queued_scans" => "CREATE TABLE queued_scans (
        queued_scan_job_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        queued_scan_property_id BIGINT,
        queued_scan_url_id BIGINT,
        queued_scan_processing TINYINT(1),
        queued_scan_prioritized TINYINT(1)
    );"

];

// Function to check and create table if it doesn't exist
function tableExists($pdo, $tableName) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schemaName AND TABLE_NAME = :tableName");
    $stmt->execute(['schemaName' => $_ENV['DB_NAME'], 'tableName' => $tableName]);
    return $stmt->fetchColumn() > 0;
}

// Then use this function in your loop
foreach ($tables as $tableName => $createQuery) {
    if (!tableExists($pdo, $tableName)) {        $pdo->exec($createQuery);
    }
}