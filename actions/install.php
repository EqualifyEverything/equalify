<?php
// Array of table creation queries
$tables = [
    "status" => "CREATE TABLE status (
        status_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        status VARCHAR(220) NOT NULL
    );",

    "pages" => "CREATE TABLE pages (
        page_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        page_url TEXT NOT NULL,
        page_property_id BIGINT NOT NULL
    );",

    "code" => "CREATE TABLE code (
        code_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        code TEXT NOT NULL
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
        message_status_id BIGINT UNSIGNED NOT NULL,
        FOREIGN KEY (message_status_id) REFERENCES status(status_id)
    );",
    
    "message_pages" => "CREATE TABLE message_pages (
        message_id BIGINT UNSIGNED NOT NULL,
        page_id BIGINT UNSIGNED NOT NULL, 
        PRIMARY KEY (message_id, page_id),
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (page_id) REFERENCES pages(page_id) ON DELETE CASCADE
    );",    

    "message_code" => "CREATE TABLE message_code (
        message_id BIGINT UNSIGNED NOT NULL,
        code_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (message_id, code_id),
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
        FOREIGN KEY (code_id) REFERENCES code(code_id) ON DELETE CASCADE
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

    "message_updates" => "CREATE TABLE message_updates (
        update_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        date_created datetime NOT NULL,
        message_id BIGINT NOT NULL,
        message_update VARCHAR(220) NOT NULL
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
        queued_scan_page_id BIGINT,
        queued_scan_processing TINYINT(1),
        queued_scan_prioritized TINYINT(1)
    );"

];

// Function to check and create table if it doesn't exist
function tableExists($pdo, $tableName) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schemaName AND TABLE_NAME = :tableName");
    $stmt->execute(['schemaName' => 'test', 'tableName' => $tableName]); // Replace 'test' with your actual database name
    return $stmt->fetchColumn() > 0;
}

// Then use this function in your loop
foreach ($tables as $tableName => $createQuery) {
    if (!tableExists($pdo, $tableName)) {
        $pdo->exec($createQuery);
    }
}