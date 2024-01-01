<?php
// Create a button list of filters for easy removal.
function the_active_filters($report_id, $report_filters) {

    // Display Filters
    if(empty($report_filters)){
?>
    
<p>No active filters.</p>';

<?php
    }else{
?>
<div class="row" id="active_filters">
    <ul class="col">
    
        <?php
        foreach ($report_filters as $filter):
            // Store the filter data in variables
            $filter_type = $filter['filter_type'];
            $filter_value = $filter['filter_value'];
            $filter_id = $filter['filter_id'];

            // Link to remove the filter
            $filter_string = http_build_query(['filter_type' => $filter_type, 'filter_value' => $filter_value, 'filter_id' => $filter_id, 'filter_change' => 'remove']);
            $query = "report_id=$report_id&filters[]=" . urlencode($filter_string);
        ?>

        <li class="btn-group btn-group-sm mb-2" role="group" aria-label="Report Filter">
            <span class="btn btn-outline-secondary disabled text-primary-emphasis">
                <span class="fw-semibold pe-1"><?php echo $filter_type;?>:</span><?php echo htmlspecialchars($filter_value); ?>
            </span>
            <a class="btn btn-outline-secondary remove-filter-button" href="actions/queue_report_filter_change.php?<?php echo $query;?>">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                </svg>
                <span class="visually-hidden">Remove Filter</span>
            </a>
        </li>
        
        <?php
        endforeach;
        ?>

    </ul>
</div>

<?php
    }
}
?>