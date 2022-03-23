<?php
// TODO: Make property Object Oriented instead of Procedural
/**
 * Connect to DB
 */
function connect($hostname, $username, $password, $database){
    // TODO: Refactor how DB connects so that `$db` doesn't neet to be called in every function.
    $db = new mysqli(
        $hostname, 
        $username, 
        $password,
        $database
    );
    mysqli_set_charset($db, 'utf8mb4');
    if($db->connect_error){
        throw new Exception('<p>Cannot connect to database: '
            . $db->connect_error . "<br>"
            . $db->connect_errorno . '</p>'
        );
    }
    return $db;
}

/**
 * Get All Properties
 * @param $filter limits the query
 */
function get_properties(mysqli $db, $filter = ''){

    // SQL
    $sql = 'SELECT * FROM `properties`';
    if($filter == 'parents')
        $sql.= ' WHERE `parent` = ""';

    // Query
    $results = $db->query($sql);

    // Result
    $data = [];
    if($results->num_rows > 0){
        while($row = $results->fetch_object()){
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get Events
 */
function get_events(mysqli $db){

    // SQL
    $sql = 'SELECT * FROM `events` ORDER BY STR_TO_DATE(`time`,"%Y-%m-%d %H:%i:%s")';

    // Query
    $results = $db->query($sql);

    // Result
    $data = [];
    if($results->num_rows > 0){
        while($row = $results->fetch_object()){
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get Events by property
 */
function get_events_by_property(mysqli $db, $property_id){

    // SQL
    $sql = 'SELECT * FROM `events` WHERE `property_id` = '.$property_id;

    // Query
    $results = $db->query($sql);

    // Result
    $data = [];
    if($results->num_rows > 0){
        while($row = $results->fetch_object()){
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get Alerts
 */
function get_alerts(mysqli $db){

    // SQL
    $sql = 'SELECT * FROM `alerts` ORDER BY STR_TO_DATE(`time`,"%Y-%m-%d %H:%i:%s")';

    // Query
    $results = $db->query($sql);

    // Result
    $data = [];
    if($results->num_rows > 0){
        while($row = $results->fetch_object()){
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get Alerts
 */
function get_alerts_by_property(mysqli $db, $property_id){

    // SQL
    $sql = 'SELECT * FROM `alerts` WHERE `property_id` = '.$property_id;

    // Query
    $results = $db->query($sql);

    // Result
    $data = [];
    if($results->num_rows > 0){
        while($row = $results->fetch_object()){
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get Account Info
 */
function get_account(mysqli $db, $id){

    // SQL
    $sql = 'SELECT * FROM accounts WHERE id = '.$id;

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object();

    // Result
    return $data;
}

/**
 * Get Property Records
 */
function get_property(mysqli $db, $id){

    // SQL
    $sql = 'SELECT * FROM properties WHERE id = "'.$id.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object();

    // Result
    return $data;
    
}

/**
 * Get Property ID
 */
function get_property_id(mysqli $db, $url){

    // SQL
    $sql = 'SELECT id FROM properties WHERE url = "'.$url.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->id;

    // Result
    return $data;
    
}

/**
 * Get Property URL
 */
function get_property_url(mysqli $db, $id){

    // SQL
    $sql = 'SELECT `url` FROM properties WHERE `id` = "'.$id.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->url;

    // Result
    return $data;
    
}

/**
 * Get property Children
 */
function get_property_children(mysqli $db, $parent_url){

    // SQL
    $sql = 'SELECT * FROM `properties` WHERE `parent` = "'.$parent_url.'"';

    // Query
    $results = $db->query($sql);

    // Result
    $data = [];
    if($results->num_rows > 0){
        while($row = $results->fetch_object()){
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Insert property
 */
function is_unique_property_url(mysqli $db, $property_url){

    // Require unique URL
    $url_sql = 'SELECT * FROM properties WHERE url = "'.$property_url.'"';
    $url_query = $db->query($url_sql);
    if(mysqli_num_rows($url_query) > 0){
        return false;
    }else{
        return true;
    }
}

/**
 * Insert Properties
 */
function insert_properties(mysqli $db, $properties_records){

    // Create SQL
    $sql = 'INSERT INTO `properties` (`parent`, `url`, `wcag_errors`) VALUES';
    
    // Insert Each Record
    $record_count = count($properties_records);
    $record_iteration = 0;
    foreach ($properties_records as $record){

        // SQL
        $sql.= "(";
        $sql.= "'".$record['parent']."',";
        $sql.= "'".$record['url']."',";
        $sql.= "'".$record['wcag_errors']."'";
        $sql.= ")";
        if(++$record_iteration != $record_count)
            $sql.= ",";

    }
    $sql.= ";";
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert properties for user '.USER_ID);
    $record['id']->insert_id;
    return $record;
}

/**
 * Update Account
 */
function update_account(mysqli $db, array $record){

    // SQL
    $sql = "UPDATE `accounts` SET ";
    $sql.= "property_unreachable_alert = '".$record['property_unreachable_alert']."',";
    $sql.= "wcag_2_1_page_error_alert = '".$record['wcag_2_1_page_error_alert']."',";
    $sql.= "email_site_owner = '".$record['email_site_owner']."',";
    $sql.= "scan_frequency = '".$record['scan_frequency']."',";
    $sql.= "accessibility_testing_service = '".$record['accessibility_testing_service']."',";
    $sql.= "wave_key = '".$record['wave_key']."'";
    $sql.= " WHERE id = ".USER_ID.";";

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot insert account.');
    $record['id']->insert_id;
    return $record;
}

/**
 * Archive Property
 */
function archive_property(mysqli $db, $property_id){
    
    // SQL
    $sql = 'UPDATE `properties` SET `status` = "archived" WHERE `id` = "'.$property_id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot archive property '.$parent_id);
}

/**
 * Delete Property Children
 */
function archive_property_children(mysqli $db, $parent_id){
    
    // Get URL of parent
    $parent = get_property_url($db, $parent_id);

    // SQL
    $sql = 'UPDATE `properties` SET `status` = "archived" WHERE parent = "'.$parent.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot archive children of property '.$parent_id.'.');
}

/**
 * Subtract Account Credits
 */
function subtract_account_credits(mysqli $db, $id, $credits){
    
    // SQL
    $sql = 'UPDATE `accounts` SET credits = credits - '.$credits.' WHERE id = '.$id;
    $delete_pages_sql = 'DELETE FROM `pages` WHERE property_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete property.');
}

/**
 * The WCAG Report URL
 */
function the_wcag_report_URL($db, $property_url){
    $account_info = get_account($db, USER_ID);
    if($account_info->accessibility_testing_service == 'Little Forrest'){
        echo 'https://inspector.littleforest.co.uk/InspectorWS/Inspector?url='.$property_url;
    }elseif($account_info->accessibility_testing_service == 'WAVE'){
        echo 'https://wave.webaim.org/report#/'.$property_url;
    }else{
        return null;
    }
    
}

/**
 * The Property URI
 */
function the_property_view_uri(mysqli $db, $property_id){

    // Set $property
    $property = get_property($db, $property_id);

    // Set URL
    if($property->parent == ''){
        echo '?view=property_details&id='.$property->id;
    }elseif(!empty($property->parent)){
        echo '?view=property_details&id='.get_property_id($db, $property->parent);
    }else{
        return false;
    }
    
}