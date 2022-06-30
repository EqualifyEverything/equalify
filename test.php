<?php
function add_db_entry($db, array $fields){
    
    // We're going to use the field count a few times,
    // so let's create the variable first;
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

    // SQL
    $sql = 'INSERT INTO `'.$db.'` ('.$field_names.') VALUES ';
    $sql.= '('.str_repeat('?', $field_count).')';
    
    // Query
    print_r($sql);
    
}


$alert_arguments = array(
    array(
        'name' => 'source',
        'value'=> 'source value'
    ),
    array(
        'name' => 'url',
        'value'=> 'url value'
    ),
    array(
        'name' => 'integration_uri',
        'value'=> 'integration_uri value'
    ),
    array(
        'name' => 'type',
        'value'=> 'type value'
    ),
    array(
        'name' => 'message',
        'value'=> 'message value'
    ),
    array(
        'name' => 'meta',
        'value'=> 'meta value'
    ),
);
add_db_entry('alerts', $alert_arguments);
?>