<h2>All Alerts</h2>
<table class="table">
    <thead>
        <tr>
            <th scope="col">Time</th>
            <th scope="col">Site</th>
            <th scope="col">Policy</th>
        </tr>
    </thead>

    <?php
    // Begin Alerts
    $alerts = get_all_alerts($db);
    if(count($alerts) > 0 ): foreach($alerts as $alert):    
    ?>

    <tr>
        <td><?php echo $alert->time;?></td>
        <td>
        <a href="?view=site_details&id=<?php echo $alert->site_id;?>">
            <?php echo get_site_url($db, $alert->site_id);?>
        </a>
        </td>
        <td>
            <?php echo $alert->alert;?>
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
