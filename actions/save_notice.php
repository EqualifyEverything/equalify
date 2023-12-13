<pre>
<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Let's save a report!
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
 **********************************************************/

// We're going to use the DB in this document.
require_once '../config.php';
require_once '../models/db.php';

session_start();

// First let's check if this is a new notice or an existing one

if (empty($_SESSION['notice_id'])) {
    // Archived will be removed when added to view
    $archived =  array(
        'name' => 'archived',
        'value' => 0
    );
    $source = 'manually_created';
} else {
    $source = $_SESSION['source'];
    $notice_id = $_SESSION['notice_id'];
}
// Let's set up error handling for incomplete user defined fields notice forms

$status = $_POST['status'];
if ($status == false)
    throw new Exception('Property status is missing.');

$related_url = $_POST['related_url'];
if ($related_url == false)
    throw new Exception('Property related_url is missing.');

$property_id = $_POST['property_id'];
if ($property_id == false)
    throw new Exception('Property property_id is missing.');

if (isset($_POST['notice_id']))
    $notice_id = $_POST['notice_id'];

$archived = isset($_POST['archived']) ? $_POST['archived'] : 0;
$archived = $archived ? 1 : 0;

if (isset($_POST['message']))
    $message = $_POST['message'];

if (isset($_POST['tags']))
    $tags = $_POST['tags'];

// Catching all meta posts
if (isset($_POST['meta_code_snippet'])) {
    $meta['code_snippets'] = $_POST['meta_code_snippet'];
}

if (isset($_POST['meta_more_info_url'])) $meta['more_info_url'] = $_POST['meta_more_info_url'];

if (isset($_POST['meta_notes'])) $meta['notes'] = $_POST['meta_notes'];


$fields = array(
    array(
        'name' => 'message',
        'value' => $message
    ),
    array(
        'name' => 'status',
        'value' => $status
    ),
    array(
        'name' => 'related_url',
        'value' => $related_url
    ),
    array(
        'name' => 'property_id',
        'value' => $property_id
    ),

    array(
        'name' => 'source',
        'value' => $source
    ),

    array(
        'name' => 'archived',
        'value' => $archived
    ),

    array(
        'name' => 'meta',
        'value' => serialize($meta)
    )
);

// Check for session ID to add values for new notices
if (empty($_SESSION['notice_id'])) {
    DataAccess::add_db_entry(
        'notices',
        $fields
    );
    unset($_SESSION['notice_id']);
    unset($_SESSION['source']);
} else {
    // Use session ID to update db 
    $filtered_to_notice = array(
        array(
            'name' => 'id',
            'value' => $_SESSION['notice_id']
        ),
    );
    DataAccess::update_db_rows(
        'notices',
        $fields,
        $filtered_to_notice
    );
    unset($_SESSION['notice_id']);
    unset($_SESSION['source']);
}

// When done, we can checkout the saved notice.
header('Location: ../index.php?view=reports&status=success');

?>
</pre>