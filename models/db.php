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
        die('<p>Cannot connect to database: '
            . $db->connect_error . "<br>"
            . $db->connect_errorno . '</p>'
        );
    }
    return $db;
}

/**
 * Get All Properties
 */
function get_properties(mysqli $db){

    // SQL
    $sql = 'SELECT * FROM `properties`';

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
 * Get property Records
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
 * Get property ID
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
 * Get property Title
 */
function get_property_title(mysqli $db, $id){

    // SQL
    $sql = 'SELECT title FROM properties WHERE id = "'.$id.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->title;

    // Result
    return $data;
    
}

/**
 * Get property Pages
 */
function get_property_pages(mysqli $db, $property_id){

    // SQL
    $sql = 'SELECT * FROM `pages` WHERE `property_id` = '.$property_id;

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
 * Insert property
 */
function insert_property(mysqli $db, array $record){

    // SQL
    $sql = "INSERT INTO `properties` ";
    $sql.= "(`url`)";
    $sql.= " VALUES ";
    $sql.= "(";
    $sql.= "'".$record['url']."'";
    $sql.= ");";
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert property.');
    $record['id']->insert_id;
    return $record;
}

/**
 * Insert Page
 */
function insert_page(mysqli $db, array $record){

    // SQL
    $sql = "INSERT INTO `pages` ";
    $sql.= "(`property_id`, `url`, `wcag_errors`)";
    $sql.= " VALUES ";
    $sql.= "(";
    $sql.= "'".$record['property_id']."',";
    $sql.= "'".$record['url']."',";
    $sql.= "'".$record['wcag_errors']."'";
    $sql.= ");";

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot insert pages.');
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
 * Delete property
 */
function delete_property(mysqli $db, $id){
    
    // SQL
    $sql = 'DELETE FROM `properties` WHERE id = "'.$id.'"';
    $delete_pages_sql = 'DELETE FROM `pages` WHERE property_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete property.');
}

/**
 * Delete property Pages
 */
function delete_property_pages(mysqli $db, $id){
    
    // SQL
    $sql = 'DELETE FROM `pages` WHERE property_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete pages.');
}

/**
 * Delete property Events
 */
function delete_property_events(mysqli $db, $id){
    
    // SQL
    $sql = 'DELETE FROM `events` WHERE property_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete events.');
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