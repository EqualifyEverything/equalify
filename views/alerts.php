<section>
    <h1 class="mb-3 pb-4 border-bottom">
    All Alerts</h1>
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
        $alerts = DataAccess::get_alerts();
        if(count($alerts) > 0 ): foreach($alerts as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords($alert->source);?></td>
            <td><?php echo ucwords($alert->type);?></td>
            <td><?php echo $alert->message;?></td>
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
</section>