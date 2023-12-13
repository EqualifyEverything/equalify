<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Change the meta information. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/


// Add DB Info
require_once '../init.php';
require_once '../models/db.php';

// We need the last view, so we can redirect on success.
if(!empty($_POST['last_view'])){
    $last_view = $_POST['last_view'];
}else{

    // If no view is specified, we'll return to scan profiles.
    $last_view = 'scan_profiles';

}

// TODO: update logic, so the $_POST parameters are
// set in the integrations file. See Github Issue #12.
$account_records = [];
if(isset($_POST['a11ywatch_key'])){
    DataAccess::update_meta_value('a11ywatch_key', $_POST['a11ywatch_key']);
};
if(isset($_POST['axe_uri'])){
    DataAccess::update_meta_value('axe_uri', $_POST['axe_uri']);
};
if(isset($_POST['wave_key'])){
    DataAccess::update_meta_value('wave_key', $_POST['wave_key']);
};

header('Location: ../index.php?view='.$last_view.'&status=success');