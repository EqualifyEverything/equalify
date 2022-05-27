<?php
class DataAccess {

    // Set the records per page.
    private const ITEMS_PER_PAGE = 10;

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
     * Query helper.
     *
     * @param string $sql The query SQL.
     * @param array $params The query parameters to bind.
     * @param boolean $return If we're expecting a result.
     * @return mysqli_result|boolean
     */
    private static function query($sql, $params, $return) {
        $connection = self::connect();
        if (empty($params)) {
            $results = $connection->query($sql);
        } else {
            $statement = $connection->prepare($sql);
            if ($statement === false) {
                throw new Exception('Unable to prepare SQL: ' . $connection->error);
            }
            $statement->bind_param(str_repeat('s', count($params)), ...$params);
            $results = $statement->execute();
            if ($return) {
                $results = $statement->get_result();
            }
        }

        return $results;
    }

    /**
     * Get All Pages
     * @param array filters [ array ('name' => $name, 'value' => $value) ]
     */
    public static function get_pages($filters = []){
    
        // SQL
        $sql = 'SELECT * FROM `pages`';
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= 'WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $results = self::query($sql, $params, true);
    
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
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= 'WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $results = self::query($sql, $params, true);
    
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
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= ' WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ' ORDER BY STR_TO_DATE(`time`,"%Y-%m-%d %H:%i:%s") DESC;';
    
        // Query
        $results = self::query($sql, $params, true);
    
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
     * @param array filters [ array ('name' => $name, 'value' => $value, 'page' => $page) ]
     * @param string page
     */
    public static function get_alerts($filters = [], $page = 1){

        // Set alerts per page.
        $alerts_per_page = self::ITEMS_PER_PAGE;
        $page_offset = ($page-1) * $alerts_per_page;

        // Create 'total_pages' SQL.
        $total_pages_sql = "SELECT COUNT(*) FROM `alerts`";

        // Create 'content' SQL.
        $content_sql = "SELECT * FROM `alerts`";
        $params = array();

        // Add optional filters to content and total_pages.
        $filter_count = count($filters);
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $filters_sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
            $filters_sql.= ' LIMIT '.$page_offset.', '.$alerts_per_page;
            $total_pages_sql.= $filters_sql;
            $content_sql.= $filters_sql;
        }

        // Run 'total_pages' SQL.
        $total_pages_result = self::query($total_pages_sql, $params, true);
        $total_pages_rows = $total_pages_result->fetch_array()[0];
        $total_pages = ceil($total_pages_rows / $alerts_per_page);
    
        // Run 'content' SQL
        $content_results = self::query($content_sql, $params, true);
        $content = [];
        if($content_results->num_rows > 0){
            while($row = $content_results->fetch_object()){
                $content[] = $row;
            }
        }
    
        // Create and return data.
        $data = [
            'total_pages' => $total_pages,
            'content' => $content
        ];
        return $data;

    }

    /**
     * Count Alerts
     */
    public static function count_alerts(){

        // SQL
        $sql = 'SELECT COUNT(*) AS TOTAL FROM `alerts`';
        $params = array();

        // Query
        $results = self::query($sql, $params, true);

        // Result
        $data = $results->fetch_object()->TOTAL;
        return $data;

    }

    
    /**
     * Get Alerts By Page Site
     */
    public static function get_alerts_by_site($site){
    
        // SQL
        $sql = 'SELECT * FROM `alerts` WHERE `site` = ?';
        $params = array($site);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
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
        $sql = 'SELECT * FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $results = self::query($sql, $params, true);

        // Results
        $data = $results->fetch_object();
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
        $sql = 'SELECT * FROM pages WHERE id = ?';
        $params = array($id);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object();
        return $data;
        
    }
    
    /**
     * Get Page ID
     */
    public static function get_page_id($url){
    
        // SQL
        $sql = 'SELECT `id` FROM `pages` WHERE `url` = ?';
        $params = array($url);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->id;
        return $data;
        
    }
    
    /**
     * Get Page URL
     */
    public static function get_page_url($id){
    
        // SQL
        $sql = 'SELECT `url` FROM pages WHERE `id` = ?';
        $params = array($id);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->url;
        return $data;
        
    }
    
    /**
     * Get Site Parent Status
     * Parent sets the site status.
     */
    public static function get_site_parent_status($site){
    
        // SQL
        $sql = 'SELECT `status` FROM `pages` WHERE `site` = ? AND `is_parent` = 1';
        $params = array($site);
    
        // Query
        $results = self::query($sql, $params, true);
    
        // Result
        $data = $results->fetch_object()->status;
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
        $sql.= 'WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?';
        $params = array($table, $GLOBALS['DB_NAME']);
    
        // Query
        $results = self::query($sql, $params, true);
    
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
        $sql = 'SELECT * FROM `pages` WHERE `site` = ?';
    
        // We don't consider a page with a '/' a unique url
        // so we will also search for them.
        // Possible injection point:
        // INSERT INTO `equalify`.`meta` (`usage`, `wave_key`) VALUES ('1', 'c');
        $sql.= ' OR `site` = ?';
    
        $params = array($site_url, $site_url . '/');
        $results = self::query($sql, $params, true);
        if($results->num_rows > 0){
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
        $sql.= '(?, ?, ?,';
        $params = array($url, $type, $status);
        if(empty($is_parent)){
            $sql.= 'NULL,';
        }else{
            $sql.= '?,';
            $params[] = $is_parent;
        }
        $sql.= '?)';
        $params[] = $site;
        
        // Query
        $result = self::query($sql, $params, false);
    
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
        $sql = 'INSERT INTO `scans` (`status`, `pages`) VALUES';
        $sql.= '(?, ?)';
        $params = array($status, $pages);
        
        // Query
        $result = self::query($sql, $params, false);
    
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
        $params = array();
        foreach ($pages_records as $record){
    
            // SQL
            $sql.= "(?, ?, ?,";
            $params[] = $record['site'];
            $params[] = $record['url'];
            $params[] = $record['status'];
            if(empty($record['is_parent'])){
                $sql.= 'NULL,';
            }else{
                $sql.= '?,';
                $params[] = $record['is_parent'];
            }
            $sql.= '?)';
            $params[] = $record['type'];

            if(++$record_iteration != $record_count)
                $sql.= ",";
    
        }
        $sql.= ";";
        
        // Query
        $result = self::query($sql, $params, false);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert page records "'.$pages_records.'"');
    
    }
    
    /**
     * Update Status 
     */
    public static function update_scan_status($old_status, $new_status){
    
        // SQL
        $sql = 'UPDATE `scans` SET `status` = ? WHERE `status` = ?';
        $params = array($new_status, $old_status);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update scan status where old status is "'.$old_status.'" and new status is "'.$new_status.'"');
    }
    
    /**
     * Update Page Scanned Time 
     */
    public static function update_page_scanned_time($id){
    
        // SQL
        $sql = 'UPDATE `pages` SET `scanned` = CURRENT_TIMESTAMP() WHERE `id` = ?';
        $params = array($id);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update scan time for scan with id "'.$id.'"');
    }
    
    /**
     * Update Page Site Status 
     */
    public static function update_site_status($site, $new_status){
    
        // SQL
        $sql = 'UPDATE `pages` SET `status` = ? WHERE `site` = ?';
        $params = array($new_status, $site);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$site.'" site status to "'.$new_status.'"');
    }
    
    /**
     * Update Page Data 
     */
    public static function update_page_data($id, $column, $value){
    
        // SQL
        $sql = 'UPDATE `pages` SET `'.$column.'` = ? WHERE `id` = ?';
        $params = array($value, $id);
    
        // Query
        $result = self::query($sql, $params, false);
    
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
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
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
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
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
        $params = array();
    
        // Query
        $result = self::query($sql, $params, false);
    
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
        $params = array();
    
        // Add optional filters
        $filter_count = count($filters);
        if($filter_count > 0){
            $sql.= 'WHERE ';
    
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $sql.= ' AND ';
        
            }
        }
        $sql.= ';';
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot delete alert using filters "'.$filters.'"');
    
    }
    
    /**
     * Add Page Alert
     */
    public static function add_page_alert($page_id, $site, $integration_uri, $type, $message, $meta = []){
        
        // Sanitize items.
        $message = filter_var($message, FILTER_SANITIZE_STRING);
        $meta = filter_var(serialize($meta), FILTER_SANITIZE_STRING);

        // Require certain alert types.
        $allowed_types = array('error', 'warning', 'notice');
        if(!in_array($type, $allowed_types))
            throw new Exception('Alert type, "'.$type.'," is not allowed');

        // SQL
        $sql = 'INSERT INTO `alerts` (`source`, `page_id`, `site`, `integration_uri`, `type`, `message`, `meta`) VALUES';
        $sql.= '(?, ?, ?, ?, ?, ?, ?)';
        $params = array('page', $page_id, $site, $integration_uri, $type, $message, $meta);
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot insert integration alert for "'.$page_id.'", "'.$site.'", "'.$integration_uri.'", "'.$type.'", "'.$message.'"');
        
        // Complete Query
        return $result;
        
    }
    
    /**
     * Add Integration Alert
     */
    public static function add_integration_alert($message){
    
        // SQL
        $sql = 'INSERT INTO `alerts` (`source`, `message`) VALUES';
        $sql.= '(?, ?)';
        $params = array('integration', $message);
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Cannot insert alert "'.$message.'"');
        
        // Complete Query
        return $result;
        
    }
    
    /**
     * Add Meta
     */
    public static function add_meta($meta_name, $meta_value = ''){
    
        // Serialize meta_value.
        $meta_value = serialize($meta_value);
    
        // SQL
        $sql = 'INSERT INTO `meta` (`meta_name`, `meta_value`) VALUES (?, ?)';
        $params = array($meta_name, $meta_value);
        
        // Query
        $result = self::query($sql, $params, false);
    
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
        $sql = 'DELETE FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $result = self::query($sql, $params, false);
    
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
        $sql = "UPDATE `meta` SET `meta_value` = ? WHERE `meta_name` = ?";
        $params = array($meta_value, $meta_name);

        // Query
        $result = self::query($sql, $params, false);

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
                `page_id` bigint(20) NOT NULL,
                `message` text NOT NULL,
                `integration_uri` varchar(200) NOT NULL,
                `site` text NOT NULL,
                `type` varchar(200) NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
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
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
        ';
        $params = array();

        // Query 1
        $result = self::query($sql_1, $params, false);

        // Fallback
        if(!$result)
            throw new Exception('Error creating table: "'.$result->error.'"');

        // Little Forrest is activated here.
        self::add_meta('active_integrations', serialize(['little_forrest']));
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
                `little_forrest_wcag_2_1_errors` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "0", -- Little Forrest is activated here.
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
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
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
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
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Results
        if($result !== FALSE){
           return true;
        }else{
            return false;
        }
    
    }

}
