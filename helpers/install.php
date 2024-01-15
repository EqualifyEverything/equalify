<?php
// Array of table creation queries
$tables = [
    "messages" => "CREATE TABLE `messages` (
        `message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `message_title` text NOT NULL,
        PRIMARY KEY (`message_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "meta" => "CREATE TABLE `meta` (
        `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `meta_name` varchar(220) DEFAULT NULL,
        `meta_value` longtext DEFAULT NULL,
        PRIMARY KEY (`meta_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
    
    "occurrences" => "CREATE TABLE `occurrences` (
            `occurrence_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `occurrence_message_id` bigint(20) NOT NULL,
            `occurrence_property_id` bigint(20) NOT NULL,
            `occurrence_status` varchar(220) NOT NULL,
            `occurrence_page_id` bigint(20) NOT NULL,
            `occurrence_source` varchar(220) NOT NULL,
            `occurrence_code_snippet` longtext DEFAULT NULL,
            `occurrence_archived` tinyint(1) DEFAULT NULL,
            PRIMARY KEY (`occurrence_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=67320 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "pages" => "CREATE TABLE `pages` (
            `page_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `page_url` text NOT NULL,
            `page_property_id` bigint(20) NOT NULL,
            PRIMARY KEY (`page_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=3998 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "properties" => "CREATE TABLE `properties` (
            `property_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `property_name` text NOT NULL,
            `property_archived` tinyint(1) DEFAULT NULL,
            `property_url` text NOT NULL,
            `property_processed` datetime DEFAULT NULL,
            `property_processing` tinyint(1) DEFAULT NULL,
            PRIMARY KEY (`property_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "queued_scans" => "CREATE TABLE `queued_scans` (
            `queued_scan_job_id` bigint(22) NOT NULL,
            `queued_scan_property_id` bigint(22) NOT NULL,
            `queued_scan_processing` tinyint(1) DEFAULT NULL,
            `queued_scan_prioritized` tinyint(1) DEFAULT NULL,
            PRIMARY KEY (`queued_scan_job_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "reports" => "CREATE TABLE `reports` (
            `report_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `report_title` text NOT NULL,
            `report_visibility` varchar(220) DEFAULT NULL,
            `report_filters` text DEFAULT NULL,
            PRIMARY KEY (`report_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "tag_relationships" => "CREATE TABLE `tag_relationships` (
            `occurrence_id` bigint(20) DEFAULT NULL,
            `tag_id` bigint(20) unsigned NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "tags" => "CREATE TABLE `tags` (
            `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `tag_name` varchar(220) NOT NULL,
            `tag_slug` varchar(220) NOT NULL,
            PRIMARY KEY (`tag_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "updates" => "CREATE TABLE `updates` (
            `update_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `occurrence_id` bigint(20) NOT NULL,
            `update_message` varchar(220) NOT NULL,
            PRIMARY KEY (`update_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
];

// Function to check and create table if it doesn't exist
function checkAndCreateTable($pdo, $tableName, $createQuery) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec($createQuery);
    }
}

// Loop through tables and check/create each
foreach ($tables as $tableName => $createQuery) {
    checkAndCreateTable($pdo, $tableName, $createQuery);
}