<?php
$filters = array(
    array(
        'name' => 'status',
        'value' => 'active',
        'operator' => '=',
        'condition' => 'AND'
    ),
    array(
        'name' => 'site_id',
        'value' => array(
            array(
                'name' => 'site_id',
                'value' => 1,
                'operator' => '=',
                'condition' => 'OR'
            ),
            array(
                'name' => 'site_id',
                'value' => 2,
                'operator' => '=',
                'condition' => 'OR'
            ),
        )
    )
);
$sql = 'UPDATE `alerts` SET ';


/**
 * Prepare DB Filters
 */
function prepare_db_filters($filters){

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
$prepared_filters = prepare_db_filters($filters);
$sql.= $prepared_filters['sql'];
echo $sql;