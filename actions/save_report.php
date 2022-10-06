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

// First let's create an array that we'll use to update 
// the meta from.
$updated_meta = array();

// The array is populated with URL parameters.
if(!empty($_POST)){
    foreach ($_POST as $key => $value){

        // We'll push every filled value but the name,
        // which receive special treatment later.
        if($key != 'name' && !empty($value))
            array_push(
                $updated_meta,
                array(
                    'name' => $key,
                    'value' => strip_tags($value)
                )
            );

    }
}

// Depending on if the name is present, we'll either save
// or update the report.
if(empty($_POST['name'])){

    // No ID means we need to generate an id by counting
    // all the rows in meta 
    $name = 'report_'.DataAccess::count_db_rows('meta');

    // Now we can create the meta.
    $fields = array(
        array(
            'name' => 'meta_name',
            'value' => $name
        ),
        array(
            'name' => 'meta_value',
            'value' => serialize($updated_meta)
        )
    );
    DataAccess::add_db_entry('meta', $fields);

}else{

    // Otherwise we can update the fields.
    $fields = array(
        array(
            'name' => 'meta_value',
            'value' => serialize($updated_meta)
        )
    );

    // All fields are filtered to the current post.
    $filtered_to_report = array(
        array(
            'name' => 'meta_name',
            'value' => $_POST['name']
        )
    );
    DataAccess::update_db_rows(
        'meta', $fields, $filtered_to_report
    );

    // And let's set the name with the post variable.
    $name = $_POST['name'];

}

// When done, we can checkout the saved report.
header('Location: ../index.php?view=reports&status=success&report='.$name);

?>
</pre>