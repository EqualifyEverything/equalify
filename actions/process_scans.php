<?php
// This file is designed to be run from command line
// so we can do things like trigger via CRON.
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/init.php'); 

try {

    // This script can be run via cli
    if (php_sapi_name() === 'cli') {

        // cli arguments
        parse_str(implode('&', array_slice($argv, 1)), $_CLI);
        $job_id = isset($_CLI['job_id']) ? $_CLI['job_id'] : '';
        $property_id = isset($_CLI['property_id']) ? $_CLI['property_id'] : '';

        // Check if it's an individual scan is requested
        if (!empty($job_id ) && !empty($property_id)) {
            if ($job_id !== false && $property_id !== false) {
                $scans = [
                    [
                        'queued_scan_job_id' => $job_id,
                        'queued_scan_property_id' => $property_id,
                    ]
                ];
                process_scans($scans);
            } else {
                $error_message = 'Invalid scan parameters';
                $_SESSION['error'] = $error_message;
                header("Location: ../index.php?view=scans");
                exit;
            }

        // No arguements mean we process everything.
        } else {
            process_scans();
        }
    
    // The script can also be run by posting to it or via custom URL variables
    }elseif ( 
        (isset($_POST['job_id']) && isset($_POST['property_id'])) || 
        (isset($_GET['job_id']) && isset($_GET['property_id']))
    ) {
        
        // Validate and sanitize inputs
        if(isset($_POST['job_id']))
            $job_id = filter_var($_POST['job_id'], FILTER_VALIDATE_INT);
        if(isset($_POST['property_id']))
            $property_id = filter_var($_POST['property_id'], FILTER_VALIDATE_INT);
        if(isset($_GET['job_id']))
            $job_id = $_GET['job_id'];
        if(isset($_GET['property_id']))
            $property_id = $_GET['property_id'];

        if ($job_id !== false && $property_id !== false) {
            $scans = [
                [
                    'queued_scan_job_id' => $job_id,
                    'queued_scan_property_id' => $property_id,
                ]
            ];
            process_scans($scans);
        } else {
            $error_message = 'Invalid scan parameters';
            update_log($error_message);
            $_SESSION['error'] = $error_message;
            header("Location: ../index.php?view=scans");
            exit;
        }

    } else {

        // Not a POST request. Could be CLI or other
        // Existing code for processing scans
        process_scans();

    }

} catch (Exception $e) {

    // Handle the exception
    echo $e->getMessage();
    exit;

}

// Helper Functions
function process_scans($scans = null) {
    global $pdo;

    $max_scans = $_ENV['CONCURRENT_SCANS'] ?? 20; // Set maximum concurrent scans, default to 20 (what axe can do on xxs machine)

    // If scans aren't declared, this will just get multiple
    // scans automatically. 
    if ($scans === null) {

        // Fetch up prioritized scans
        $stmt = $pdo->prepare("SELECT queued_scan_job_id, queued_scan_property_id FROM queued_scans WHERE queued_scan_prioritized = 1 LIMIT $max_scans;");
        $stmt->execute();
        $prioritized_scans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Just use prioritized scans if there's enough
        if(count($prioritized_scans) >= $max_scans)
            $scans = $prioritized_scans;

        // If not enough prioritized scans fetch the next scan
        if (count($prioritized_scans) < $max_scans || empty($scans)) {
            $scan_limit = $max_scans - count($prioritized_scans);
            $stmt = $pdo->prepare("SELECT queued_scan_job_id, queued_scan_property_id FROM queued_scans WHERE queued_scan_processing IS NULL AND queued_scan_prioritized IS NULL LIMIT $scan_limit;");
            $stmt->execute();
            $other_scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $scans = array_merge($prioritized_scans, $other_scans);
        }

    }

    // Handle if no scans to process.
    if (empty($scans)) {

        // Stop process if there is no scan.
        $error_message = 'No scans to process.';
        update_log($error_message);
        $_SESSION['error'] = $error_message;
        header("Location: ../index.php?view=scans");
        exit;

    }

    // Run API on each scan
    $logged_messages = array();
    foreach ($scans as $scan):

        // Define property id and job id.
        $property_id = $scan['queued_scan_property_id'];
        $job_id = $scan['queued_scan_job_id'];

        // Set the scan as processing
        update_processing_value($job_id, 1);

        // Perform the API GET request
        $api_url =  $_ENV['SCAN_URL']. '/results/' . $job_id;
        $json = file_get_contents($api_url);

        // Handle scans that don't return JSON.
        if ($json === false) {
            $message = "Scan $job_id returns no JSON. Scan deleted.";
            $logged_messages.=$message.'<br>';
            update_log($message);
            delete_scan($job_id);
            continue;
        }

        // Decode the JSON response
        $data = json_decode($json, true);

        // Handle incomplete scans.
        $statuses = array('delayed', 'active', 'waiting');
        if(in_array($data['status'], $statuses)){
            $message = 'Scan ' . $job_id . ' has "' . $data['status'] .'" status. Scan skipped.';
            $logged_messages.=$message.'<br>';
            update_log($message);
            update_processing_value($job_id, NULL);
            continue;
        }

        // Handle problems scans.
        $statuses = array('failed', 'unknown');
        if(in_array($data['status'], $statuses)){
            $message = 'Scan ' . $job_id . ' has "' . $data['status'] .'" status. Scan skipped.';
            $logged_messages.=$message.'<br>';
            update_log($message);
            delete_scan($job_id);
            continue;
        }

        // Setup variables from decoded json
        $new_occurrences = [];
        $page_url = $data['result']['results']['url'] ?? '';

        // Setup page id
        $page_id = get_page_id($page_url, $property_id);

        // Check if violations are formatted correctly and set them up
        if (isset($data['result']['results']['violations']) && !empty($data['result']['results']['violations'])) {
            foreach ($data['result']['results']['violations'] as $violation) {

                // Handle incorrectly formatted violations
                if (!isset($violation['id'], $violation['tags'], $violation['nodes'])) {
                    $message = "Scan $job_id returns violations in invalid format. Scan deleted.";
                    $logged_messages.=$message.'<br>';
                    update_log($message);
                    delete_scan($job_id);
                    continue;
                }

                // Handle More Info URL
                $message_link = $violation['helpUrl'];

                foreach ($violation['nodes'] as $node) {

                    // Handle incorrectly formatted nodes.
                    if (!isset($node['html'])) {
                        $message = "Scan $job_id returns node in invalid format. Scan deleted.";
                        $logged_messages.=$message.'<br>';
                        update_log($message);
    
                        delete_scan($job_id);
                        continue;
                    }
                    foreach (['any', 'all', 'none'] as $key) {
                        if (isset($node[$key]) && is_array($node[$key])) {
                            foreach ($node[$key] as $item) {

                                // Handle incorrectly formatted messages.
                                if (!isset($item['message'])) {
                                    $message = "Scan $job_id returns invalid '$key' format in node. Scan deleted.";
                                    $logged_messages.=$message.'<br>';
                                    update_log($message);
                
                                    delete_scan($job_id);
                                    continue;
                                }

                                // Construct the occurrence data
                                $new_occurrences[] = [
                                    "occurrence_message_id" => get_message_id($item['message'], $message_link),
                                    "occurrence_code_snippet" => $node['html'],
                                    "occurrence_page_id" => $page_id,
                                    "occurrence_source" => "scan.equalify.app",
                                    "occurrence_property_id" => $property_id,
                                    "tag_ids" => get_tag_ids($violation['tags'])
                                ];
                            }
                        }
                    }
                }
            }
        
        // Handle unformatted results.
        }else{
            $message = "Scan $job_id returns no violations. Scan deleted.";
            $logged_messages[] = $message;
            update_log($message);
            delete_scan($job_id);
            continue;
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

            // Mark as 'equalified' occurrences that are in the database without the status "equalfied" but not in new occurrences
            foreach ($existing_occurrences as $existing_occurrence) {
                if (!in_array($existing_occurrence['occurrence_id'], $existing_ids_in_group) && $existing_occurrence['occurrence_status'] !== 'equalified') {
                    $equalified_occurrences[] = $existing_occurrence['occurrence_id'];
                }
            }

        }

        // Save new occurrences as 'activated'
        $new_occurrence_ids = [];
        $new_occurrence_tag_relationships = [];
        foreach ($to_save_occurrences as $occurrence) {

            // Insert occurrences into db.
            $insert_stmt = $pdo->prepare("INSERT INTO occurrences (occurrence_message_id, occurrence_code_snippet, occurrence_page_id, occurrence_source, occurrence_property_id, occurrence_status) VALUES (?, ?, ?, ?, ?, 'active')");
            $insert_stmt->execute([
                $occurrence['occurrence_message_id'],
                $occurrence['occurrence_code_snippet'],
                $occurrence['occurrence_page_id'],
                $occurrence['occurrence_source'],
                $occurrence['occurrence_property_id']
            ]);
            $new_occurrence_ids[] = $pdo->lastInsertId();
            $new_occurrence_tag_relationships[] = array(
                'occurrence_id' => $pdo->lastInsertId(),
                'occurrence_tag_ids' => $occurrence['tag_ids']
            );

        }

        // Insert tags relationships into db
        add_tag_relationships($new_occurrence_tag_relationships);

        // Count occurrences for logging
        $count_reactivated_occurrences = count($reactivated_occurrences);
        $count_equalified_occurrences = count($equalified_occurrences);
        $count_new_occurrence_ids = count($new_occurrence_ids);

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
        delete_scan($job_id);

        // Log output.
        $message = "Scan $job_id successfully processed. $count_new_occurrence_ids new. $count_equalified_occurrences equalified. $count_reactivated_occurrences reactivated.";
        $logged_messages[] = $message;
        update_log($message);

    // End scan processing
    endforeach;

    // Redirect with logged messages
    if(!empty($logged_messages)){
        $success_message = 'Success! Returned the following results: <ul>';
        foreach ($logged_messages as $message){
            $success_message.= "<li>$message</li>";
        }
        $success_message.= "</ul>";
        $_SESSION['success'] = $success_message;
        header("Location: ../index.php?view=scans");    
    }
}

function update_processing_value($job_id, $new_value){
    global $pdo;

    $stmt = $pdo->prepare("UPDATE queued_scans SET queued_scan_processing = ? WHERE queued_scan_job_id = ?");
    $stmt->execute([$new_value, $job_id]);
}

function get_message_id($title, $message_link) {
    global $pdo;

    // Check if the message exists
    $query = "SELECT message_id FROM messages WHERE message_title = :title";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':title' => $title]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row['message_id']; // Return existing ID
    } else {
        // Insert the new message
        $insertQuery = "INSERT INTO messages (message_title, message_link) VALUES (:title, :message_link)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([':title' => $title, ':message_link' => $message_link]);
        return $pdo->lastInsertId(); // Return new ID
    }
}

function get_page_id($url, $property_id) {
    global $pdo;

    // Check if the page exists
    $pageQuery = "SELECT page_id FROM pages WHERE page_url = :url AND page_property_id = :property_id";
    $pageStmt = $pdo->prepare($pageQuery);
    $pageStmt->execute([':url' => $url, ':property_id' => $property_id]);
    $pageRow = $pageStmt->fetch(PDO::FETCH_ASSOC);

    if ($pageRow) {
        return $pageRow['page_id']; // Return existing ID
    } else {
        // Insert the new page
        $insertPageQuery = "INSERT INTO pages (page_url, page_property_id) VALUES (:url, :property_id)";
        $insertPageStmt = $pdo->prepare($insertPageQuery);
        $insertPageStmt->execute([':url' => $url, ':property_id' => $property_id]);
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

function add_tag_relationships($new_occurrence_tag_relationships) {
    global $pdo;

    // Start transaction
    $pdo->beginTransaction();

    try {
        $query = "INSERT INTO tag_relationships (tag_id, occurrence_id) VALUES ";

        $insertValues = [];
        $params = [];
        $index = 0;

        foreach ($new_occurrence_tag_relationships as $tag_relationship) {
            foreach ($tag_relationship['occurrence_tag_ids'] as $tag_id) {
                $insertValues[] = "(:tag_id{$index}, :occurrence_id{$index})";
                $params[":tag_id{$index}"] = $tag_id;
                $params[":occurrence_id{$index}"] = $tag_relationship['occurrence_id'];
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

function delete_scan($job_id){

    global $pdo;

    $query = "DELETE FROM queued_scans WHERE queued_scan_job_id = :queued_scan_job_id";
    $statement = $pdo->prepare($query);
    $statement->execute([':queued_scan_job_id' => $job_id]);

}

function update_log($message){
    echo(date('Y-m-d H:i:s') . ": $message\n");
}
?>
