<?php
class DataAccess {

    // Connect to MySQL.
    private static $conn = null;
    private static function connect() {
        if (self::$conn) {
            return self::$conn;
        } else {
            self::$conn = new mysqli($GLOBALS['DB_HOST'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], $GLOBALS['DB_NAME']);
            if(self::$conn->connect_error){
                throw new Exception('Cannot connect to database: ' . self::$conn->connect_error);
            }
            return self::$conn;
        }
    }
 
    /**
     * Get All Pages
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_pages($filters = []){
    
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
        $results = self::connect()->query($sql);
    
        // Result
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
    
        // We're adding a condition so that we don't loop
        // when there is nothing to return.
        if($results->num_rows == 0){
            return NULL;
        }else{
            return $data;
        }
    }
    
    /**
     * Get Page Ids
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_page_ids($filters = []){
    
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
        $results = self::connect()->query($sql);
    
        // Result
        $data = array();
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = array(
                    'id' => $row->id
                );
            }
        }
    
        // We're adding a condition so that we don't loop
        // when there is nothing to return.
        if($results->num_rows == 0){
            return NULL;
        }else{
            return $data;
        }
    
    }
    
    /**
     * Get Scans
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_scans($filters = []){
    
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
        $results = self::connect()->query($sql);
    
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
    public static function get_alerts(){
    
        // SQL
        $sql = 'SELECT * FROM `alerts` ORDER BY STR_TO_DATE(`time`,"%Y-%m-%d %H:%i:%s")';
    
        // Query
        $results = self::connect()->query($sql);
    
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
    public static function get_alerts_by_site($site){
    
        // SQL
        $sql = 'SELECT * FROM `alerts` WHERE `site` = ?';
    
        // Query
        $stmt = self::connect()->prepare($sql);
        $stmt->bind_param('s', $site);
        $stmt->execute();
    
        // Result
        $results = $stmt->get_result();
        $data = [];
        if($results->num_rows > 0){
            while($row = $results->fetch_object()){
                $data[] = $row;
            }
        }
        return $data;

        // Close
        $results->close();
    
    }
    
    /**
     * Get Meta Value
     */
    public static function get_meta_value($meta_name){

        // SQL
        $sql = 'SELECT * FROM `meta` WHERE `meta_name` = "'.$meta_name.'"';
    
        // Query
        $data = [];
        $data = self::connect()->query($sql)->fetch_object();

        // Results
        if($data == NULL){

            // Returns "false" if no data exists.
            return false; 

        }else{

            // Returns meta_value.
            return $data->meta_value;

        }
    
    }
    
    /**
     * Get Page Records
     */
    public static function get_page($id){
    
        // SQL
        $sql = 'SELECT * FROM pages WHERE id = "'.$id.'"';
    
        // Query
        $data = [];
        $data = self::connect()->query($sql)->fetch_object();
    
        // Result
        return $data;
        
    }
    
    /**
     * Get Page ID
     */
    public static function get_page_id($url){
    
        // SQL
        $sql = 'SELECT `id` FROM `pages` WHERE `url` = "'.$url.'"';
    
        // Query
        $data = [];
        $data = self::connect()->query($sql)->fetch_object()->id;
    
        // Result
        return $data;
        
    }
    
    /**
     * Get Page URL
     */
    public static function get_page_url($id){
    
        // SQL
        $sql = 'SELECT `url` FROM pages WHERE `id` = "'.$id.'"';
    
        // Query
        $data = [];
        $data = self::connect()->query($sql)->fetch_object()->url;
    
        // Result
        return $data;
        
    }
    
    /**
     * Get Site Parent Status
     * Parent sets the site status.
     */
    public static function get_site_parent_status($site){
    
        // SQL
        $sql = 'SELECT `status` FROM `pages` WHERE `site` = "'.$site.'" AND `is_parent` = 1';
    
        // Query
        $data = [];
        $data = self::connect()->query($sql)->fetch_object()->status;
    
        // Result
        return $data;
        
    }
    
    /**
     * Get Details URI
     */
    public static function get_site_details_uri($page_id){
    
        // We just need to see if the page is a parent. If 
        // it is, that page's ID is good so we don't need
        // to run another query to get the id of the parent.
        $page = self::get_page($page_id);
        if($page->is_parent == 1){
            return '?view=site_details&id='.$page->id;
        }else{
            return '?view=site_details&id='.self::get_page_id($page->url);
        }
        
    }
    
    /**
     * Get Column Names
     */
    public static function get_column_names($table){
    
        // SQL
        $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ';
        $sql.= 'WHERE TABLE_NAME = "'.$table.'" AND TABLE_SCHEMA = "'.DB_NAME.'"';
    
        // Query
        $results = self::connect()->query($sql);
    
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
     * Is Unique Site
     */
    public static function is_unique_site($site_url){
    
        // Require unique URL
        $sql = 'SELECT * FROM `pages` WHERE `site` = "'.$site_url.'"';
    
        // We don't consider a page with a '/' a unique url
        // so we will also search for them.
        // Possible injection point:
        // INSERT INTO `equalify`.`meta` (`usage`, `wave_key`) VALUES ('1', 'c');
        $sql.= ' OR `site` = "'.$site_url.'/"';
    
        $query = self::connect()->query($sql);
        if(mysqli_num_rows($query) > 0){
            return false;
        }else{
            return true;
        }
    
    }
    
    /**
     * Add Page
     */
    public static function add_page($url, $type, $status, $site, $is_parent){
    
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
        $result = self::connect()->query($sql);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert page with values "'.$url.',"'.$url.',"'.$type.',"'.$status.',"'.$site.',"'.$is_parent.'"');
        
        // Complete Query
        return $result;
    }
    
    /**
     * Add Scan
     */
    public static function add_scan($status, array $pages){
    
        // Serialize pages.
        $pages = serialize($pages);
    
        // SQL
        $sql = "INSERT INTO `scans` (`status`, `pages`) VALUES";
        $sql.= "('".$status."',";
        $sql.= "'".$pages."')";
        
        // Query
        $result = self::connect()->query($sql);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert scan with status "'.$status.'" and records "'.$records.'"');
        
        // Complete Query
        return $result;
        
    }
    
    /**
     * Add Pages 
     */
    public static function add_pages($pages_records){
    
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
        $result = self::connect()->query($sql);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert page records "'.$pages_records.'"');
    
    }
    
    /**
     * Update Status 
     */
    public static function update_scan_status($old_status, $new_status){
    
        // SQL
        $sql = 'UPDATE `scans` SET `status` = "'.$new_status.'" WHERE `status` = "'.$old_status.'"';
    
        // Query
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update scan status where old status is "'.$old_status.'" and new status is "'.$new_status.'"');
    }
    
    /**
     * Update Page Scanned Time 
     */
    public static function update_page_scanned_time($id){
    
        // SQL
        $sql = 'UPDATE `pages` SET `scanned` = CURRENT_TIMESTAMP() WHERE `id` = "'.$id.'"';
    
        // Query
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update scan time for scan with id "'.$id.'"');
    }
    
    /**
     * Update Page Site Status 
     */
    public static function update_site_status($site, $new_status){
    
        // SQL
        $sql = 'UPDATE `pages` SET `status` = "'.$new_status.'" WHERE `site` = "'.$site.'"';
    
        // Query
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$site.'" site status to "'.$new_status.'"');
    }
    
    /**
     * Update Page Data 
     */
    public static function update_page_data($id, $column, $value){
    
        // SQL
        $sql = 'UPDATE `pages` SET `'.$column.'` = "'.$value.'" WHERE `id` = "'.$id.'"';
    
        // Query
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update data column "'.$column.'" to "'.$value.'" for page "'.$id.'"');
    
    }
    
    /**
     * The Page Badge
     */
    public static function get_page_badge($page){
    
        // Badge info
        if($page->status == 'archived'){
            $badge_status = 'bg-dark';
            $badge_content = 'Archived';
        }elseif($page->scanned == NULL){
            $badge_status = 'bg-warning text-dark';
            $badge_content = 'Unscanned';
        }else{
    
            // Alerts
            $alert_count = count(self::get_alerts_by_site($page->site));
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
    public static function add_db_column($table, $column_name, $column_type){
    
        // SQL.
        $sql = 'ALTER TABLE `'.$table.'` ';
        $sql.= 'ADD COLUMN '.$column_name.' '.$column_type.';';
    
        // Query
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot add "'.$column_name.'" to "'.$table.'" with type "'.$column_type.'"');
    
    }
    
    /**
     * Add DB Column
     */
    public static function delete_db_column($table, $column_name){
    
        // SQL.
        $sql = 'ALTER TABLE `'.$table.'` ';
        $sql.= 'DROP COLUMN '.$column_name.';';
    
        // Query
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot drop "'.$column_name.'" to "'.$table.'"');
    
    }
    
    
    /**
     * DB Column Exists
     */
    public static function db_column_exists($table, $column_name){
    
        // SQL.
        $sql = 'SELECT '.$column_name.' FROM `'.$table.'` ';
    
        // Query
        $result = self::connect()->query($sql);
    
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
    public static function delete_alerts($filters = []){
    
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
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
    }
    
    /**
     * Add Page Alert
     */
    public static function add_page_alert($page_id, $site, $integration_uri, $details){
        
        // SQL
        $sql = "INSERT INTO `alerts` (`source`, `page_id`, `site`, `integration_uri`, `details`) VALUES";
        $sql.= "('page',";
        $sql.= "'".$page_id."',";
        $sql.= "'".$site."',";
        $sql.= "'".$integration_uri."',";
        $sql.= "'".$details."')";
        
        // Query
        $result = self::connect()->query($sql);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot insert integration alert for page "'.$page_id.'" with integration uri "'.$integration_uri.'" details "'.$details.'"');
        
        // Complete Query
        return $result;
        
    }
    
    /**
     * Add Integration Alert
     */
    public static function add_integration_alert($details){
    
        // SQL
        $sql = "INSERT INTO `alerts` (`source`, `details`) VALUES";
        $sql.= "('integration',";
        $sql.= "'".$details."')";
        
        // Query
        $result = self::connect()->query($sql);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot insert alert "'.$details.'"');
        
        // Complete Query
        return $result;
        
    }
    
    /**
     * Add Meta
     */
    public static function add_meta($meta_name, $meta_value = ''){
    
        // SQL
        $sql = 'INSERT INTO `meta` (`meta_name`, `meta_value`) VALUES ("'.$meta_name.'", "'.$meta_value.'")';
        
        // Query
        $result = self::connect()->query($sql);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot add meta field "'.$meta_name.'" and value "'.$meta_value.'"');
        
        // Complete Query
        return $result;
    }

    /**
     * Delete Meta
     */
    public static function delete_meta($meta_name){
    
        // SQL
        $sql = 'DELETE FROM `meta` WHERE `meta_name` = "'.$meta_name.'"';
    
        // Query
        $result = self::connect()->query($sql);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
        // Complete Query
        return $result;

    }

    /**
     * Update Meta 
     */
    public static function update_meta_value($meta_name, $meta_value){

        // SQL
        $sql = "UPDATE `meta` SET `meta_value` = '".$meta_value."' WHERE `meta_name` = '".$meta_name."'";

        // Query
        $result = self::connect()->query($sql);

        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$meta_name.'" with value "'.$meta_value.'"');
    
        // Complete Query
        return $result;

    }
        
    /**
     * Create Alerts Table
     */
    public static function create_alerts_table(){
    
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
        $result = self::connect()->query($sql);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }
    
    /**
     * Create Meta Table
     */
    public static function create_meta_table(){
    
        // SQL
        $sql_1 = 
        'CREATE TABLE `meta` (
            `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `meta_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT "",
            `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
            PRIMARY KEY (`meta_id`),
            UNIQUE KEY `option_name` (`meta_name`)
          ) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
        ';

        // Query 1
        $result = self::connect()->query($sql_1);

        $sql_2 = 
        'INSERT INTO `meta` (`meta_id`, `meta_name`, `meta_value`)
        VALUES (1, "active_integrations", "a:1:{i:0;s:14:\"little_forrest\";}")';
    
        $result = self::connect()->query($sql_2);

        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }
    
    /**
     * Create Pages Table
     */
    public static function create_pages_table(){
    
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
        $result = self::connect()->query($sql);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }
    
    /**
     * Create Scans Table
     */
    public static function create_scans_table(){
    
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
        $result = self::connect()->query($sql);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');
        
    }
    
    /**
     * Table Exists
     */
    public static function table_exists($table_name){
    
        // SQL
        $sql = 
            'SELECT 1 from '.$table_name;
        
        // Query
        $result = self::connect()->query($sql);
    
        // Results
        if($result !== FALSE){
           return true;
        }else{
            return false;
        }
    
    }

}