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
    echo json_encode(['error' => 'url_ids required']);
    exit;
}

// Fetch the URL
$urlsIn = str_repeat('?,', count($urlIds) - 1) . '?';

// Fetch URLs
$sqlUrls = "SELECT url_id, url FROM urls WHERE url_id IN ($urlsIn)";
$stmtUrls = $pdo->prepare($sqlUrls);
$stmtUrls->execute($urlIds);
$urls = $stmtUrls->fetchAll(PDO::FETCH_ASSOC);

// Fetch Nodes related to URLs
$sqlNodes = "SELECT node_id, node_html AS html, node_targets AS targets, node_url_id, node_equalified AS equalified FROM nodes WHERE node_url_id IN ($urlsIn)";
$stmtNodes = $pdo->prepare($sqlNodes);
$stmtNodes->execute($urlIds);
$nodes = $stmtNodes->fetchAll(PDO::FETCH_ASSOC);

$nodeIds = array_column($nodes, 'node_id');

// Fetch Messages related to Nodes
$nodeIdsIn = str_repeat('?,', count($nodeIds) - 1) . '?';
$sqlMessages = "SELECT m.message_id, m.message, m.message_type AS type, mn.node_id 
                FROM messages m 
                INNER JOIN message_nodes mn ON m.message_id = mn.message_id 
                WHERE mn.node_id IN ($nodeIdsIn)";
$stmtMessages = $pdo->prepare($sqlMessages);
$stmtMessages->execute($nodeIds);
$messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);

// Fetch Tags related to Messages
$messageIds = array_column($messages, 'message_id');
$messageIdsIn = str_repeat('?,', count($messageIds) - 1) . '?';
$sqlTags = "SELECT t.tag_id, t.tag, mt.message_id 
            FROM tags t 
            INNER JOIN message_tags mt ON t.tag_id = mt.tag_id 
            WHERE mt.message_id IN ($messageIdsIn)";
$stmtTags = $pdo->prepare($sqlTags);
$stmtTags->execute($messageIds);
$tags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

// Organize Messages with related Tag IDs
foreach ($messages as &$message) {
    $message['relatedTagIds'] = array_column(array_filter($tags, function ($tag) use ($message) {
        return $tag['message_id'] == $message['message_id'];
    }), 'tag_id');
    $message['relatedNodeIds'] = array($message['node_id']);
    unset($message['node_id'], $message['message_id']); // Clean up
}
unset($message); // Break the reference with the last element

// Organize Nodes with related URL IDs
foreach ($nodes as &$node) {
    $node['relatedUrlIds'] = array($node['node_url_id']);
    unset($node['node_url_id']); // Clean up
    $node['equalified'] = $node['equalified'] == 1; // Convert to boolean
}
unset($node); // Break the reference with the last element

// Prepare the final structure
$output = [
    "urls" => $urls,
    "nodes" => $nodes,
    "messages" => $messages,
    "tags" => array_map(function ($tag) {
        unset($tag['message_id']); // Clean up
        return $tag;
    }, $tags),
];

// Return as JSON
echo json_encode($output);