<section>
    <h1 class="mb-3 pb-4 border-bottom">
        All Alerts
    </h1>
    <div class="d-flex">
        <div class="me-auto" aria-label="Filters">
            <div class="btn-group">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    Source
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                </ul>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    Type
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-outline-secondary">Meta</button>
        </div>
        <div class="" aria-label="Search">
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Keyword.." aria-label="Search Term" aria-describedby="basic-addon1">
                <button class="btn btn-outline-secondary" type="button">Search</button>
            </div>
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
        $alerts = DataAccess::get_alerts([], get_current_page_number());
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