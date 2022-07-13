<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the alerts view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Sometimes this view is filtered to a label's data.
if(!empty($_GET['label'])){

    // Load the data of a selected label.
    $filtered_to_label = array(
        array(
            'name' => 'meta_name',
            'value' => $_GET['label']
        )
    );
    $label = DataAccess::get_db_entries(
        'meta', $filtered_to_label
    )['content'][0];

    // We need to unserialize the meta from the label.
    $label_meta = unserialize($label->meta_value);

// We also have special presets
}elseif(!empty($_GET['preset'])){

    // The active preset contains all alerts.
    if($_GET['preset'] == 'all'){
        $label = array(
            'meta_name' => '',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'All Alerts'
                )
            )
        );
        $label_meta = $label['meta_value'];    

    // The ignored preset contains all 'ignored' alerts.
    }elseif($_GET['preset'] == 'ignored'){
        $label = array(
            'meta_name' => '',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'Ignored Alerts'
                ),
                array(
                    'name' => 'status',
                    'value' => 'ignored'
                )
            )
        );
        $label_meta = $label['meta_value'];

    // The equalified preset contains all 'equalified' 
    // alerts.
    }elseif($_GET['preset'] == 'equalified'){
        $label = array(
            'meta_name' => '',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'Equalified Alerts'
                ),
                array(
                    'name' => 'status',
                    'value' => 'equalified'
                )
            )
        );
        $label_meta = $label['meta_value'];
        
    }

}else{

    // When there's no label data, we get active alerts.
    $label = array(
        'meta_name' => '',
        'meta_value' => array(
            array(
                'name' => 'title',
                'value' => 'Active Alerts'
            ),
            array(
                'name' => 'status',
                'value' => 'active'
            )
        )
    );
    $label_meta = $label['meta_value'];

}

// Let's extract the "title" meta, so we can use it 
// later and so we can use any label's meta_values to
// fitler the alerts.
foreach($label_meta as $k => $val) {
    if($val['name'] == 'title') {
        $the_title = $val['value'];
        unset($label_meta[$k]);
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
            // If we're not on a label page, we can't edit
            // the page.
            if(!empty($_GET['label'])):
            ?>

            <a href="index.php?view=label_customizer&name=<?php echo $label->meta_name;?>" class="btn btn-primary">
                Edit Label
            </a>

            <?php
            endif;
            ?>

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
        // all label meta.
        $filters = $label_meta;
        $alerts = DataAccess::get_db_entries( 'alerts',
            $filters, get_current_page_number()
        );
        if( count($alerts['content']) > 0 ): 
            foreach($alerts['content'] as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords($alert->source);?></td>
            <td><?php echo $alert->url;?></td>
            <td><?php echo ucwords($alert->type);?></td>
            <td><?php echo covert_code_shortcode($alert->message);?></td>
            <td style="min-width: 200px;">

                <?php
                // Active alerts can be ignored.
                if( $alert->status == 'active' ){
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