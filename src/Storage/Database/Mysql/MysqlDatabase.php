<?php

namespace Equalify\Storage\Database\Mysql;

use Equalify\Storage\Database\IDatabase;
use mysqli;

/**
 * @author Chris Kelly (TolstoyDotCom)
 */
class MysqlDatabase implements IDatabase {

    // Set the records per page.
    private const ITEMS_PER_PAGE = 10;

    private $conn = null;

    /**
     * Copied from db.php.
     * @todo: replace or at least check for security
     */
    private function connect() {
        if ($this->conn) {
            return $this->conn;
        }
        else {
            $this->conn = new mysqli(
                $_ENV['DB_HOST'], 
                $_ENV['DB_USERNAME'], 
                $_ENV['DB_PASSWORD'], 
                $_ENV['DB_NAME'],  
                $_ENV['DB_PORT'],
                //$_ENV['DB_SOCKET']
            ); 
            if($this->conn->connect_error){
                throw new Exception(
                    'Cannot connect to database: '
                    .$this->conn->connect_error
                );
            }
            return $this->conn;
        }
    }

    public function getRows($table, array $filters = [], $page = 1, $rows_per_page = '', $order_by = '') : array {
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
        $filters = $this->filters($filters);

        // Add optional "ORDER BY".
        if(!empty($order_by))
            $content_sql.= ' ORDER BY `'.$order_by.'`';

        // Add filters and page limit.
        $total_pages_sql.= $filters['sql'];
        $content_sql.= $filters['sql'].' LIMIT '.$page_offset
            .', '.$rows_per_page;

        // Run 'total_pages' SQL.
        $total_pages_result = $this->query(
            $total_pages_sql, $filters['params'], true
        );
        $total_pages_rows = $total_pages_result->fetch_array()[0];
        $total_pages = ceil($total_pages_rows / $rows_per_page);
    
        // Run 'content' SQL
        $content_results = $this->query($content_sql, $filters['params'], true);
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
     * Filters helper.
     * @param array filters  
     * [ array ('name' => $name, 'value' => $value, 
     * 'operator' => '=', 'condition' => 'AND', 'type' = '' ) ]
     */
    private function filters($filters){

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
     * Query helper.
     *
     * @param string $sql The query SQL.
     * @param array $params The query parameters to bind.
     * @param boolean $return If we're expecting a result.
     * @return mysqli_result|boolean
     */
    private function query($sql, $params, $return) 
    {
        $connection = $this->connect();
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

}
