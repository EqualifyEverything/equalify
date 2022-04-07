<section>
    <h1 class="mb-3 pb-4 border-bottom">
    All Alerts</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Source</th>
                <th scope="col">Details</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        // Begin Alerts
        $alerts = get_alerts($db);
        if(count($alerts) > 0 ): foreach($alerts as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords($alert->source);?></td>
            <td><?php echo $alert->details;?></td>
            <td style="min-width: 200px;">
                <?php 
                // Integration alerts link to the integration.
                if( $alert->source == 'property' ){
                    echo '<a class="btn btn-primary btn-sm"  href="'.get_property_view_uri($db, $alert->property_id).'">View Property</a>';
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