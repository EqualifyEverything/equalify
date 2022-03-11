<?php
/**
 * Connect to DB
 * TODO: Refactor this terrible way the DB connects
 */
function connect($hostname, $username, $password, $database){
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
 * Get Account WAVE Key
 */
function get_account_wave_key(mysqli $db, $id){

    // SQL
    $sql = 'SELECT wave_key FROM accounts WHERE id = '.$id;

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->wave_key;

    // Result
    return $data;
}

/**
 * Get Account Credits
 */
function get_account_credits(mysqli $db, $id){

    // SQL
    $sql = 'SELECT credits FROM accounts WHERE id = '.$id;

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->credits;

    // Result
    return $data;
}

/**
 * Get Accessibility Testing Service
 */
function get_accessibility_testing_service(mysqli $db, $id){

    // SQL
    $sql = 'SELECT accessibility_testing_service FROM accounts WHERE id = '.$id;

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->accessibility_testing_service;

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
    $sql.= "(`status`, `url`)";
    $sql.= " VALUES ";
    $sql.= "(";
    $sql.= "'".$record['status']."',";
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
    $sql.= "wave_key = '".$record['wave_key']."',";
    $sql.= "accessibility_testing_service = '".$record['accessibility_testing_service']."'";
    $sql.= " WHERE id = 1;";

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
 * Delete Pages
 */
function delete_pages(mysqli $db, $id){
    
    // SQL
    $sql = 'DELETE FROM `pages` WHERE site_id = "'.$id.'"';

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete site.');
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