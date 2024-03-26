<?php
// Set content type
header('Content-Type: application/json');

// Add helper functions
require_once('init.php');

// Require url_id parameter
if( isset($_GET['urlIds']) && !empty($_GET['urlIds'])){
    $urlIds = explode(',', $_GET['urlIds']);
}else{
    http_response_code(400);
    echo json_encode(['error' => 'urlIds required']);
    exit;
}

// Fetch the URL
$urlsIn = str_repeat('?,', count($urlIds) - 1) . '?';

// Fetch URLs
$sqlUrls = "SELECT url_id AS urlId, url FROM urls WHERE url_id IN ($urlsIn)";
$stmtUrls = $pdo->prepare($sqlUrls);
$stmtUrls->execute($urlIds);
$urls = $stmtUrls->fetchAll(PDO::FETCH_ASSOC);

// Require at least one URL to be found
if( empty($urls)){
    http_response_code(400);
    echo json_encode(['error' => 'No URLs found with that ID.']);
    exit;
}

// Fetch Nodes related to URLs
$sqlNodes = "SELECT node_id AS nodeId, node_html AS html, node_targets AS targets, node_url_id AS relatedUrlId, node_equalified AS equalified FROM nodes WHERE node_url_id IN ($urlsIn)";
$stmtNodes = $pdo->prepare($sqlNodes);
$stmtNodes->execute($urlIds);
$nodes = $stmtNodes->fetchAll(PDO::FETCH_ASSOC);
if(!empty($nodes)){
    foreach ($nodes as &$node) {
        // Escape HTML and tagets
        $node['html'] = addslashes($node['html']);
        $node['targets'] = json_decode($node['targets']);
        if(!empty($node['targets'])){
            foreach ($node['targets'] as &$target) {
                $target = addslashes($target);
            }
            unset($target);
        }
    }
    unset($node);
}
$nodeIds = array_column($nodes, 'nodeId');

// Fetch Messages related to Nodes
$nodeIdsIn = str_repeat('?,', count($nodeIds) - 1) . '?';
$sqlMessages = "SELECT m.message_id, m.message, m.message_type AS type, mn.node_id 
                FROM messages m 
                INNER JOIN message_nodes mn ON m.message_id = mn.message_id 
                WHERE mn.node_id IN ($nodeIdsIn)";
$stmtMessages = $pdo->prepare($sqlMessages);
$stmtMessages->execute($nodeIds);
$messagesRaw = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);
$messages = [];

// Re-organize messages to include necessary data
foreach ($messagesRaw as $message) {
    if (!isset($messages[$message['message_id']])) {
        $messages[$message['message_id']] = [
            'message' => $message['message'],
            'type' => $message['type'],
            'relatedTagIds' => [],
            'relatedNodeIds' => [],
        ];
    }
    $messages[$message['message_id']]['relatedNodeIds'][] = $message['node_id'];
}

// Fetch Tags related to Messages
$messageIds = array_keys($messages);
$messageIdsIn = str_repeat('?,', count($messageIds) - 1) . '?';
$sqlTags = "SELECT t.tag_id AS tagId, t.tag, mt.message_id 
            FROM tags t 
            INNER JOIN message_tags mt ON t.tag_id = mt.tag_id 
            WHERE mt.message_id IN ($messageIdsIn)";
$stmtTags = $pdo->prepare($sqlTags);
$stmtTags->execute($messageIds);
$tagsRaw = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
$tags = [];

foreach ($tagsRaw as $tag) {
    if (!isset($tags[$tag['tagId']])) {
        $tags[$tag['tagId']] = [
            'tagId' => $tag['tagId'],
            'tag' => $tag['tag'],
        ];
    }
    $messages[$tag['message_id']]['relatedTagIds'][] = $tag['tagId'];
}

// Finalize messages array
$messages = array_values($messages); // Reset keys

// Prepare the final structure
$output = [
    "urls" => $urls,
    "nodes" => $nodes,
    "messages" => $messages,
    "tags" => array_values($tags), // Ensure tags are properly formatted
];

// Return as JSON
echo json_encode($output);