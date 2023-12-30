<?php
// The tag_id URL parameter defines the tag.
$tag_id = $_GET['tag_id'];
if($tag_id == ''){
    throw new Exception(
        'tag_id is missing'
    );
}

// The DB can be used by required info
require_once('db.php');

// Let's get the various components we need to create the view.
require_once('components/title.php');
require_once('components/chart.php');
require_once('components/message_list.php');


?>

<?php
// Page Title
the_title($tag_id, 'tag');

// Chart component.
the_chart('tag_ids=33');

// Message Occurrences
the_message_list("tags=$tag_id");
?>