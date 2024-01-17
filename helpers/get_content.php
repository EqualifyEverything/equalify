<?php
function get_content($table, $id){
    global $pdo;

    // Table names can be used in the ID column if we turn them singular.
    if (substr($table, -3) === 'ies'){
        $id_column = substr($table, 0, -3) . 'y_id';
    }elseif (substr($table, -1) === 's') {
        $id_column = substr($table, 0, -1) . '_id';
    }else{
        $id_column = $table.'_id';
    }

    // Query to fetch 
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $id_column = :id");
    $stmt->execute(['id' => $id]);
    $content = $stmt->fetchObject() ?: 'Content Not Found';

    return $content;

}
