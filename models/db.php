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
 * @param filters [ array ('name' => $name, 'value' => $value) ]
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
 * @param filters [ array ('name' => $name, 'value' => $value) ]
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
        while($row = $results->fetch_object()){
            $data[] = $row->id;
        }
    }
    return $data;

}

/**
 * Get Scans
 * @param filters [ array ('name' => $name, 'value' => $value) ]
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
 * Get Alerts By Property Group
 */
function get_alerts_by_property_group(mysqli $db, $group){

    // SQL
    $sql = 'SELECT * FROM `alerts` WHERE `property_group` = "'.$group.'"';

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
 * Get Group Parent Status
 * Parent sets the group status.
 */
function get_group_parent_status(mysqli $db, $group){

    // SQL
    $sql = 'SELECT `status` FROM `properties` WHERE `group` = "'.$group.'" AND `is_parent` = 1';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->status;

    // Result
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
 * Add Property
 */
function add_property(mysqli $db, $url, $type, $status, $group, $is_parent){

    // Create SQL
    $sql = 'INSERT INTO `properties` (`url`, `type`, `status`, `is_parent`, `group`) VALUES';
    $sql.= '("'.$url.'",';
    $sql.= '"'.$type.'",';
    $sql.= '"'.$status.'",';
    if(empty($is_parent)){
        $sql.= 'NULL,';
    }else{
        $sql.= '"'.$is_parent.'",';
    }
    $sql.= '"'.$group.'")';
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert property with values "'.$url.',"'.$url.',"'.$type.',"'.$status.',"'.$group.',"'.$is_parent.'"');
    
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
    $sql = 'INSERT INTO `properties` (`group`, `url`, `status`, `is_parent`, `type`) VALUES';
    
    // Insert Each Record
    $record_count = count($properties_records);
    $record_iteration = 0;
    foreach ($properties_records as $record){

        // SQL
        $sql.= "(";
        $sql.= "'".$record['group']."',";
        $sql.= "'".$record['url']."',";
        $sql.= "'".$record['status']."',";
        if(empty($record['is_parent'])){
            $sql.= 'NULL,';
        }else{
            $sql.= '"'.$record['is_parent'].'",';
        }
        $sql.= "'".$record['type']."'";

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
 * Add Account Usage
 */
function add_account_usage(mysqli $db, $id, $usage){
    
    // SQL
    $sql = 'UPDATE `accounts` SET `usage` = `usage` + '.$usage.' WHERE `id` = '.$id;

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot add "'.$usage.'" for user "'.$id.'"');
}

/**
 * Get Property URI
 */
function get_property_view_uri(mysqli $db, $property_id){

    // Set $property
    $property = get_property($db, $property_id);

    // Set URL
    if($property->group == ''){
        return '?view=property_details&id='.$property->id;
    }elseif(!empty($property->group)){
        return '?view=property_details&id='.get_property_id($db, $property->group);
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
 * Update Property Group Status 
 */
function update_property_group_status(mysqli $db, $group, $new_status){

    // SQL
    $sql = 'UPDATE `properties` SET `status` = "'.$new_status.'" WHERE `group` = "'.$group.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update "'.$group.'" group status to "'.$new_status.'"');
}

/**
 * Update Property Data 
 */
function update_property_data(mysqli $db, $id, $column, $value){

    // SQL
    $sql = 'UPDATE `properties` SET `'.$column.'` = "'.$value.'" WHERE `id` = "'.$id.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update data column "'.$column.'" to "'.$value.'" for property "'.$id.'"');

}

/**
 * The Property Badge
 */
function get_property_badge($db, $property){

    // Badge info
    if($property->status == 'archived'){
        $badge_status = 'bg-dark';
        $badge_content = 'Archived';
    }elseif($property->scanned == NULL){
        $badge_status = 'bg-warning text-dark';
        $badge_content = 'Unscanned';
    }else{

        // Alerts
        $alert_count = count(get_alerts_by_property_group($db, $property->group));
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
    return '<span class="badge mb-2 '.$badge_status.'">'.$badge_content.'</span>';

}

/**
 * Add DB Column
 */
function add_db_column($db, $table, $column_name, $column_type){

    // SQL.
    $sql = 'ALTER TABLE `'.$table.'` ';
    $sql.= 'ADD COLUMN '.$column_name.' '.$column_type.';';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot add "'.$column_name.'" to "'.$table.'" with type "'.$column_type.'"');

}

/**
 * DB Column Exists
 */
function db_column_exists($db, $table, $column_name){

    // SQL.
    $sql = 'SELECT '.$column_name.' FROM `'.$table.'` ';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result){
        return false;   
    }else{
        return true;
    }

}

/**
 * Delete Alerts
 * @param filters [ array ('name' => $name, 'value' => $value) ]
 */
function delete_alerts(mysqli $db, $filters = []){

    // SQL
    $sql = 'DELETE FROM `alerts`';

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
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot delete alert using filters "'.$filters.'"');
}

/**
 * Add Alert
 */
function add_alert(mysqli $db, $property_id, $property_group, $integration_uri, $details){

    // Create SQL
    $sql = "INSERT INTO `alerts` (`property_id`, `property_group`, `integration_uri`, `details`) VALUES";
    $sql.= "('".$property_id."',";
    $sql.= "'".$property_group."',";
    $sql.= "'".$integration_uri."',";
    $sql.= "'".$details."')";
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert alert for property "'.$property_id.'" with integration uri "'.$integration_uri.'" details "'.$details.'"');
    
    // Complete Query
    return $result;
}