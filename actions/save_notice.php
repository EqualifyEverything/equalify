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

// First, let's create an array that we'll use to update 
// the notice from.

$updated_notice = array();

// The array is populated with URL parameters.
if(!empty($_POST)){
    foreach ($_POST as $key => $value){

        // We'll push every value but the name,
        // which receive special treatment later.
        if($key != 'name' && !empty($value))
            array_push(
                $updated_notice,
                array(
                    'name' => $key,
                    'value' => strip_tags($value)
                )
            );

    }
    echo $updated_notice;
}

// Depending on if the name is present, we'll either save
// or update the report.
if(empty($_POST['name'])){

    // No ID means we need to generate an id by counting
    // all the rows in notices 
    $notice_id = 'notice_'.bin2hex(openssl_random_pseudo_bytes(8));
    // $notice_id = '23';


    // Now we can create the notice.
    $fields = array(
        array(
            'name' => 'id',
            'value' => $notice_id
        ),
        array(
            'name' => 'message',
            'value' => serialize('this is a test message')
        )
    );
    DataAccess::add_db_entry('notices', $fields);

}else{

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
            'name' => 'notice_name',
            'value' => $_POST['name']
        ),
        array(
            'name' => 'message',
            'value' => serialize('this is a test message if the fields empty')
        )
    );
    DataAccess::update_db_rows(
        'notices', $fields, $filtered_to_notice
    );

    // And let's set the name with the post variable.
    $notice_id = $_POST['name'];

}

// When done, we can checkout the saved report.
// header('Location: ../index.php?view=notice_editor&notice_id='.$notice_id);
header('Location: ../index.php?view=notice_editor&status=success&notice_id='.$notice_id);

?>
</pre>