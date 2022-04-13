<?php
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
    if($db->connect_error){
        throw new Exception('Cannot connect to database: ' . $db->connect_error);
    }
    return $db;
}

/**
 * Get All Pages
 * @param array filters [ array ('name' => $name, 'value' => $value) ]
 */
function get_pages(mysqli $db, $filters = []){

    // SQL
    $sql = 'SELECT * FROM `pages`';

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
 * Get Page Ids
 * @param array filters [ array ('name' => $name, 'value' => $value) ]
 */
function get_page_ids(mysqli $db, $filters = []){

    // SQL
    $sql = 'SELECT `id` FROM `pages`';

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
    $data[] = '';
    if($results->num_rows > 0){
        while($row = $results->fetch_object()){
            $data[] = $row->id;
        }
    }
    return $data;

}

/**
 * Get Scans
 * @param array filters [ array ('name' => $name, 'value' => $value) ]
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
 * Get Alerts By Page Site
 */
function get_alerts_by_site(mysqli $db, $site){

    // SQL
    $sql = 'SELECT * FROM `alerts` WHERE `site` = "'.$site.'"';

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
 * Get Meta
 */
function get_meta(mysqli $db){

    // SQL
    $sql = 'SELECT * FROM meta';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object();

    // Result
    return $data;

}

/**
 * Get Page Records
 */
function get_page(mysqli $db, $id){

    // SQL
    $sql = 'SELECT * FROM pages WHERE id = "'.$id.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object();

    // Result
    return $data;
    
}

/**
 * Get Page ID
 */
function get_page_id(mysqli $db, $url){

    // SQL
    $sql = 'SELECT `id` FROM `pages` WHERE `url` = "'.$url.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->id;

    // Result
    return $data;
    
}

/**
 * Get Page URL
 */
function get_page_url(mysqli $db, $id){

    // SQL
    $sql = 'SELECT `url` FROM pages WHERE `id` = "'.$id.'"';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->url;

    // Result
    return $data;
    
}

/**
 * Get Site Parent Status
 * Parent sets the site status.
 */
function get_site_parent_status(mysqli $db, $site){

    // SQL
    $sql = 'SELECT `status` FROM `pages` WHERE `site` = "'.$site.'" AND `is_parent` = 1';

    // Query
    $data = [];
    $data = $db->query($sql)->fetch_object()->status;

    // Result
    return $data;
    
}


/**
 * Is Unique Page URL
 */
function is_unique_page_url(mysqli $db, $page_url){

    // We don't consider a page with a '/' a unique url
    // so we will also search for them.
    if( !str_ends_with($page_url, '/') )
        $page_url_backslashed = $page_url.'/';

    // Require unique URL
    $sql = 'SELECT * FROM `pages` WHERE `url` = "'.$page_url.'"';
    if(isset($page_url_backslashed))
        $sql.= ' OR `url` = "'.$page_url_backslashed.'"';

    $query = $db->query($sql);
    if(mysqli_num_rows($query) > 0){
        return false;
    }else{
        return true;
    }

}

/**
 * Add Page
 */
function add_page(mysqli $db, $url, $type, $status, $site, $is_parent){

    // SQL
    $sql = 'INSERT INTO `pages` (`url`, `type`, `status`, `is_parent`, `site`) VALUES';
    $sql.= '("'.$url.'",';
    $sql.= '"'.$type.'",';
    $sql.= '"'.$status.'",';
    if(empty($is_parent)){
        $sql.= 'NULL,';
    }else{
        $sql.= '"'.$is_parent.'",';
    }
    $sql.= '"'.$site.'")';
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert page with values "'.$url.',"'.$url.',"'.$type.',"'.$status.',"'.$site.',"'.$is_parent.'"');
    
    // Complete Query
    return $result;
}

/**
 * Add Scan
 */
function add_scan(mysqli $db, $status, array $pages){

    // Serialize pages.
    $pages = serialize($pages);

    // SQL
    $sql = "INSERT INTO `scans` (`status`, `pages`) VALUES";
    $sql.= "('".$status."',";
    $sql.= "'".$pages."')";
    
    // Query
    $result = $db->query($sql);

    //Fallback
    if(!$result)
        throw new Exception('Cannot insert scan with status "'.$status.'" and records "'.$records.'"');
    
    // Complete Query
    return $result;
    
}

/**
 * Add Pages 
 */
function add_pages(mysqli $db, $pages_records){

    // SQL
    $sql = 'INSERT INTO `pages` (`site`, `url`, `status`, `is_parent`, `type`) VALUES';
    
    // Insert Each Record
    $record_count = count($pages_records);
    $record_iteration = 0;
    foreach ($pages_records as $record){

        // SQL
        $sql.= "(";
        $sql.= "'".$record['site']."',";
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
        throw new Exception('Cannot insert page records "'.$pages_records.'"');

}

/**
 * Update Usage Meta
 */
function update_usage_meta(mysqli $db, $usage){
    
    // SQL
    $sql = 'UPDATE `meta` SET `usage` = `usage` + '.$usage;

    // Execute Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot add "'.$usage.'"');
}

/**
 * Get Details URI
 */
function get_site_details_uri(mysqli $db, $page_id){

    // We just need to see if the page is a parent. If 
    // it is, that page's ID is good so we don't need
    // to run another query to get the id of the parent.
    $page = get_page($db, $page_id);
    if($page->is_parent == 1){
        return '?view=site_details&id='.$page->id;
    }else{
        return '?view=site_details&id='.get_page_id($db, $page->url);
    }
    
}

/**
 * Update Meta 
 */
function update_meta(mysqli $db, array $meta_records){

    // SQL
    $sql = 'UPDATE `meta` SET ';

    // Loop Based on the amount of records updated
    $record_count = count($meta_records);
    $record_iteration = 0;
    foreach ($meta_records as $record){
        $sql.= '`'.$record['key'].'` = "'.$record['value'].'"';
        if(++$record_iteration != $record_count)
            $sql.= ",";
    }

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update meta: '.$meta_records);

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
 * Update Page Scanned Time 
 */
function update_page_scanned_time(mysqli $db, $id){

    // SQL
    $sql = 'UPDATE `pages` SET `scanned` = CURRENT_TIMESTAMP() WHERE `id` = "'.$id.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update scan status where old status is "'.$old_status.'" and new status is "'.$new_status.'"');
}

/**
 * Update Page Site Status 
 */
function update_site_status(mysqli $db, $site, $new_status){

    // SQL
    $sql = 'UPDATE `pages` SET `status` = "'.$new_status.'" WHERE `site` = "'.$site.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update "'.$site.'" site status to "'.$new_status.'"');
}

/**
 * Update Page Data 
 */
function update_page_data(mysqli $db, $id, $column, $value){

    // SQL
    $sql = 'UPDATE `pages` SET `'.$column.'` = "'.$value.'" WHERE `id` = "'.$id.'"';

    // Query
    $result = $db->query($sql);

    // Result
    if(!$result)
        throw new Exception('Cannot update data column "'.$column.'" to "'.$value.'" for page "'.$id.'"');

}

/**
 * The Page Badge
 */
function get_page_badge($db, $page){

    // Badge info
    if($page->status == 'archived'){
        $badge_status = 'bg-dark';
        $badge_content = 'Archived';
    }elseif($page->scanned == NULL){
        $badge_status = 'bg-warning text-dark';
        $badge_content = 'Unscanned';
    }else{

        // Alerts
        $alert_count = count(get_alerts_by_site($db, $page->site));
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
 * @param array filters [ array ('name' => $name, 'value' => $value) ]
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
 * Add Page Alert
 */
function add_page_alert(mysqli $db, $page_id, $site, $integration_uri, $details){
    
    // SQL
    $sql = "INSERT INTO `alerts` (`source`, `page_id`, `site`, `integration_uri`, `details`) VALUES";
    $sql.= "('page',";
    $sql.= "'".$page_id."',";
    $sql.= "'".$site."',";
    $sql.= "'".$integration_uri."',";
    $sql.= "'".$details."')";
    
    // Query
    $result = $db->query($sql);

    // Fallback
    if(!$result)
        throw new Exception('Cannot insert integration alert for page "'.$page_id.'" with integration uri "'.$integration_uri.'" details "'.$details.'"');
    
    // Complete Query
    return $result;
    
}

/**
 * Add Integration Alert
 */
function add_integration_alert(mysqli $db, $details){

    // SQL
    $sql = "INSERT INTO `alerts` (`source`, `details`) VALUES";
    $sql.= "('integration',";
    $sql.= "'".$details."')";
    
    // Query
    $result = $db->query($sql);

    // Fallback
    if(!$result)
        throw new Exception('Cannot insert alert "'.$details.'"');
    
    // Complete Query
    return $result;
    
}

/**
 * Add Meta
 */
function add_meta(mysqli $db, $field, $value){

    // SQL
    $sql = 'INSERT INTO `meta` (`'.$field.'`) VALUES ("'.$value.'");';
    
    // Query
    $result = $db->query($sql);

    // Fallback
    if(!$result)
        throw new Exception('Cannot add meta field "'.$field.'" and value "'.$value.'"');
    
    // Complete Query
    return $result;
}

/**
 * Create Alerts Table
 */
function create_alerts_table($db){

    // SQL
    $sql = 
        'CREATE TABLE `alerts` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `source` varchar(200) NOT NULL,
            `page_id` bigint(20) DEFAULT NULL,
            `details` text,
            `integration_uri` varchar(200) DEFAULT NULL,
            `site` text,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4;';
    
    // Query
    $result = $db->query($sql);

    // Fallback
    if(!$result)
        throw new Exception('Error creating table: "'.$result->error.'"');
    
}

/**
 * Create Meta Table
 */
function create_meta_table($db){

    // SQL
    $sql = 
        'CREATE TABLE `meta` (
            `usage` bigint(20) DEFAULT NULL,
            `wave_key` varchar(20) DEFAULT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
    
    // Query
    $result = $db->query($sql);

    // Fallback
    if(!$result)
        throw new Exception('Error creating table: "'.$result->error.'"');
    
}

/**
 * Create Pages Table
 */
function create_pages_table($db){

    // SQL
    $sql = 
        'CREATE TABLE `pages` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `url` text COLLATE utf8mb4_bin NOT NULL,
            `type` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "static",
            `status` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "active",
            `site` text COLLATE utf8mb4_bin,
            `is_parent` tinyint(1) DEFAULT NULL,
            `scanned` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=7172 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;';
    
    // Query
    $result = $db->query($sql);

    // Fallback
    if(!$result)
        throw new Exception('Error creating table: "'.$result->error.'"');
    
}

/**
 * Create Scans Table
 */
function create_scans_table($db){

    // SQL
    $sql = 
        'CREATE TABLE `scans` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `pages` blob,
            `status` varchar(20) DEFAULT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4;';
    
    // Query
    $result = $db->query($sql);

    // Fallback
    if(!$result)
        throw new Exception('Error creating table: "'.$result->error.'"');
    
}

/**
 * Table Exists
 */
function table_exists($db, $table_name){

    // SQL
    $sql = 
        'SELECT 1 from '.$table_name;
    
    // Query
    $result = $db->query($sql);

    // Results
    if($result !== FALSE){
       return true;
    }else{
        return false;
    }

}