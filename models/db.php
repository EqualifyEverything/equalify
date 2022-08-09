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
                $GLOBALS['DB_PORT']); 
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
     * Filters helper.
     * @param array filters  
     * [ array ('name' => $name, 'value' => $value, 
     * 'operator' => '=', 'condition' => 'AND' ) ]
     */
    private static function filters($filters){

        // Our goal is to output sql and params.
        $output = array(
            'sql' => '',
            'params' => array()
        );
        
        // Let's start a count so we can tell when our
        // loop ends.
        $filter_count = count($filters);

        // We only need to prepare SQL if filters exist.
        if($filter_count > 0){
            
            // We start a loop to setup our filters.
            $output['sql'] = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){

                // The default condition 'AND'.
                if(empty($filter['condition'])){
                    $condition = 'AND';

                // You can use other conditions like 'OR'.
                }else{
                    $condition = $filter['condition'];
                }

                // The default operator is '='.
                if(empty($filter['operator'])){
                    $operator = '=';

                // You can use other operators like 'IS'.
                }else{
                    $operator = $filter['operator'];
                }

                // You can nest filters in filter values.
                if(is_array($filter['value'])){

                    // Start the sub filter SQL.
                    $sub_filter_iteration = 0;
                    $sub_filter_count = count($filter['value']);
                    $output['sql'].= '(';

                    // We only support one sub filter.
                    foreach($filter['value'] as $sub_filter){

                        // Like we did before, let's build the
                        // sql and add to params.
                        if(empty($sub_filter['condition'])){
                            $sub_condition = 'AND';
                        }else{
                            $sub_condition = $sub_filter['condition'];
                        }
                        if(empty($sub_filter['operator'])){
                            $sub_operator = '=';        
                        }else{
                            $sub_operator = $sub_filter['operator'];
                        }
                        $output['sql'].= '`'.$sub_filter['name'].'` '
                        .$sub_operator.' ?';
                        if(++$sub_filter_iteration != $sub_filter_count)
                            $output['sql'].= ' '.$sub_condition.' ';
                        $output['params'][] = $sub_filter['value'];
                        
                    }

                    // End the sub filter SQL.
                    $output['sql'].= ')';
                    
                }else{

                    // If the filter doesn't have array, we can just
                    // assemble it with existing content.
                    $output['sql'].= '`'.$filter['name'].'` '.$operator
                    .' ?';
                    $output['params'][] = $filter['value'];

                }

                if(++$filter_iteration != $filter_count)
                    $output['sql'].= ' '.$condition.' ';

            }

        }

        // Let's put everything together.
        return $output;

    }

    /**
     * Get DB Rows
     * @param array filters  
     * [ array ('name' => $name, 'value' => $value, 
     * 'operator' => '=' ) ]
     * @param string page
     * @param string rows_per_page
     * @param string operator
     */
    public static function get_db_rows(
        $table, $filters = [], $page = 1, 
        $rows_per_page = self::ITEMS_PER_PAGE,
        $operator = 'AND'
    ){

        // Set rows per page.
        $page_offset = ($page-1) * $rows_per_page;

        // Create 'total_pages' SQL.
        $total_pages_sql = 'SELECT COUNT(*) FROM 
            `'.$table.'`';
    
        // Create 'content' SQL.
        $content_sql = 'SELECT * FROM `'.$table.'`';

        // Add optional filters to content and total_pages.
        $filters = self::filters($filters);

        // Add filters and page limit.
        $total_pages_sql.= $filters['sql'];
        $content_sql.= $filters['sql'].' LIMIT '.$page_offset
            .', '.$rows_per_page;

        // Run 'total_pages' SQL.
        $total_pages_result = self::query(
            $total_pages_sql, $filters['params'], true
        );
        $total_pages_rows = $total_pages_result->fetch_array()[0];
        $total_pages = ceil($total_pages_rows / $rows_per_page);
    
        // Run 'content' SQL
        $content_results = self::query($content_sql, $filters['params'], true);
        $content = [];
        if($content_results->num_rows > 0){
            while($row = $content_results->fetch_object()){
                $content[] = $row;
            }
        }
    
        // Create and return data.
        $data = [
            'total_rows' => $total_pages_rows,
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
     * Update DB Rows
     * @param string table
     * @param array fields [ array ( 
     * 'name' => $name, 'value' => $value, 'page' => $page) ]
     * @param array filters  
     * [ array ('name' => $name, 'value' => $value, 
     * 'operator' => '=' ) ]
     * @param string operator 
     */
    public static function update_db_rows(
        $table, $fields, $filters = [], $operator = 'AND'
    ){

        // Prepare the SQL.
        $sql = 'UPDATE `'.$table.'` SET ';

        // Add fields that we're updating.
        $field_count = count($fields);
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
        $filters = self::filters($filters);
        $sql.= $filters['sql'];

        //Add filters parameters.
        foreach ($filters['params'] as $param){
            $params[] = $param;
        }

        // Query
        $results = self::query($sql, $params, false);

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
     * Add DB Rows
     * @param string table
     * @param array data  
     * [ array($field => $value, ...) ]
     */
    public static function add_db_rows(
        $table, array $data
    ){
        
        // Let's first format the field names. Note:
        // these fieldnames should be represented in the
        // first set of rows as they are in the last.
        $field_names = implode(', ',array_keys($data[0]));  
        
        // Now we format the unprepared values.
        $values = '';
        $value_count = 0;
        foreach ($data as $datum){
            $value_count++;    
            $values.= '('.str_repeat('?, ', count(
                $datum
            ));
            $values = substr($values, 0, -2);
            $values.= ')';
            if($value_count !== count($data))
                $values.= ', ';
        }
        
        // All the values need to go into their own params.
        $value_count = 0;
        $params = array();
        foreach ($data as $datum){
            foreach($datum as $value){
                if(is_array($value)){
                    $value = serialize($value);
                }                
                array_push($params, $value);
            }
        }
        
        // Time to prepare the SQL!
        $sql = 'INSERT INTO `'.$table.'` ('.$field_names
            .') VALUES '.$values.';';

        // Let's esecute the query
        $result = self::query($sql, $params, true);
        
        // And complete the query.
        return $result;

    }

    /**
     * Update DB Entry
     * @param string table
     * @param string id
     * @param array fields  
     * [ array('name' => $name, 'value' => $value) ]
     */
    public static function update_db_entry($table, $id, array $fields)
    {

        // Prepare the UPDATE SQL.
        $sql = 'UPDATE `'.$table.'` SET ';
        $field_count = count($fields);
        $params = array();
        $counter = 0;
        foreach($fields as $field){
            $counter++;
            $sql.= $field['name'].' = ?';
            if($counter !== $field_count)
                $sql.= ', ';
            $params[] = $field['value'];
        }

        // Finish up the SQL.
        $sql.= ' WHERE id = ?';
        $params[] = $id;

        // Query
        $result = self::query($sql, $params, true);
        
        // Complete Query
        return $result;
        
    }

    /**
     * Delete DB Entry
     * @param string table
     * @param array filters [ array ( 
     * 'name' => $name, 'value' => $value, 'page' => $page) ]
     */
    public static function delete_db_entry($table, $filters){
    
        // SQL
        $sql = 'DELETE FROM `'.$table.'`';

        // Add optional filters.
        $filters = self::filters($filters);
        $sql.= $filters['sql'];

        // Query
        $result = self::query($sql, $filters['params'], false);
    
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

        // Add optional filters.
        $filters = self::filters($filters);
        $sql.= $filters['sql'];

        // Query
        $results = self::query($sql, $filters['params'], true);

        // Result
        $data = $results->fetch_object()->TOTAL;
        return $data;

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
            throw new Exception('Cannot delete meta using "'.$meta_name.'"');
    
        // Complete Query
        return $result;

    }

    /**
     * Update Meta 
     * @param string meta_name 
     * @param string meta_value
     * @param bool concatenate
     */
    public static function update_meta_value(
        $meta_name, $meta_value, $concatenate = false
    ){

        // SQL
        $sql = 'UPDATE `meta` SET `meta_value` = ';
        
        // Optional concatenation.
        if($concatenate == true){
            $sql.= 'concat(`meta_value`,?)';
        }else{
            $sql.= '?';
        }
        
        // Finish SQL and params.
        $sql.= ' WHERE `meta_name` = ?';
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
                `status` varchar(200) NOT NULL DEFAULT "active",
                `type` varchar(200) NOT NULL,
                `source` varchar(200) NOT NULL,
                `site_id` bigint(20) NOT NULL,
                `url` text,
                `message` text,
                `meta` text,
                `archived` BOOLEAN NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
        
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

        // Now, create the content in the meta table.
        $sql_2 = '
            INSERT INTO `meta` (meta_name, meta_value)
            VALUES 
            ("active_integrations", ?),
            ("scan_status", ?),
            ("scan_schedule", ?),
            ("scan_log", ?),
            ("scannable_pages", ?),
            ("last_scan_time", ?);
        ';
        $default_active_integrations = serialize(array('little_forest'));
        $default_scan_status = '';
        $default_scan_schedule = 'manually';
        $default_scan_log = '';
        $default_scannable_pages = serialize(array());
        $default_last_scan_time = '';
        $params = array(
            $default_active_integrations,
            $default_scan_status, $default_scan_schedule,
            $default_scan_log, $default_scannable_pages, 
            $default_last_scan_time
        );

        // Query 2
        $result_2 = self::query($sql_2, $params, false);
        
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
                `scanned` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;';
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
        
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
