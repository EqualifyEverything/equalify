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

// These items are required but will be assigned 

// $id = $_POST['id'];
// if ($id == false)
//     throw new Exception('Property id is missing.');
// $archived = $_POST['archived'];
// if ($archived == false)
//     throw new Exception('Property archived is missing.');

// $source = $_POST['source'];
// if ($source == false)
//     throw new Exception('Property source is missing.');

// Let's set up the nonrequired variables

if (isset($_POST['notice_id'])) {
    $notice_id = $_POST['notice_id'];
}
if (isset($_POST['message'])) {
    $message = $_POST['message'];
}
if (isset($_POST['tags'])) {
    $tags = $_POST['tags'];
}
// Catching all meta posts
if (isset($_POST['meta_code_snippet'])) {
    $meta['code_snippets'] = $_POST['meta_code_snippet'];
}
if (isset($_POST['meta_more_info_url'])) {
    $meta['more_info_url'] = $_POST['meta_more_info_url'];
}
if (isset($_POST['meta_notes'])) {
    $meta['notes'] = $_POST['meta_notes'];
}

$updated_notice = [];

// The array is populated with URL parameters.
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {

        // We'll push every value but the name,
        // which receive special treatment later.
        if ($key != 'name' && !empty($value))
            array_push(
                $updated_notice,
                array(
                    'name' => $key,
                    'value' => strip_tags($value)
                )
            );
    }
    // print_r($updated_notice);
}

// Depending on if the name is present, we'll either save
// or update the report.
if (empty($_POST['notice_id'])) {

    $source = 'manually_created';
    $archived = 0;
    // Now we can create the notice.
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
            'name' => 'source',
            'value' => $source
        ),
        array(
            'name' => 'property_id',
            'value' => $property_id
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
    DataAccess::add_db_entry(
        'notices',
        $fields
    );
} else {

    // Otherwise, we can update the fields.
    $fields = array(
        array(
            'name' => 'notice_value',
            'value' => serialize($updated_notice)
        )
    );

    // All fields are filtered to the current post.
    $filtered_to_notice = array(
        array(
            'name' => 'id',
            'value' => $notice_id
        ),
    );
    DataAccess::update_db_rows(
        'notices',
        $fields,
        $filtered_to_notice
    );
}

// When done, we can checkout the saved notice.
header('Location: ../index.php?view=reports&status=success');

?>
</pre>