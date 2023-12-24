<?php
include '../db.php'; 

try {

    $next = get_queued_scan();

    if(!empty($next) && ($next['queued_scan_running'] !== 1)){

        // Mark the scan as running
        mark_scan_as_running($next['queued_scan_job_id']);

        // If scan needs to be processed, let's go!
        get_job_results($next['queued_scan_job_id'], $next['queued_scan_property_id']);

        // Log success.
        echo "Processed scan with Job ID ".$next['queued_scan_job_id']."\n";

        // On success, remove sitemap from db and rerun script.
        remove_scan_from_queue($next['queued_scan_job_id']);
        exit;

    }else{
        echo "No scans to process.\n";
        exit;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

// Function to get next scan.
function get_queued_scan() {
    global $pdo;
    $query = "SELECT queued_scan_property_id, queued_scan_job_id, queued_scan_running FROM queued_scans ORDER BY queued_scan_job_id ASC LIMIT 1";
    $statement = $pdo->prepare($query);
    $statement->execute();
    return $statement->fetch(PDO::FETCH_ASSOC); // Fetch only the first row
}

// Mark Scan as running
function mark_scan_as_running($scan_job_id) {
    global $pdo;
    $updateQuery = "UPDATE queued_scans SET queued_scan_running = 1 WHERE queued_scan_job_id = :scan_job_id";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([':scan_job_id' => $scan_job_id]);
}

// Function to get results for a specific job ID
function get_job_results($job_id, $property_id) {
    // API endpoint
    $api_url = 'http://198.211.98.156/results/' . $job_id;

    // Initialize cURL session
    $ch = curl_init($api_url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL session
    $response = curl_exec($ch);

    // Check for errors
    if(curl_errno($ch)){
        throw new Exception(curl_error($ch));
    }

    // Close cURL session
    curl_close($ch);

    // Decode JSON response
    $response = json_decode($response, true);

    // Properly formatted scans have a status
    if(empty($response['status'])){
        remove_scan_from_queue($job_id);
        throw new Exception("Scan $job_id skipped. Scan status is invalid.");
        exit;
    }

    // Different statuses required different action.
    switch ($response['status']) {
        case 'waiting':
        case 'active':
            // Terminate the process. The cron job will check again.
            echo "Status is '{$response['status']}'. Will check again later.";
            exit;
        case 'completed':
            // Process completed results
            process_completed_results($response, $property_id);
            break;
        case 'failed':
            echo "Job failed.";
            break;
        default:
            echo "Unknown status.";
    }
}

function process_completed_results($response, $property_id) {
    $new_occurrences = array();

    // Check if there are violations in the response
    if (isset($response['result']['results']['violations']) && is_array($response['result']['results']['violations'])) {
        foreach ($response['result']['results']['violations'] as $violation) {
            foreach ($violation['nodes'] as $node) {
                $occurrence = array(
                    'occurrence_id' => '',
                    'occurrence_code_snippet' => $node['html'],
                    'occurrence_message_id' =>  handle_message_db($violation['help'], $violation['description']),
                    'occurrence_property_id' => $property_id,
                    'occurrence_tag_ids' => handle_tags_db($violation['tags']),
                    'occurrence_page_id' =>  handle_page_db('https://temp.com'), // use $response['url']
                    'occurrence_source' => 'Equalify Scan',
                );

                $new_occurrences[] = $occurrence;
            }
        }

    }

    // Insert occurrences into the database
    $added_occurrences = handle_queued_occurrences($new_occurrences);
    add_tag_relationships($added_occurrences);
        
}

// Function to handle message database operations
function handle_message_db($title, $body) {
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

// Function to handle tags database operations
function handle_tags_db($tags) {
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

// Function to handle page database operations
function handle_page_db($url) {
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

function handle_queued_occurrences($new_occurrences) {
    global $pdo;

    $query = "INSERT INTO queued_occurrences 
              (queued_occurrence_property_id, queued_occurrence_code_snippet, queued_occurrence_message_id, queued_occurrence_page_id, queued_occurrence_source) 
              VALUES 
              (:occurrence_property_id, :occurrence_code_snippet, :occurrence_message_id, :occurrence_page_id, :occurrence_source)";

    foreach ($new_occurrences as &$occurrence) {
        $statement = $pdo->prepare($query);
        $statement->execute(array(
            ':occurrence_property_id' => $occurrence['occurrence_property_id'],
            ':occurrence_code_snippet' => $occurrence['occurrence_code_snippet'],
            ':occurrence_message_id' => $occurrence['occurrence_message_id'],
            ':occurrence_page_id' => $occurrence['occurrence_page_id'],
            ':occurrence_source' => $occurrence['occurrence_source']
        ));
        $occurrence['occurrence_id'] = (int)$pdo->lastInsertId();
    }
    unset($occurrence); 

    return $new_occurrences;
}

function add_tag_relationships($added_occurrences) {
    global $pdo;

    // Start transaction
    $pdo->beginTransaction();

    try {
        $query = "INSERT INTO tag_relationships (tag_id, queued_occurrence_id) VALUES ";

        $insertValues = [];
        $params = [];
        $index = 0;

        foreach ($added_occurrences as $occurrence) {
            foreach ($occurrence['occurrence_tag_ids'] as $tag_id) {
                $insertValues[] = "(:tag_id{$index}, :occurrence_id{$index})";
                $params[":tag_id{$index}"] = $tag_id;
                $params[":occurrence_id{$index}"] = $occurrence['occurrence_id'];
                $index++;
            }
        }

        if(!empty($insertValues)) {
            $query .= implode(', ', $insertValues);
            $statement = $pdo->prepare($query);
            $statement->execute($params);
        }

        // Commit the transaction
        $pdo->commit();
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        throw $e;
    }
}

function remove_scan_from_queue($scan_id) {
    global $pdo;

    $query = "DELETE FROM queued_scans WHERE queued_scan_job_id = :queued_scan_job_id";
    $statement = $pdo->prepare($query);
    $statement->execute([':queued_scan_job_id' => $scan_id]);

    // You can check the number of affected rows if needed
    $deletedRows = $statement->rowCount();

    return $deletedRows; // Returns the number of rows deleted
}
?>