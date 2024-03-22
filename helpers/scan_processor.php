<?php
/* Scan Processor - helpers/scan_processor.php
 *
 * Processes STREAM results. This processor will:
 * 1. Add new urls
 * 2. Add new messages
 * 3. Add new tags and relate tags to messages.
 * 4. Add new nodes and relate nodes to messages and URLs.
 * 5. Remove/add "Equalified" status.
 * 
 * What does Equalified mean?
 * 
 * Equalified is when a node on a page is no longer related to a 
 * violation or error.
 * 
 * A few known issues:
 * - If a message tag or node relationship is deleted, it won't be
 *   deleted by this script. This should be okay since most scanners 
 *   have no reason to delete a tag and we want to keep a record of
 *   what nodes were associated with what messages.
 * - If a message is deleted, this script won't delete a tag. This
 *   is okay because we want to have as many messages as possible, 
 *   it could also be an issue if message text just changes in a 
 *   scan.
 * 
 * For more info on STREAM, visit:
 * https://github.com/equalifyEverything/stream
 * 
 */

//======================================================================
// Testing Data
//======================================================================
// if(!defined('__ROOT__'))
//     define('__ROOT__', dirname(dirname(__FILE__)));
// require_once(__ROOT__.'/init.php'); 
// require_once(__ROOT__.'/helpers/scan_processor.php'); 
// $property_id = 1; 
// $jsonFilePath = __ROOT__.'/_dev/samples/stream-sample-1.json';
// $jsonData = json_decode(file_get_contents($jsonFilePath), true);
// scan_processor($jsonData, $property_id);

//======================================================================
// The Processsor
//======================================================================
function scan_processor($jsonData, $property_id, $debug = false){

    global $pdo;
    $logMessages = [];

    try {
        $pdo->beginTransaction();

        // Log start
        $logMessages[] = "Starting process_scans.php";

        // We aren't interested in pass data, since we'll 
        // get it when violations or errors are equalified.
        // We also want to get rid of any ophans because
        // we know a node is equalified when it isn't in
        // the JSON. We also remove tags so we don't fill
        // the DB with unnessary tags.
        trimPassDataAndOrphans($jsonData);

        // Count unique items in JSON for debugging - all
        // JSON counts should add up to added counts.
        countUniqueItemsInJson($jsonData);

        // Process content
        processUrl($pdo, $jsonData, $property_id);
        processMessages($pdo, $jsonData);
        processTags($pdo, $jsonData);
        processNodes($pdo, $jsonData);
        
        // After all processing is done, compare and update nodes
        compareAndUpdateNodes($pdo, $jsonData, $property_id);

        $pdo->commit();

        // Log end
        $logMessages[] = "Ending process_scans.php";

    } catch (Exception $e) {
        $pdo->rollBack();
        $logMessages[] = "Error: " . $e->getMessage();
    }

    // Return log messages if debug is true, otherwise return true to indicate success
    return $debug ? $logMessages : true;

}

//======================================================================
// Helper Functions
//======================================================================

// Remove pass data and ophans
function trimPassDataAndOrphans(&$jsonData) {
    $passTagIds = [];
    $passNodeIds = [];
    $allTagIds = [];
    $allNodeIds = [];

    // Ensure that messages, tags, and nodes keys exist in $jsonData
    $jsonData['messages'] = $jsonData['messages'] ?? [];
    $jsonData['tags'] = $jsonData['tags'] ?? [];
    $jsonData['nodes'] = $jsonData['nodes'] ?? [];

    // Step 1: Identify "pass" messages and collect their related tags and nodes
    foreach ($jsonData['messages'] as $index => $message) {
        if ($message['type'] === 'pass') {
            $passTagIds = array_merge($passTagIds, $message['relatedTagIds']);
            $passNodeIds = array_merge($passNodeIds, $message['relatedNodeIds']);
            unset($jsonData['messages'][$index]); // Remove "pass" message
        } else {
            $allTagIds = array_merge($allTagIds, $message['relatedTagIds']);
            $allNodeIds = array_merge($allNodeIds, $message['relatedNodeIds']);
        }
    }

    // Re-index messages after removing "pass" messages
    $jsonData['messages'] = array_values($jsonData['messages']);

    // Step 2: Remove exclusive "pass" related tags and nodes
    $jsonData['tags'] = array_filter($jsonData['tags'], function ($tag) use ($allTagIds) {
        return in_array($tag['tagId'], $allTagIds);
    });

    $jsonData['nodes'] = array_filter($jsonData['nodes'], function ($node) use ($allNodeIds) {
        return in_array($node['nodeId'], $allNodeIds);
    });

    // Step 3: Further trim tags and nodes that are not related to any messages
    $relatedTagIds = [];
    $relatedNodeIds = [];

    foreach ($jsonData['messages'] as $message) {
        $relatedTagIds = array_merge($relatedTagIds, $message['relatedTagIds']);
        $relatedNodeIds = array_merge($relatedNodeIds, $message['relatedNodeIds']);
    }

    $jsonData['tags'] = array_filter($jsonData['tags'], function ($tag) use ($relatedTagIds) {
        return in_array($tag['tagId'], $relatedTagIds);
    });

    $jsonData['nodes'] = array_filter($jsonData['nodes'], function ($node) use ($relatedNodeIds) {
        return in_array($node['nodeId'], $relatedNodeIds);
    });

    // Re-index tags and nodes arrays after filtering
    $jsonData['tags'] = array_values($jsonData['tags']);
    $jsonData['nodes'] = array_values($jsonData['nodes']);
}

// Count Unique Items in JSON
function countUniqueItemsInJson(array $jsonData) {
    // Initialize sets to track unique items
    $uniqueMessages = [];
    $uniqueTags = [];
    $uniqueNodes = [];
    $totalRelatedTags = 0;
    $totalRelatedNodes = 0;

    // Count the URL (assuming there's only one URL in the structure)
    $url = $jsonData['url'];

    // Count Unique Messages
    foreach ($jsonData['messages'] as $message) {
        $uniqueKey = md5($message['message'] . '|' . $message['type']);
        $uniqueMessages[$uniqueKey] = true; // Store in associative array to prevent duplicates
        
        // Sum up all related tags and nodes for each message
        $totalRelatedTags += count($message['relatedTagIds']);
        $totalRelatedNodes += count($message['relatedNodeIds']);
    }

    // Count Unique Tags
    foreach ($jsonData['tags'] as $tag) {
        $uniqueTags[$tag['tagId']] = true; // Assuming tagId is unique
    }

    // Count Unique Nodes
    foreach ($jsonData['nodes'] as $node) {
        $uniqueKey = md5($node['html'] . '|' . json_encode($node['targets']));
        $uniqueNodes[$uniqueKey] = true; // Use md5 hash of html and targets to identify uniqueness
    }

    // $logMessages[] = counts
    $logMessages[] = "- JSON has \"$url\" URL";
    $logMessages[] = "- JSON has " . count(array_filter($jsonData['messages'], fn($msg) => $msg['type'] === 'error')) . " Unique Error Messages";
    $logMessages[] = "- JSON has " . count(array_filter($jsonData['messages'], fn($msg) => $msg['type'] === 'pass')) . " Unique Pass Messages";
    $logMessages[] = "- JSON has " . count(array_filter($jsonData['messages'], fn($msg) => $msg['type'] === 'violation')) . " Unique Violation Messages";
    $logMessages[] = "- JSON has " . count($uniqueTags) . " Unique Tags";
    $logMessages[] = "- JSON has " . count($uniqueNodes) . " Unique Nodes";
    $logMessages[] = "- JSON has " . $totalRelatedTags . " Total Related Tags in All Messages";
    $logMessages[] = "- JSON has " . $totalRelatedNodes . " Total Related Nodes in All Messages";
}

// Process url.
function processUrl(PDO $pdo, array &$jsonData, $property_id) {

    // URL Is required, so if it isn't present we'll stop the script
    if(empty($jsonData['url'])){
        throw new Exception("No URL found!");
        exit;
    }

    $url = $jsonData['url'];
    $stmt = $pdo->prepare("SELECT url_id FROM urls WHERE url = :url AND url_property_id = :property_id");
    $stmt->execute(['url' => $url, 'property_id' => $property_id]);

    // Start for logging.
    $logging_message = '';

    // Give an existing id to an exisiting url.
    $urlId = $stmt->fetchColumn();

    if (!$urlId) {
        $insert = $pdo->prepare("INSERT INTO urls (url, url_property_id) VALUES (:url, :property_id)");
        $insert->execute(['url' => $url, 'property_id' => $property_id]);

        // Give a new id to a new url.
        $urlId = $pdo->lastInsertId();

        // Add logging message
        $logging_message = "New";
    }else{
        $logging_message = "Existing";
    }

    // Update tag in JSON
    $jsonData['urlId'] = $urlId;

    // Log count.
    $logMessages[] = "- Processed URL \"$url\" ($logging_message)";

}

// Process messages.
function processMessages(PDO $pdo, array &$jsonData) {
        
    $uniqueMessages = [];
    $typeCounts = [
        'error' => ['all' => 0, 'new' => 0],
        'pass' => ['all' => 0, 'new' => 0],
        'violation' => ['all' => 0, 'new' => 0],
    ];
    $urlId = $jsonData['urlId']; // Assuming this has been set earlier in your script

    // Check to see if there are messages.
    if(!empty($jsonData['messages'])){

        // Process messages
        foreach ($jsonData['messages'] as $key => $message) {
            $uniqueKey = md5($message['message'] . '|' . $message['type']);

            if (!isset($uniqueMessages[$uniqueKey])) {
                $uniqueMessages[$uniqueKey] = true;
                
                $stmt = $pdo->prepare("SELECT message_id FROM messages WHERE message = :message AND message_type = :type");
                $stmt->execute(['message' => $message['message'], 'type' => $message['type']]);
                $messageId = $stmt->fetchColumn();

                $typeCounts[$message['type']]['all']++;

                if (!$messageId) {
                    $insert = $pdo->prepare("INSERT INTO messages (message, message_type) VALUES (:message, :type)");
                    $insert->execute(['message' => $message['message'], 'type' => $message['type']]);
                    $messageId = $pdo->lastInsertId();

                    $typeCounts[$message['type']]['new']++;
                }

                // Update JSON with the database message ID for reference.
                $jsonData['messages'][$key]['messageId'] = $messageId;
            }
        }

        // Log counts for each type.
        foreach ($typeCounts as $type => $counts) {
            $logMessages[] = "- Processed {$counts['all']} $type Messages ({$counts['new']} New)";
        }

    // Log if no messages.
    }else{
        $logMessages[] = "- No messages to process.";
    }

}

// Process tags.
function processTags(PDO $pdo, array &$jsonData) {

    $tagIdMap = []; // Map JSON tag IDs to actual database tag IDs
    $allTagsCounter = 0;
    $newTagsCounter = 0;
    $processedRelationshipsCounter = 0;

    // Check to see if there are tags.
    if(!empty($jsonData['tags'])){

        // Process tags.
        foreach ($jsonData['tags'] as $key => $tag) {
            $allTagsCounter++;
            $stmt = $pdo->prepare("SELECT tag_id FROM tags WHERE tag = :tag");
            $stmt->execute(['tag' => $tag['tag']]);
            $tagId = $stmt->fetchColumn();

            if (!$tagId) {
                $insert = $pdo->prepare("INSERT INTO tags (tag) VALUES (:tag)");
                $insert->execute(['tag' => $tag['tag']]);
                $tagId = $pdo->lastInsertId();
                $newTagsCounter++;
            }

            $tagIdMap[$tag['tagId']] = $tagId;
            $jsonData['tags'][$key]['tagId'] = $tagId; // Update JSON structure with actual tag ID
        }

        foreach ($jsonData['messages'] as $message) {
            if (!empty($message['relatedTagIds'])) {
                foreach ($message['relatedTagIds'] as $oldTagId) {
                    $tagId = $tagIdMap[$oldTagId] ?? null; // Get the actual tag ID
                    if ($tagId) {
                        // Attempt to relate the tag to the message
                        $insertRel = $pdo->prepare("INSERT IGNORE INTO message_tags (message_id, tag_id) VALUES (:message_id, :tag_id)");
                        $success = $insertRel->execute(['message_id' => $message['messageId'], 'tag_id' => $tagId]);
                        if ($success) $processedRelationshipsCounter++;
                    }
                }
            }
        }

        $logMessages[] = "- Processed $allTagsCounter Tags ($newTagsCounter New)";
        $logMessages[] = "- Processed $processedRelationshipsCounter New Message+Tag Relationships";

    }else{
        $logMessages[] = "- No tags to process.";
    }
}

// Process nodes.
function processNodes(PDO $pdo, array &$jsonData) {
    $nodeIdMap = []; // Map JSON node IDs to actual DB node IDs.
    $allNodesCounter = 0;
    $newNodesCounter = 0;
    $newNodeMessageRelationsCounter = 0;

    $urlId = $jsonData['urlId']; // Assumed to be set from a previous function call.

    // Check to see if there are nodes to process.
    if(!empty($jsonData['nodes'])){

        // Process and add new nodes, updating JSON node IDs with actual DB IDs.
        foreach ($jsonData['nodes'] as $key => &$node) {
            $allNodesCounter++;
            // Query to check if node exists by comparing HTML and targets, ensuring uniqueness.
            $stmt = $pdo->prepare("SELECT node_id FROM nodes WHERE node_html = :node_html AND node_targets = :node_targets AND node_url_id = :node_url_id");
            $stmt->execute([
                'node_html' => $node['html'],
                'node_targets' => json_encode($node['targets']),
                'node_url_id' => $urlId
            ]);
            $nodeId = $stmt->fetchColumn();

            if (!$nodeId) {
                // Node does not exist, add new node.
                $insert = $pdo->prepare("INSERT INTO nodes (node_html, node_targets, node_url_id) VALUES (:node_html, :node_targets, :node_url_id)");
                $insert->execute([
                    'node_html' => $node['html'],
                    'node_targets' => json_encode($node['targets']),
                    'node_url_id' => $urlId
                ]);
                $nodeId = $pdo->lastInsertId();
                $newNodesCounter++;

                // Since this is a new node, add it to node_updates marking as not equalified
                $insertUpdate = $pdo->prepare("INSERT INTO node_updates (node_id, node_equalified, update_date) VALUES (:node_id, 0, NOW())");
                $insertUpdate->execute(['node_id' => $nodeId]);
            }

            $nodeIdMap[$node['nodeId']] = $nodeId;
            $node['nodeId'] = $nodeId; // Update node ID in JSON to reflect the actual DB node ID.
        }

        // Relate nodes to messages.
        foreach ($jsonData['messages'] as $message) {
            if (!empty($message['relatedNodeIds'])) {
                foreach ($message['relatedNodeIds'] as $oldNodeId) {
                    $newNodeId = $nodeIdMap[$oldNodeId] ?? null;
                    if ($newNodeId) {
                        // Insert new node-message relationship, if not exists.
                        $insertRel = $pdo->prepare("INSERT IGNORE INTO message_nodes (message_id, node_id) VALUES (:message_id, :node_id)");
                        $insertRel->execute(['message_id' => $message['messageId'], 'node_id' => $newNodeId]);
                        $newNodeMessageRelationsCounter++;
                    }
                }
            }
        }

        $logMessages[] = "- Processed $allNodesCounter Nodes ($newNodesCounter New)";
        $logMessages[] = "- Processed $newNodeMessageRelationsCounter Message+Node Relationships";

    }else{
        $logMessages[] = "- No nodes to process.";
    }

}

// Compare and update nodes.
function compareAndUpdateNodes(PDO $pdo, array $jsonData, $property_id) {
    $urlId = $jsonData['urlId'];

    // Counters for different node statuses
    $equalifiedNodesCount = 0;
    $unequalifiedNodesCount = 0;

    // Get all node_ids associated with the URL directly from the nodes table now.
    $stmt = $pdo->prepare("SELECT n.node_id, n.node_equalified
        FROM nodes n
        JOIN urls u ON n.node_url_id = u.url_id
        WHERE u.url_id = :url_id AND u.url_property_id = :property_id");
    $stmt->execute(['url_id' => $urlId, 'property_id' => $property_id]);
    $dbNodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert JSON nodeIds to a simple array for comparison
    $jsonNodeIds = !empty($jsonData['nodes']) ? array_map(function($node) {
        return $node['nodeId'];
    }, $jsonData['nodes']) : [];

    foreach ($dbNodes as $dbNode) {
        $dbNodeId = $dbNode['node_id'];
        $isNodeEqualified = $dbNode['node_equalified'];

        // Check for status change or first-time equalifying
        if (!in_array($dbNodeId, $jsonNodeIds) && !$isNodeEqualified) {
            // Node is now equalified but wasn't before
            $updateStmt = $pdo->prepare("UPDATE nodes SET node_equalified = 1 WHERE node_id = :node_id");
            $updateStmt->execute(['node_id' => $dbNodeId]);
            $equalifiedNodesCount++;

            // Add entry to node_updates
            $insert = $pdo->prepare("INSERT INTO node_updates (node_id, node_equalified, update_date) VALUES (:node_id, 1, NOW())");
            $insert->execute(['node_id' => $dbNodeId]);
        } elseif (in_array($dbNodeId, $jsonNodeIds) && $isNodeEqualified) {
            // Node is present in JSON but was previously marked as equalified
            $updateStmt = $pdo->prepare("UPDATE nodes SET node_equalified = 0 WHERE node_id = :node_id");
            $updateStmt->execute(['node_id' => $dbNodeId]);
            $unequalifiedNodesCount++;

            // Add entry to node_updates
            $insert = $pdo->prepare("INSERT INTO node_updates (node_id, node_equalified, update_date) VALUES (:node_id, 0, NOW())");
            $insert->execute(['node_id' => $dbNodeId]);
        }
    }

    // $logMessages[] = out the totals
    $logMessages[] = "- Nodes Equalified: $equalifiedNodesCount";
    $logMessages[] = "- Nodes Un-equalified: $unequalifiedNodesCount";
}
?>