<?php

// Assuming this script is process_scan.php
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/db.php');

try {

    // Check for any processing scans
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM queued_scans WHERE queued_scan_processing = 1");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        echo("A scan is already processing.\n");
    }

    // Fetch the next job ID
    $stmt = $pdo->prepare("SELECT queued_scan_job_id FROM queued_scans LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception("No scans to process.");
    }

    $job_id = $row['queued_scan_job_id'];

    // Perform the API GET request
    $api_url = "http://198.211.98.156/results/" . $job_id;
    $json = file_get_contents($api_url);
    if ($json === false) {
        throw new Exception("Failed to fetch data from API for job ID $job_id.");
    }

    // Decode the JSON response
    $data = json_decode($json, true);
    if (!isset($data['result']) || !isset($data['result']['results']['violations'])) {
        throw new Exception("Invalid result format for job ID $job_id.");
    }

    $new_occurrences = [];
    $page_url = $data['result']['results']['url'] ?? 'Unknown URL';
    $page_id = get_page_id($page_url);
    $property_id = get_property_id("http://example.com"); // This will be set later

    // Check if violations exist and are not empty
    if (isset($data['result']['results']['violations']) && !empty($data['result']['results']['violations'])) {
        // Process violations
        foreach ($data['result']['results']['violations'] as $violation) {
            if (!isset($violation['id'], $violation['tags'], $violation['help'], $violation['nodes'])) {
                throw new Exception("Invalid violation format for job ID $job_id.");
            }

            foreach ($violation['nodes'] as $node) {
                if (!isset($node['html'])) {
                    throw new Exception("Invalid node format in violations for job ID $job_id.");
                }

                foreach (['any', 'all', 'none'] as $key) {
                    if (isset($node[$key]) && is_array($node[$key])) {
                        foreach ($node[$key] as $item) {
                            if (!isset($item['message'])) {
                                throw new Exception("Invalid '$key' format in node for job ID $job_id.");
                            }

                            // Construct the occurrence data
                            $new_occurrences[] = [
                                "occurrence_message_id" => get_message_id($item['message'],$violation['help']),
                                "occurrence_code_snippet" => $node['html'],
                                "occurrence_page_id" => get_page_id($page_url),
                                "occurrence_source" => "scan.equalify.app",
                                "occurrence_property_id" => get_property_id("http://example.com"), // Note: This will be set later
                                "tag_ids" => get_tag_ids($violation['tags'])
                            ];
                        }
                    }
                }
            }
        }
    }

    // Group occurrences by page_id and source
    $grouped_occurrences = [];
    foreach ($new_occurrences as $occurrence) {
        $key = $occurrence['occurrence_page_id'] . '_' . $occurrence['occurrence_source'];
        $grouped_occurrences[$key][] = $occurrence;
    }

    // If no new occurrences are found, add a dummy group to trigger the database check
    if (empty($new_occurrences)) {
        $grouped_occurrences[$page_id . '_scan.equalify.app'] = [];
    }

    
    $reactivated_occurrences = [];
    $equalified_occurrences = [];
    $to_save_occurrences = [];

    foreach ($grouped_occurrences as $key => $group) {
        list($page_id, $source) = explode('_', $key);

        // Fetch existing occurrences from database
        $existing_occurrences_stmt = $pdo->prepare("SELECT * FROM occurrences WHERE occurrence_page_id = ? AND occurrence_source = ?");
        $existing_occurrences_stmt->execute([$page_id, $source]);
        $existing_occurrences = $existing_occurrences_stmt->fetchAll(PDO::FETCH_ASSOC);

        $existing_ids_in_group = [];

        // Check if each new occurrence exists in the database
        foreach ($group as $occurrence) {
            $found = false;
            foreach ($existing_occurrences as $existing_occurrence) {
                if ($existing_occurrence['occurrence_code_snippet'] == $occurrence['occurrence_code_snippet'] &&
                    $existing_occurrence['occurrence_message_id'] == $occurrence['occurrence_message_id']) {
                    $found = true;
                    $existing_ids_in_group[] = $existing_occurrence['occurrence_id'];
                    if ($existing_occurrence['occurrence_status'] == 'equalified') {
                        $reactivated_occurrences[] = $existing_occurrence['occurrence_id'];
                    }
                    break;
                }
            }

            if (!$found) {
                $to_save_occurrences[] = $occurrence;
            }
        }

        // Mark as 'equalified' occurrences that are in the database but not in new occurrences
        foreach ($existing_occurrences as $existing_occurrence) {
            if (!in_array($existing_occurrence['occurrence_id'], $existing_ids_in_group)) {
                $equalified_occurrences[] = $existing_occurrence['occurrence_id'];
            }
        }
    }

    // Save new occurrences as 'activated'
    $new_occurrence_ids = [];
    foreach ($to_save_occurrences as $occurrence) {
        $insert_stmt = $pdo->prepare("INSERT INTO occurrences (occurrence_message_id, occurrence_code_snippet, occurrence_page_id, occurrence_source, occurrence_property_id, occurrence_status) VALUES (?, ?, ?, ?, ?, 'active')");
        $insert_stmt->execute([
            $occurrence['occurrence_message_id'],
            $occurrence['occurrence_code_snippet'],
            $occurrence['occurrence_page_id'],
            $occurrence['occurrence_source'],
            $occurrence['occurrence_property_id']
        ]);
        $new_occurrence_ids[] = $pdo->lastInsertId();
    }

    // Update statuses in the database
    $update_stmt = $pdo->prepare("UPDATE occurrences SET occurrence_status = ? WHERE occurrence_id = ?");
    foreach ($reactivated_occurrences as $id) {
        $update_stmt->execute(['active', $id]);
    }
    foreach ($equalified_occurrences as $id) {
        $update_stmt->execute(['equalified', $id]);
    }

    // Insert updates for new and reactivated occurrences
    $insert_update_stmt = $pdo->prepare("INSERT INTO updates (date_created, occurrence_id, update_message) VALUES (NOW(), ?, ?)");
    foreach (array_merge($new_occurrence_ids, $reactivated_occurrences) as $id) {
        $insert_update_stmt->execute([$id, 'activated']);
    }

    // Insert updates for equalified occurrences
    foreach ($equalified_occurrences as $id) {
        $insert_update_stmt->execute([$id, 'equalified']);
    }

    // On success delete scan
    $query = "DELETE FROM queued_scans WHERE queued_scan_job_id = :queued_scan_job_id";
    $statement = $pdo->prepare($query);
    $statement->execute([':queued_scan_job_id' => $scan_id]);

} catch (Exception $e) {
    // Handle the exception
    error_log($e->getMessage());

    // Remove processing
    $stmt = $pdo->prepare("UPDATE queued_scans SET queued_scan_processing = NULL WHERE queued_scan_job_id = ?");
    $stmt->execute([$job_id]);

    exit;
    
}

// Help Functions:
function get_property_id($url) {
    global $pdo;

    // Check if the page exists
    $pageQuery = "SELECT property_id FROM properties WHERE property_url = :url";
    $pageStmt = $pdo->prepare($pageQuery);
    $pageStmt->execute([':url' => $url]);
    $pageRow = $pageStmt->fetch(PDO::FETCH_ASSOC);

    if ($pageRow) {
        return $pageRow['property_id']; // Return existing ID
    } else {
        throw new Exception("No property id found for $url");
    }
}

function get_message_id($title, $body) {
    global $pdo;

    // Check if the message exists
    $query = "SELECT message_id FROM messages WHERE message_title = :title AND message_body = :body";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':title' => $title, ':body' => $body]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row['message_id']; // Return existing ID
    } else {
        // Insert the new message
        $insertQuery = "INSERT INTO messages (message_title, message_body) VALUES (:title, :body)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([':title' => $title, ':body' => $body]);
        return $pdo->lastInsertId(); // Return new ID
    }
}

function get_page_id($url) {
    global $pdo;

    // Check if the page exists
    $pageQuery = "SELECT page_id FROM pages WHERE page_url = :url";
    $pageStmt = $pdo->prepare($pageQuery);
    $pageStmt->execute([':url' => $url]);
    $pageRow = $pageStmt->fetch(PDO::FETCH_ASSOC);

    if ($pageRow) {
        return $pageRow['page_id']; // Return existing ID
    } else {
        // Insert the new page
        $insertPageQuery = "INSERT INTO pages (page_url) VALUES (:url)";
        $insertPageStmt = $pdo->prepare($insertPageQuery);
        $insertPageStmt->execute([':url' => $url]);
        return $pdo->lastInsertId(); // Return new ID
    }
}

function get_tag_ids($tags) {
    global $pdo;
    $tagIds = [];

    foreach ($tags as $tag) {
        $sanitizedTagSlug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($tag)); // Sanitize tag slug

        // Check if the tag exists
        $tagQuery = "SELECT tag_id FROM tags WHERE tag_name = :tag";
        $tagStmt = $pdo->prepare($tagQuery);
        $tagStmt->execute([':tag' => $tag]);
        $tagRow = $tagStmt->fetch(PDO::FETCH_ASSOC);

        if ($tagRow) {
            $tagIds[] = (int)$tagRow['tag_id'];
        } else {
            // Insert the new tag
            $insertTagQuery = "INSERT INTO tags (tag_name, tag_slug) VALUES (:tag, :slug)";
            $insertTagStmt = $pdo->prepare($insertTagQuery);
            $insertTagStmt->execute([':tag' => $tag, ':slug' => $sanitizedTagSlug]);
            $tagIds[] = (int)$pdo->lastInsertId();
        }
    }

    return $tagIds; // Return concatenated tag IDs
}

?>
