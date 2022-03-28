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
 * @param filters [$name => $value]
 */
function get_properties(mysqli $db, $filters = []){

    // SQL
    $sql = 'SELECT * FROM `properties`';

    // Add optional filters
    $filter_count = count($filters);
    if($filter_count > 0){
        $sql.= 'WHERE ';

        $filter_iteration = 0;
        foreach ($filters as $filter){
            $sql.= '`'.$filter['name'].'` = "'.$filter['value'].'"';
            if(++$filter_iteration != $filter_count)
                $sql.= ' AND ';
    
        }
    }
    $sql.= ';';

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
 * Get Property Ids
 * @param filters [$name => $value]
 */
function get_property_ids(mysqli $db, $filters = []){

    // SQL
    $sql = 'SELECT `id` FROM `properties`';

    // Add optional filters
    $filter_count = count($filters);
    if($filter_count > 0){
        $sql.= 'WHERE ';

        $filter_iteration = 0;
        foreach ($filters as $filter){
            $sql.= '`'.$filter['name'].'` = "'.$filter['value'].'"';
            if(++$filter_iteration != $filter_count)
                $sql.= ' AND ';
    
        }
    }
    $sql.= ';';

    // Query
    $results = $db->query($sql);

    // Result
    if($results->num_rows > 0){
        while($row = $results->fetch_object()->id){
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get Scans
 *  @param filters [$status => $value]
 */
function get_scans(mysqli $db, $filters = []){

    // SQL
    $sql = 'SELECT * FROM `scans`';

    // Add optional filters
    $filter_count = count($filters);
    if($filter_count > 0){
        $sql.= ' WHERE ';

        $filter_iteration = 0;
        foreach ($filters as $filter){
            $sql.= '`'.$filter['name'].'` = "'.$filter['value'].'"';
            if(++$filter_iteration != $filter_count)
                $sql.= ' AND ';
    
        }
    }
    $sql.= ' ORDER BY STR_TO_DATE(`time`,"%Y-%m-%d %H:%i:%s") DESC;';

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
 * Get Property Children
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
 * Is Unique Property URL
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
 * Is Active Intragration
 */
function is_active_intration(mysqli $db, $integration_uri){

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
 * Add Property
 */
function add_property(mysqli $db, $url, $type, $status, $parent){

    // Create SQL
    $sql = 'INSERT INTO `properties` (`url`, `type`, `status`, `parent`) VALUES';
    $sql.= '("'.$url.'",';
    $sql.= '"'.$type.'",';
    $sql.= '"'.$status.'",';
    $sql.= '"'.$parent.'")';
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert property with values "'.$url.',"'.$url.',"'.$type.',"'.$status.',"'.$parent);
    
    // Complete Query
    return $result;
}

/**
 * Add Scan
 */
function add_scan(mysqli $db, $status, array $properties){

    // Serialize properties.
    $properties = serialize($properties);

    // Create SQL
    $sql = "INSERT INTO `scans` (`status`, `properties`) VALUES";
    $sql.= "('".$status."',";
    $sql.= "'".$properties."')";
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert scan with status "'.$status.'" and records "'.$records.'"');
    
    // Complete Query
    return $result;
}

/**
 * Add Properties 
 */
function add_properties(mysqli $db, $properties_records){

    // Create SQL
    $sql = 'INSERT INTO `properties` (`parent`, `url`, `status`) VALUES';
    
    // Insert Each Record
    $record_count = count($properties_records);
    $record_iteration = 0;
    foreach ($properties_records as $record){

        // SQL
        $sql.= "(";
        $sql.= "'".$record['parent']."',";
        $sql.= "'".$record['url']."',";
        $sql.= "'".$record['status']."'";
        $sql.= ")";
        if(++$record_iteration != $record_count)
            $sql.= ",";

    }
    $sql.= ";";
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert property records "'.$properties_records.'"');

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


/**
 * Update Account 
 */
function update_account(mysqli $db, array $account_records){

    // SQL
    $sql = 'UPDATE `accounts` SET ';

    // Loop Based on the amount of records updated
    $record_count = count($account_records);
    $record_iteration = 0;
    foreach ($account_records as $record){
        $sql.= '`'.$record['key'].'` = "'.$record['value'].'"';
        if(++$record_iteration != $record_count)
            $sql.= ",";
    }
    $sql.= ' WHERE `id` = "'.USER_ID.'";';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update account for user '.USER_ID);

}

/**
 * Update Status 
 */
function update_scan_status(mysqli $db, $old_status, $new_status){

    // SQL
    $sql = 'UPDATE `scans` SET `status` = "'.$new_status.'" WHERE `status` = "'.$old_status.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update scan status where old status is "'.$old_status.'" and new status is "'.$new_status.'"');
}

/**
 * Update Property Scanned Time 
 */
function update_property_scanned_time(mysqli $db, $id){

    // SQL
    $sql = 'UPDATE `properties` SET `scanned` = CURRENT_TIMESTAMP() WHERE `id` = "'.$id.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update scan status where old status is "'.$old_status.'" and new status is "'.$new_status.'"');
}

/**
 * Update Property Status 
 */
function update_property_status(mysqli $db, $id, $new_status){

    // SQL
    $sql = 'UPDATE `properties` SET `status` = "'.$new_status.'" WHERE `id` = "'.$id.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update property status where old status is "'.$old_status.'" and new status is "'.$new_status.'"');
}

/**
 * Update Property Children Status 
 */
function update_property_children_status(mysqli $db, $parent_id, $new_status){

    // Get URL of parent.
    $parent = get_property_url($db, $parent_id);

    // SQL
    $sql = 'UPDATE `properties` SET `status` = "'.$new_status.'" WHERE parent = "'.$parent.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot archive children of property "'.$parent_id.'"');
}

/**
 * The Property Badge
 */
function the_property_badge($db, $property){

    // Badge info
    if($property->status == 'archived'){
        $badge_status = 'bg-dark';
        $badge_content = 'Archived';
    }elseif($property->scanned == NULL){
        $badge_status = 'bg-warning text-dark';
        $badge_content = 'Unscanned';
    }else{

        // Alerts
        $alert_count = count(get_alerts_by_property($db, $property->id));
        if($alert_count == 0){
            $badge_status = 'bg-success';
            $badge_content = 'Equalified';
        }else{
            $badge_status = 'bg-danger';
            if($alert_count == 1){
                $badge_content = $alert_count.' Alert';
            }else{
                $badge_content = $alert_count.' Alerts';
            }
        };

    }
    echo '<span class="badge mb-2 '.$badge_status.'">'.$badge_content.'</span>';

}