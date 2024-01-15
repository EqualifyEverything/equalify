<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document contains all the integrations with the 
 * app's MySQL database.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
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
                $GLOBALS['DB_SOCKET']
            ); 
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
     * 'operator' => '=', 'condition' => 'AND', 'type' = '' ) ]
     */
    private static function filters($filters){

        // Our goal is to output SQL and params.
        $output = array(
            'sql' => '',
            'params' => array()
        );
        
        // Let's start a count so we can tell when our
        // loop ends.
        $filter_count = count($filters);

        // We only need to prepare SQL if filters exist.
        if($filter_count > 0){
            
            // We start a loop to set up our filters.
            $output['sql'] = ' WHERE ';
            $filter_iteration = 0;
            foreach ($filters as $filter){

                // Filters without type have the standard 
                // markup
                if(empty($filter['type'])){

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

                // Other filters are setup differently.
                }else{

                    // Add "find in set" variables.
                    if($filter['type'] === 'find_in_set'){
                        $copy = $filter['value'];
                        $output['sql'].= '(';
                        foreach($filter['value'] as $value){
                            $output['sql'].= ' FIND_IN_SET(\''.$value['value'].'\', '.$value['column'].')';
                            if(next($copy)){
                                $output['sql'].= ' OR ';
                            }else{
                                $output['sql'].= ')';
                            }
                        }
                    }      
                                 
                } 

            }

        }

        // Let's put everything together.
        return $output;

    }

    /**
     * Get DB Rows
     * @param array filters  
     * [ array ('name' => $name, 'value' => $value, 
     * 'operator' => '=', 'condition' => 'AND' ) ]
     * @param string page
     * @param string rows_per_page
     * @param string order_by
     */
    public static function get_db_rows(
        $table, array $filters = [], $page = 1, 
        $rows_per_page = '', $order_by = ''
    ){

        // Set rows per page.
        if(empty($rows_per_page))
            $rows_per_page = self::ITEMS_PER_PAGE;
        $page_offset = ($page-1) * $rows_per_page;

        // Create 'total_pages' SQL.
        $total_pages_sql = 'SELECT COUNT(*) FROM 
            `'.$table.'`';
    
        // Create 'content' SQL.
        $content_sql = 'SELECT * FROM `'.$table.'`';

        // Add optional filters to content and total_pages.
        $filters = self::filters($filters);

        // Add optional "ORDER BY".
        if(!empty($order_by))
            $content_sql.= ' ORDER BY `'.$order_by.'`';

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
     * Get Joined DB
     * @param string requesting
     * @param array site_ids
     */
    public static function get_joined_db(
        $requesting, $sites = []){

            // TODO: Add "WHERE" with site id, so that we only equalify active site alerts.

        // Set conditions based on type.
        if($requesting == 'equalified_alerts'){
            $table1 = 'alerts';
            $table2 = 'queued_alerts';
            $selected_columns = "DISTINCT $table1.id";
        }elseif($requesting == 'new_alerts'){
            $table1 = 'queued_alerts';
            $table2 = 'alerts';
            $selected_columns = "DISTINCT $table1.id, ";
            $selected_columns.= "$table1.url, $table1.message, ";
            $selected_columns.= "$table1.status, $table1.site_id, ";
            $selected_columns.= "$table1.source, $table1.tags, ";
            $selected_columns.= "$table1.more_info, $table1.more_info";
        }
        
        // SQL.
        $sql = "SELECT $selected_columns FROM $table1 ";
        $sql.= "LEFT JOIN $table2 ";
        $sql.= "ON $table1.url=$table2.url ";
        $sql.= "AND $table1.message=$table2.message ";
        $sql.= "AND $table1.site_id=$table2.site_id ";
        $sql.= "AND $table1.source=$table2.source ";
        $sql.= "WHERE $table2.id IS NULL AND $table1.archived = 0 ";
        if(!empty($sites)){
            foreach($sites as $site){
                $sql.= "AND $table1.site_id = $site->id ";
            }
        }
        $params = array();
    
        // Query
        $result = self::query($sql, $params, true);
    
        // Result
        $content = [];
        if($result->num_rows > 0){
            while($row = $result->fetch_object()){
                $content[] = $row;
            }
        }
        return $content;

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
     * 'operator' => '=', 'condition' => 'AND' ) ]
     * 'operator' => '=' ) ]
     */
    public static function update_db_rows(
        $table, $fields, $filters = []
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
        // these field names should be represented in the
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

        // Let's execute the query
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
     * Delete DB Entries
     * @param string table
     * @param array filters [ array ( 
     * 'name' => $name, 'value' => $value, 'page' => $page) ]
     */
    public static function delete_db_entries($table, array $filters = array()){
    
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
     * Get Meta Value
     */
    public static function get_meta_value($meta_name, $strict_check = null){

        // SQL
        $sql = 'SELECT `meta_value` FROM `meta` WHERE `meta_name` = ?';
        $params = array($meta_name);
    
        // Query
        $results = self::query($sql, $params, true);

        // Returns meta_value.
        $data = $results->fetch_object();

        if(empty($data)){
            if ($strict_check) {
                return;
            }
            return false;
        } else{
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
            "CREATE TABLE `alerts` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `status` varchar(200) NOT NULL DEFAULT 'active',
                `source` varchar(200) NOT NULL,
                `site_id` bigint(20) NOT NULL,
                `url` text,
                `message` text,
                `tags` text,
                `more_info` longtext,
                `archived` BOOLEAN NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $params = array();
        
        // Query
        $result = self::query($sql, $params, false);
        
    }

    /**
     * Create Tags Table
     */
    public static function create_tags_table(){
    
        // Let's create the tags table.
        $sql = 
            "CREATE TABLE `tags` (
                `slug` varchar(255) NOT NULL,
                `title` varchar(255) NOT NULL,
                `category` varchar(255) DEFAULT NULL,
                `description` text DEFAULT NULL,
                PRIMARY KEY (slug)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $params = array();
        $result = self::query($sql, $params, false);

        // Now we need to register integration tags, since
        // the plugin is activated by default.
        require_once 'integrations.php';
        require_once 'helpers/register_tags.php';
        $integration_tags = get_integration_tags(
            'axe'
        );
        if( !empty($integration_tags) ){
            register_tags($integration_tags);
        }

    }

    /**
     * Create Queued Alerts Table
     */
    public static function create_queued_alerts_table(){
    
        // SQL
        $sql = 
            "CREATE TABLE `queued_alerts` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `status` varchar(200) NOT NULL DEFAULT 'active',
                `source` varchar(200) NOT NULL,
                `site_id` bigint(20) NOT NULL,
                `url` text,
                `message` text,
                `tags` text,
                `more_info` text,
                `archived` BOOLEAN NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
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
        "CREATE TABLE `meta` (
            `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `meta_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
            `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
            PRIMARY KEY (`meta_id`),
            UNIQUE KEY `option_name` (`meta_name`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
        ";
        $params = array();

        // Query 1
        self::query($sql_1, $params, false);

        // Let's create the params first, so we can do special
        // things with them and global meta variables.
        $params = array();

        // Optionally set global meta variables.
        $added_sql = '';
        if(isset($GLOBALS['a11ywatch_key'])){
            $added_sql.= "('a11ywatch_key', ?),";
            $params[] = $GLOBALS['a11ywatch_key'];
        }
        if(isset($GLOBALS['axe_uri'])){
            $added_sql.= "('axe_uri', ?),";
            $params[] = $GLOBALS['axe_uri'];
        }

        // Now, create the content in the meta table
        // with axe, since it's on by default. 
        $sql_2 = "
            INSERT INTO `meta` (meta_name, meta_value)
            VALUES".
            $added_sql.
            "('active_integrations', ?),
            ('scan_status', ?),
            ('scan_schedule', ?),
            ('scan_log', ?),
            ('scannable_pages', ?),
            ('last_scan_time', ?);
        ";
        
        // Default active_integrations.
        $params[] = serialize(array('axe'));

        // Default scan_status.
        $params[] = '';
        
        // Default scan_schedule.
        $params[] = 'manually';
        
        // Default scan_log.
        $params[] = '';
        
        // Default scannable_pages.
        $params[] = serialize(array());

        // Default last_scan_time.
        $params[] = '';

        // Query 2
        self::query($sql_2, $params, false);

    }
    
    /**
     * Create Scan Profiles Table
     */
    public static function create_scan_profiles_table(){

        // SQL
        $sql = 
            "CREATE TABLE `scan_profiles` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `url` text COLLATE utf8mb4_bin NOT NULL,
                `type` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT 'static',
                `status` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT 'active',
                `scanned` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;";
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
