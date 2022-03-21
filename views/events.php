<div class="mb-3 pb-4 border-bottom">
    <h1>All Events</h1>
</div>
<table class="table">
    <thead>
        <tr>
            <th scope="col">Type</th>
            <th scope="col">Time</th>
            <th scope="col">Site</th>
            <th scope="col">Status</th>
        </tr>
    </thead>

    <?php
    // Begin Events
    $events = get_events($db);
    if(count($events) > 0 ): foreach($events as $event):    
    ?>

    <tr>
        <td><?php echo ucwords(str_replace('_', ' ', $event->type));?></td>
        <td><?php echo $event->time;?></td>
        <td>
            <a href="?view=site_details&id=<?php echo $event->site_id;?>">
                <?php echo get_site_title($db, $event->site_id);?>
            </a>
        </td>
        <td><?php echo ucwords($event->status);?></td>
    </tr>

    <?php 
    // Fallback
    endforeach; else:
    ?>

    <tr>
        <td colspan="4">No events found.</td>
    </tr>

    <?php 
    // End Events
    endif;
    ?>

</table>