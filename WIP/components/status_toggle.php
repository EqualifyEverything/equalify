<?php
// Toggle statuses
function the_status_toggle($report_id, $report_filters) {

    // Parse $report_filters to check active statuses
    $active_statuses = [];
    parse_str($report_filters, $parsed_flters);
    if (isset($parsed_flters['statuses'])) {
        $active_statuses = explode(',', $parsed_flters['statuses']);
    }

    function generateStatusLink($status, $active_statuses, $report_id) {
        $all_statuses = ['equalified', 'active', 'ignored'];
        $is_active = in_array($status, $active_statuses);
        $query = "report_id=$report_id";
        $class = '';

        if ($is_active) {

            // Link to remove the filter
            $filter_string = http_build_query(['filter_type' => 'statuses', 'filter_value' => $status, 'filter_id' => $status, 'filter_change' => 'remove']);
            $query .= "&filters[]=" . urlencode($filter_string);
            $class = 'active';
            
        }elseif (empty($active_statuses)){

            // With no filters active, every other filter will be added on click.
            foreach ($all_statuses as $other_status) {
                if ($other_status !== $status) {
                    $filter_string = http_build_query(['filter_type' => 'statuses', 'filter_value' => $other_status, 'filter_id' => $other_status, 'filter_change' => 'add']);
                    $query .= "&filters[]=" . urlencode($filter_string);
                }
            }
            $class = 'active';

        } else {
            // Add filter
            $filter_string = http_build_query(['filter_type' => 'statuses', 'filter_value' => $status, 'filter_id' => $status, 'filter_change' => 'add']);
            $query .= "&filters[]=" . urlencode($filter_string);
        }
    
        return "<a id='$status' class='nav-link text-white $class' href='actions/queue_report_filter_change.php?$query'>
                    <span class='h1' id='{$status}_count'></span><br>" . ucfirst($status) . "</span>
                </a>";
    }
    
    
?>

<div id="reports_filter" class="my-2 rounded-3 bg-secondary text-center p-2 border">
    <ul class="nav d-flex justify-content-around" aria-label="Click to toggle any of these statuses. Toggling a status will hide/show related data.">
        <li class="nav-item">
            <?php echo generateStatusLink('equalified', $active_statuses, $report_id); ?>
        </li>
        <li class="nav-item">
            <?php echo generateStatusLink('active', $active_statuses, $report_id); ?>
        </li>
        <li class="nav-item">
            <?php echo generateStatusLink('ignored', $active_statuses, $report_id); ?>
        </li>
    </ul>
</div>
<script>
    function fetchStatusCounts() {
        const xhr = new XMLHttpRequest();
        const url = 'api/?request=statuses&<?php echo $report_filters; ?>';
        xhr.open('GET', url);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const statuses = response.statuses;
                document.getElementById('equalified_count').textContent = statuses.equalified !== undefined ? statuses.equalified : 0;
                document.getElementById('active_count').textContent = statuses.active !== undefined ? statuses.active : 0;
                document.getElementById('ignored_count').textContent = statuses.ignored !== undefined ? statuses.ignored : 0;
            } else {
                console.error('Error loading status counts.');
            }
        };
        xhr.onerror = function() {
            console.error('Error on AJAX request for status counts.');
        };
        xhr.send();
    }

    // Initial fetch
    fetchStatusCounts();
</script>

<?php
}