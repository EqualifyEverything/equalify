<?php
// TODO: Make site Object Oriented instead of Procedural

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
 * Get All Sites
 */
function get_all_sites(mysqli $db){

    // SQL
    $sql = 'SELECT * FROM `sites`';

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
 * Get All Pages
 */
function get_all_pages(mysqli $db, $site_id){

    // SQL
    $sql = 'SELECT * FROM `pages` WHERE `site_id` = '.$site_id;

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
function get_all_alerts(mysqli $db){

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
 * Get Alerts by Site
 */
function get_alerts_by_site(mysqli $db, $site_id){

    // SQL
    $sql = 'SELECT * FROM `alerts` WHERE `site_id` = '.$site_id;

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
function get_account_info(mysqli $db, $id){

    // SQL
    $sql = 'SELECT * FROM accounts WHERE id = '.$id;

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object();

    // Result
    return $data;
}

/**
 * Get Site ID
 */
function get_site_id(mysqli $db, $url){

    // SQL
    $sql = 'SELECT id FROM sites WHERE url = "'.$url.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->id;

    // Result
    return $data;
    
}

/**
 * Get Site URL
 */
function get_site_url(mysqli $db, $id){

    // SQL
    $sql = 'SELECT `url` FROM sites WHERE id = "'.$id.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->url;

    // Result
    return $data;
    
}

/**
 * Insert Site
 */
function insert_site(mysqli $db, array $record){

    // Require unique URL
    $url_sql = 'SELECT * FROM sites WHERE url = "'.$record['url'].'"';
    $url_query = $db->query($url_sql);
    if(mysqli_num_rows($url_query) > 0)
        throw new Exception('Site URL is aleady added.');

    // SQL
    $sql = "INSERT INTO `sites` ";
    $sql.= "(`url`)";
    $sql.= " VALUES ";
    $sql.= "(";
    $sql.= "'".$record['url']."'";
    $sql.= ");";
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert site.');
    $record['id']->insert_id;
    return $record;
}

/**
 * Insert Page
 */
function insert_page(mysqli $db, array $record){

    // SQL
    $sql = "INSERT INTO `pages` ";
    $sql.= "(`site_id`, `url`, `wcag_errors`)";
    $sql.= " VALUES ";
    $sql.= "(";
    $sql.= "'".$record['site_id']."',";
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
    $sql.= "site_unreachable_alert = '".$record['site_unreachable_alert']."',";
    $sql.= "wcag_2_1_page_error_alert = '".$record['wcag_2_1_page_error_alert']."',";
    $sql.= "testing_frequency = '".$record['testing_frequency']."',";
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
 * Delete Site
 */
function delete_site(mysqli $db, $id){
    
    // SQL
    $sql = 'DELETE FROM `sites` WHERE id = "'.$id.'"';
    $delete_pages_sql = 'DELETE FROM `pages` WHERE site_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete site.');
}

/**
 * Delete Site Pages
 */
function delete_site_pages(mysqli $db, $id){
    
    // SQL
    $sql = 'DELETE FROM `pages` WHERE site_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete pages.');
}

/**
 * Delete Site Alerts
 */
function delete_site_alerts(mysqli $db, $id){
    
    // SQL
    $sql = 'DELETE FROM `alerts` WHERE site_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete alerts.');
}

/**
 * Subtract Account Credits
 */
function subtract_account_credits(mysqli $db, $id, $credits){
    
    // SQL
    $sql = 'UPDATE `accounts` SET credits = credits - '.$credits.' WHERE id = '.$id;
    $delete_pages_sql = 'DELETE FROM `pages` WHERE site_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete site.');
}