<section>
    <h1 class="mb-3 pb-4 border-bottom">
    All Alerts</h1>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Details</th>
                <th scope="col">Site</th>
            </tr>
        </thead>

        <?php
        // Begin Alerts
        $alerts = get_alerts($db);
        if(count($alerts) > 0 ): foreach($alerts as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo $alert->details;?></td>
            <td>
                <a href="?view=site_details&id=<?php echo $alert->site_id;?>">
                    <?php echo get_site_title($db, $alert->site_id);?>
                </a>
            </td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="3">No alerts found.</td>
        </tr>

        <?php 
        // End Alerts
        endif;
        ?>

    </table>
</section>