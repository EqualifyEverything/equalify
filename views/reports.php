<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the reports view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Sometimes this view is filtered to a report's data.
if(!empty($_GET['report'])){

    // Load the data of a selected report.
    $filtered_to_report = array(
        array(
            'name' => 'meta_name',
            'value' => $_GET['report']
        )
    );
    $report = (array)DataAccess::get_db_rows(
        'meta', $filtered_to_report
    )['content'][0];

    // We need to unserialize the meta from the report.
    $report_meta = unserialize($report['meta_value']);

    // No archived alerts are shown in reports.
    array_push($report_meta, array(
        'name' => 'archived',
        'value' => 0
    ));

    // Let's prepare the tags to be queried by the db.
    $tags = array();
    if(!empty($report_meta)){
        foreach ($report_meta as $key => $meta ){
            if($meta['value'] == 'on'){
                $tags[] = array(
                    'value' => $meta['name'],
                    'column' => 'tags'
                );
                unset($report_meta[$key]);
            }
        }
    }
    if(!empty($tags)){
        $report_meta[] = array(
            'name' => '',
            'type' => 'find_in_set',
            'value' => $tags
        );
    }

// We also have special presets.
}elseif(!empty($_GET['preset'])){

    // The active preset contains all alerts.
    if($_GET['preset'] == 'all'){
        $report = array(
            'meta_name' => 'all',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'All Alerts'
                ), array(
                    'name' => 'archived',
                    'value' => 0
                )
            )
        );
        $report_meta = $report['meta_value'];    

    // The ignored preset contains all 'ignored' alerts.
    }elseif($_GET['preset'] == 'ignored'){
        $report = array(
            'meta_name' => 'ignored',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'Ignored Alerts'
                ),
                array(
                    'name' => 'status',
                    'value' => 'ignored'
                ), array(
                    'name' => 'archived',
                    'value' => 0
                )
            )
        );
        $report_meta = $report['meta_value'];

    // The equalified preset contains all 'equalified' 
    // alerts.
    }elseif($_GET['preset'] == 'equalified'){
        $report = array(
            'meta_name' => 'equalified',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'Equalified Alerts'
                ),
                array(
                    'name' => 'status',
                    'value' => 'equalified'
                ), array(
                    'name' => 'archived',
                    'value' => 0
                )
            )
        );
        $report_meta = $report['meta_value'];
        
    }

    // We have no tags.
    $tags = array();

}else{

    // When there's no report data, we get active alerts.
    $report = array(
        'meta_name' => 'active',
        'meta_value' => array(
            array(
                'name' => 'title',
                'value' => 'Active Alerts'
            ),
            array(
                'name' => 'status',
                'value' => 'active'
            ), array(
                'name' => 'archived',
                'value' => 0
            )
        )
    );
    $report_meta = $report['meta_value'];

    // We have no tags.
    $tags = array();


}


// Let's extract the "title" meta, so we can use it 
// later and so we can use any report's meta_values to
// filter the alerts.
foreach($report_meta as $k => $val) {
    if($val['name'] == 'title') {
        $the_title = $val['value'];
        unset($report_meta[$k]);
    }
}
?>

<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1><?php echo $the_title;?></h1>
        </div>
        <div> 

            <?php
            // We'll count the active filters.
            $filters_count = 0;
            if(!empty($report_meta)){
                foreach($report_meta as $meta){
                    
                    // We count tags individually.
                    if(!empty($meta['type']) && $meta['type'] == 'find_in_set'){
                        $filters_count = count($meta['value'])+$filters_count;
                    
                    // We also don't count the archived filter, since that's set by default.
                    }elseif($meta['name'] == 'archived'){

                    // We'll count the remaining filters.
                    }else{
                        $filters_count++;
                    }

                }
            }
            if($filters_count > 0)
                echo '<span class="badge text-bg-secondary">'.$filters_count.' Active Filters</span>';
            ?>
                       
            <a href="index.php?view=report_settings&report=<?php echo $report['meta_name']; ?>" class="btn btn-primary">
                Filters & Settings
            </a>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Source</th>
                <th scope="col">URL</th>
                <th scope="col">Type</th>
                <th scope="col">Message</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        // We need to setup the different filters from the
        // all report meta.
        $filters = $report_meta;
        $alerts = DataAccess::get_db_rows( 'alerts',
            $filters, get_current_page_number(), NULL,
            NULL, $tags
        );
        if( count($alerts['content']) > 0 ): 
            foreach($alerts['content'] as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords(str_replace('_', ' ', $alert->source));?></td>
            <td><?php echo $alert->url;?></td>
            <td><?php echo ucwords($alert->type);?></td>
            <td><?php echo covert_code_shortcode($alert->message);?></td>
            <td style="min-width: 200px;">

                <?php
                // Active alerts can be ignored.
                if( $alert->status == 'active' && $alert->archived != 1 ){
                ?>

                <a href="actions/ignore_alert.php?id=<?php echo $alert->id;?>&preset=<?php if(isset($_GET['preset'])) echo $_GET['preset'];?>" class="btn btn-outline-secondary btn-sm">
                    Ignore Alert
                </a>

                <?php
                // Ignored alerts can be activated.
                }elseif( $alert->status == 'ignored' ){
                ?>

                <a href="actions/activate_alert.php?id=<?php echo $alert->id;?>&preset=<?php if(isset($_GET['preset'])) echo $_GET['preset'];?>" class="btn btn-outline-success btn-sm">
                    Activate Alert
                </a>

                <?php
                }
                ?>
            </td>
        </tr>

        <?php 
        // Fallback.
        endforeach; else:
        ?>

        <tr>
            <td colspan="6">
                <p class="text-center my-2 lead">
                    No alerts found.<br>
                </p>
                <p class="text-center my-2">
                    <img src="plumeria.png" alt="Three frangiapani flowers. The flowers five pedals. Color eminates fron the center of the flower before becoming colorless at the tip of each petal."  ><br>
                    <strong>Get out and smell the frangiapani!</strong>
                </p>
            </td>
        </tr>

        <?php 
        // End Alerts
        endif;
        ?>

    </table>

    <?php
    // The pagination
    the_pagination($alerts['total_pages']);
    ?>

</section>