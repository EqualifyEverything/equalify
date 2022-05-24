<section>
    <h1 class="mb-3 pb-4 border-bottom">
        All Alerts
    </h1>
    <ul class="nav nav-tabs mb-3">

        <?php
        // Setup alert options
        $alert_options = DataAccess::get_meta_value('alert_options');
        $alert_options = array( // Sample output
            'current_view' => 'Sample View Two',
            'views'  => array(
                'Sample View One' => array(
                    'name' => 'Sample View One',
                    'filters' => array(
                        0 => array(
                            'name'  => 'integration_uri',
                            'value' => 'little_forrest'
                        ),
                        1 => array(
                            'name' => 'type',
                            'value' => 'error'
                        ),
                        2 => array(
                            'name' => 'source',
                            'value' => 'page'
                        )
                    )
                ), 
                'Sample View Two' => array(
                    'name' => 'Sample View Two',
                    'filters' => array(
                        0 => array(
                            'name'  => 'integration_uri',
                            'value' => 'little_forrest'
                        ),
                        1 => array(
                            'name' => 'type',
                            'value' => 'warning'
                        ),
                        2 => array(
                            'name' => 'source',
                            'value' => 'page'
                        )
                    )
                )
            )
        );
        if(empty($alert_options)){
            $views = '';
            $current_view = '';
            $current_view_data = '';
            $filters = [];
        }else{
            $views = $alert_options['views'];
            $current_view = $alert_options['current_view'];
            $current_view_data = $views[$current_view];
            $filters = $current_view_data['filters'];
        }

        // Start Views Loop
        if(!empty($views)): foreach ($views as $view):
        ?>

        <li class="nav-item">
            <a 
                class="nav-link <?php if($current_view == $view['name']) echo 'active';?>" 
                aria-current="page" 
                href="#<?php echo $view['name'];?>"
            >
            
                <?php echo $view['name']; ?>

                <span class="
                    ms-1
                    badge 
                    bg-<?php if($current_view == $view['name']){ echo 'primary'; }else{ echo 'secondary'; }?> 
                    rounded
                ">

                <?php
                // Counter Alerts
                echo DataAccess::count_alerts($view['filters']);
                ?>

                </span>
            </a>
        </li>

        <?php
        // End Views loop
        endforeach; endif;
        ?>

        <li class="nav-item <?php if(empty($views)){ echo 'mb-3';}else{ echo 'ms-2'; }?>">
            <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#alertOptions">+ Add View</a>
        </li>
    </ul>
    <div class="row row-cols-lg-auto g-3 align-items-center mb-3">
        <div class="col-12">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" placeholder="Keyword or Website.." aria-label="Search Term" aria-describedby="basic-addon1">
                <button class="btn btn-outline-secondary" type="button">Search</button>
            </div>
        </div>
        <div class="col-12">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#alertOptions">
                View Filters & Settings
            </button>

            <?php 
            // Alert Options Modal
            the_alert_options($current_view_data);
            ?>

        </div>
        <div class="col-12 fs-7 text-secondary">
            Showing xx of xxx results.
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Source</th>
                <th scope="col">Type</th>
                <th scope="col">Message</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        // Begin Alerts
        $alerts = DataAccess::get_alerts($filters, get_current_page_number());
        $alerts_content = $alerts['content'];
        if(count($alerts_content) > 0 ): foreach($alerts_content as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords($alert->source);?></td>
            <td><?php echo ucwords($alert->type);?></td>
            <td><?php echo covert_code_shortcode($alert->message);?></td>
            <td style="min-width: 200px;">
                <?php 
                // Integration alerts link to the integration.
                if( $alert->source == 'page' ){
                    echo '<a class="btn btn-primary btn-sm"  href="'.DataAccess::get_site_details_uri($alert->page_id).'">Site Details</a>';
                }
                ?>

                <a href="actions/delete_alert.php?id=<?php echo $alert->id;?>" class="btn btn-outline-secondary btn-sm">
                    Dismiss
                </a>
            </td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="4">No alerts found.</td>
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