<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document contains all the integrations with the 
 * app's MySQL database.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

class DataAccess {
    // Set the records per page.
    private const ITEMS_PER_PAGE = 10;

    // Connect to MySQL.
    private static $conn = null;
    private static function connect() {
        if (self::$conn) {
            return self::$conn;
        } else {
            self::$conn = new mysqli(
                $GLOBALS['DB_HOST'], 
                $GLOBALS['DB_USERNAME'], 
                $GLOBALS['DB_PASSWORD'], 
                $GLOBALS['DB_NAME'],  
                $GLOBALS['DB_PORT'],  
                $GLOBALS['DB_SOCKET']);
            if(self::$conn->connect_error){
                throw new Exception(
                    'Cannot connect to database: '
                    .self::$conn->connect_error
                );
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
    private static function query($sql, $params, $return) 
    {
        $connection = self::connect();
        if (empty($params)) {
            $results = $connection->query($sql);
        } else {
            $statement = $connection->prepare($sql);
            if ($statement === false) {
                throw new Exception(
                    'Unable to prepare SQL: '
                    .$connection->error
                );
            }
            $statement->bind_param(str_repeat(
                's', count($params)
            ), ...$params);
            $results = $statement->execute();
            if ($return) {
                $results = $statement->get_result();
            }
        }

        return $results;
    }

    /**
     * Get Meta Value
     */
    public static function get_meta_value($meta_name){

        // SQL
        $sql = 'SELECT `meta_value` FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $results = self::query($sql, $params, true);

        // Returns meta_value.
        $data = $results->fetch_object();
        if(empty($data)){
            return false;
        }else{
            return $data->meta_value;
        }

    }

    /**
     * Is Unique Site
     */
    public static function is_unique_site($url){
    
        // Require unique URL
        $sql = 'SELECT * FROM `sites` WHERE `url` = ?';
    
        // We don't consider a page with a '/' a unique url
        // so we will also search for them.
        // Possible injection point:
        // INSERT INTO `equalify`.`meta` (`usage`, `wave_key`) VALUES ('1', 'c');
        $sql.= ' OR `url` = ?';
    
        $params = array($url, $url . '/');
        $results = self::query($sql, $params, true);
        if($results->num_rows > 0){
            return false;
        }else{
            return true;
        }
    
    }
    
    /**
     * Add Site
     */
    public static function add_site($url, $type, $status){
    
        // SQL
        $sql = 'INSERT INTO `sites` (`url`, `type`, `status`) VALUES';
        $sql.= '(?, ?, ?, ?)';
        $params = array($url, $type, $status);
        
        // Query
        $result = self::query($sql, $params, false);
    
        //Fallback
        if(!$result)
            throw new Exception('Cannot insert page with values "'.$url.',"'.$url.',"'.$type.',"'.$status.'"');
        
        // Complete Query
        return $result;
    }
    
    /**
     * Update Page Site Status 
     */
    public static function update_site_status($url, $new_status){
    
        // SQL
        $sql = 'UPDATE `sites` SET `status` = ? WHERE `url` = ?';
        $params = array($new_status, $url);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception('Cannot update "'.$site.'" site status to "'.$new_status.'"');
    }

    /**
     * Get DB Entries
     * @param array filters  
     * [ array ('name' => $name, 'value' => $value, 
     * 'operator' => '=' ) ]
     * @param string page
     */
    public static function get_db_entries(
        $table, $filters = [], $page = 1
    ){

        // Set entries per page.
        $entries_per_page = self::ITEMS_PER_PAGE;
        $page_offset = ($page-1) * $entries_per_page;

        // Create 'total_pages' SQL.
        $total_pages_sql = 'SELECT COUNT(*) FROM 
            `'.$table.'`';
    
        // Create 'content' SQL.
        $content_sql = 'SELECT * FROM `'.$table.'`';
        $params = array();

        // Add optional filters to content and total_pages.
        $filter_count = count($filters);
        $filters_sql = '';
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                if(empty($filter['operator'])){
                    $operator = '=';
                }else{
                    $operator = $filter['operator'];
                }
                $filters_sql.= '`'.$filter['name'].'` '.$operator
                    .' ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
        }

        // Add filters and page limit.
        $total_pages_sql.= $filters_sql;
        $content_sql.= $filters_sql.' LIMIT '.$page_offset
            .', '.$entries_per_page;

        // Run 'total_pages' SQL.
        $total_pages_result = self::query(
            $total_pages_sql, $params, true
        );
        $total_pages_rows = $total_pages_result->fetch_array()[0];
        $total_pages = ceil($total_pages_rows / $entries_per_page);
    
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
            'total_entries' => $total_pages_rows,
            'total_pages' => $total_pages,
            'content' => $content
        ];
        return $data;
    
    }
    
    /**
     * Add DB Column
     */
    public static function add_db_column(
        $table, $column_name, $column_type){
    
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
    public static function delete_db_column(
        $table, $column_name){
    
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
    public static function db_column_exists(
        $table, $column_name
    ){
    
        // SQL.
        $sql = 'SELECT '.$column_name.' FROM `
            '.$table.'` ';
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
     * Update DB Column Data
     */
    public static function update_db_column_data(
        $table, $fields, $data, $id
    ){
    
        // SQL
        $sql = 
            'UPDATE `'.$db.'` SET `'.$column_name.'` 
            = ? WHERE `id` = ?';
        $params = array($data, $id);
    
        // Query
        $result = self::query($sql, $params, false);
    
        // Result
        if(!$result)
            throw new Exception(
                'Cannot update  "'.$table.'" - "'.$id.'" 
                data "'.$data.'". for 
                "'.$column_name.'"'
            );
    
    }

    /**
     * Update DB rows
     * @param string table
     * @param array fields [ array ( 
     * 'name' => $name, 'value' => $value, 'page' => $page) ]
     * @param array filters [ array ( 
     * 'name' => $name, 'value' => $value, 'page' => $page) ]
     */
    public static function update_db_rows(
        $table, $fields, $filters = [],
    ){

        // Prepare the SQL.
        $sql = 'UPDATE `'.$table.'` SET ';

        // Add fields that we're updating.
        $field_count = count($filters);
        $field_sql = '';
        if($field_count > 0){
            $field_iteration = 0;
            foreach ($fields as $field){
                $field_sql.= '`'.$field['name'].'` = ?';
                $params[] = $field['value'];
                if(++$field_iteration != $field_count)
                    $field_sql.= ', ';
            }
        }
        $sql.= $field_sql;

        // Add optional filters.
        $filter_count = count($filters);
        $filters_sql = '';
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $filters_sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
        }
        $sql.= $filters_sql;

        // Query
        $results = self::query($sql, $params, false);

        // Complete Query
        return $results;

    }
    
    /**
     * Add DB Entry
     * @param string table
     * @param array fields  
     * [ array('name' => $name, 'value' => $value) ]
     */
    public static function add_db_entry($table, array $fields)
    {
        
        // We're going to use the field count a few times,
        // so let's create the variable first.
        $field_count = count($fields);

        // Let's reformat fields into something easier for
        // MySQL to deal with.
        $field_names = ''; 
        $field_values = array();
        if(!empty($fields)){
            $counter = 0; 
            foreach($fields as $field){
                $counter++;
                $field_names.= '`'.$field['name'].'`';
                if($counter !== $field_count)
                    $field_names.= ', ';
                array_push($field_values, $field['value']);
            }
        }

        // Prepare the SQL.
        $sql = 'INSERT INTO `'.$table.'` ('.$field_names.') ';
        $sql.= 'VALUES ('.str_repeat('?, ', $field_count).')';
        $sql = str_replace(', )', ')', $sql);
        
        // Query
        $result = self::query($sql, $field_values, false);

        // Fallback
        if(!$result)
            throw new Exception('Cannot add DB entry');
        
        // Complete Query
        return $result;
        
    }

    /**
     * Count DB rows
     * @param string table
     * @param array filters [ array ( 
     * 'name' => $name, 'value' => $value, 'page' => $page) ]
     */
    public static function count_db_rows(
        $table, $filters = []
    ){

        // SQL
        $sql = 'SELECT COUNT(*) AS TOTAL FROM `'.$table.'`';
        $params = array();

        // Add optional filters to content and total_pages.
        $filter_count = count($filters);
        $filters_sql = '';
        if($filter_count > 0){
            $filters_sql = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){
                $filters_sql.= '`'.$filter['name'].'` = ?';
                $params[] = $filter['value'];
                if(++$filter_iteration != $filter_count)
                    $filters_sql.= ' AND ';
        
            }
        }
        $sql.= $filters_sql;


        // Query
        $results = self::query($sql, $params, true);

        // Result
        $data = $results->fetch_object()->TOTAL;
        return $data;

    }

    /**
     * Add Meta
     */
    public static function add_meta(
        $meta_name, $meta_value = NULL
    ){
    
        // Serialize meta_value.
        if(is_array($meta_value)){
            $meta_value = serialize($meta_value);
        }
    
        // SQL
        $sql = 'INSERT INTO `meta` (`meta_name`, `meta_value`) VALUES (?, ?)';
        $params = array($meta_name, $meta_value);
        
        // Query
        $result = self::query($sql, $params, false);
        print_r($sql);

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
                `status` varchar(200) DEFAULT "unread",
                `type` varchar(200) NOT NULL,
                `source` varchar(200) NOT NULL,
                `url` text,
                `integration_uri` varchar(200) DEFAULT NULL,
                `message` text,
                `meta` text,
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
    
        // First, create the meta table
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
        $result_1 = self::query($sql_1, $params, false);

        // Fallback
        if(!$result_1)
            throw new Exception('Error creating table: "'.$result_1->error.'"');

        // Now, create the content in the meta table.
        $sql_2 = '
            INSERT INTO `meta` (meta_name, meta_value)
            VALUES 
            ("active_integrations", ?),
            ("scan_status", ?),
            ("scanable_pages", ?),
            ("last_scan_time", ?);

        ';
        $default_active_integrations = serialize(array('little_forest'));
        $default_scan_status = '';
        $default_scanable_pages = serialize(array());
        $default_last_scan_time = '';
        $params = array(
            $default_active_integrations,
            $default_scan_status, $default_scanable_pages,
            $default_last_scan_time
        );

        // Query 2
        $result_2 = self::query($sql_2, $params, false);

        // Fallback
        if(!$result_2)
            throw new Exception('Error creating table: "'.$result_2->error.'"');
        
    }
    
    /**
     * Create Sites Table
     */
    public static function create_sites_table(){
    
        // SQL
        $sql = 
            'CREATE TABLE `sites` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `url` text COLLATE utf8mb4_bin NOT NULL,
                `type` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "static",
                `status` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT "active",
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Fallback
        if(!$result)
            throw new Exception('Error creating sites table: "'.$result->error.'"');
        
    }
    
    /**
     * Table Exists
     */
    public static function table_exists($table_name){
    
        // SQL
        $db_name = $GLOBALS['DB_NAME'];
        $sql = "
            SELECT * 
            FROM information_schema.tables
            WHERE table_schema = '$db_name' 
                AND table_name = '$table_name'
            LIMIT 1;
        ";
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
    
        // Results
        if($result && $result->num_rows === 1){
           return true;
        }else{
            return false;
        }
    
    }

}
