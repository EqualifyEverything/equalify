<?php
require_once('../helpers/get_scans_count.php');
require_once('../helpers/get_scans.php');

function get_results($results_per_page, $offset = NULL, $filters = NULL, $columns = NULL, $joined_columns = NULL){

    $scans = get_scans($results_per_page, $offset);
    $total_scans = get_scans_count();
    $total_pages = ceil($total_scans / $results_per_page);

    $response = [
        'scans' => $scans,
        'totalScans' => $total_scans,
        'totalPages' => $total_pages
    ];

    return $response;

}