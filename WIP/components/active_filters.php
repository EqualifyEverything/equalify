<?php
// Create a button list of filters for easy removal.
function the_active_filters($report_id, $report_filters) {

    // Display Filters
    foreach ($report_filters as $filter) {
        if (isset($filter['filter_type'], $filter['filter_value'], $filter['filter_id']) && $filter['filter_type'] !== 'statuses') {
            // Store the filter data in variables
            $filter_type = $filter['filter_type'];
            $filter_value = $filter['filter_value'];
            $filter_source = $filter['filter_source'];
            $filter_id = $filter['filter_id'];
            ?>

<div class="btn-group btn-group-sm mb-2" role="group" aria-label="Report Filter">
    <span class="btn btn-outline-secondary disabled text-primary-emphasis">
        <span class="fw-semibold pe-1"><?php echo $filter_type;?>:</span><?php echo htmlspecialchars($filter_value); ?>
    </span>
    <a class="btn btn-outline-secondary remove-filter-button" href="actions/remove_filter.php?report_id=<?php echo $report_id .'&filter_type='. $filter_type . '&filter_value='. $filter_value . '&filter_id=' . $filter_id . '&filter_source=' . $filter_source; ?>">
        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
        </svg>
        <span class="visually-hidden">Remove Filter</span>
    </a>
</div>
 
            <?php
        }
    }
}
?>