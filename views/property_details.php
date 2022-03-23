<?php
// Set Property ID with optional fallboack.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if($id == false)
    throw new Exception('Property ID format is invalid.');

// Check if Property exists.
$property = get_property($db, $id);

if(empty($property) == 1)
    throw new Exception('Property does not exist.');
?>

<div class="mb-3 pb-4 border-bottom">

    <h1>
    
        <?php 
        // Title
        echo $property->url;
        ?>

        <span class="float-end">
        
        <?php
        // Badge
        the_status_badge($property);
        ?>

        </span>
    </h1>

</div>
<section id="relatives" class="mb-3 pb-4">
    <h2>Properties</h2>

    <table class="table">
        <thead>
            <tr>
                <th scope="col">URL</th>
                <th scope="col">WCAG Errors</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo $property->url;?></td>
                <td>
                    <a href="<?php the_wcag_report_URL($db, $property->url);?>" target="_blank"><?php echo $property->wcag_errors; ?></a>
                </td>
            </tr>

<?php
$children = get_property_children($db, $property->url);
if(count($children) > 0 ):
    foreach($children as $child):    
?>
            <tr>
                <td><?php echo $child->url; ?></td>
                <td>
                    <a href="<?php the_wcag_report_URL($db, $child->url);?>" target="_blank"><?php echo $child->wcag_errors; ?></a>
                </td>
            </tr>

<?php 
    endforeach;
endif;
?>

        </tbody>
    </table>
</section>
<section id="alerts" class="mb-3 pb-4">
    <h2>Alerts</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Details</th>
            </tr>
        </thead>

        <?php
        $alerts = get_alerts_by_property($db, $id);
        if(count($alerts) > 0 ):
            foreach($alerts as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo $alert->details;?></td>
        </tr>

        <?php 
            endforeach;
        else:
        ?>

        <tr>
            <td colspan="2">No alerts found.</td>
        </tr>

        <?php 
        endif;
        ?>

    </table>
</section>
<section id="events" class="mb-3 pb-4">
    <h2>Events</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">time</th>
                <th scope="col">Type</th>
                <th scope="col">Status</th>
            </tr>
        </thead>

        <?php
        $events = get_events_by_property($db, $id);
        if(count($events) > 0 ):
            foreach($events as $event):    
        ?>

        <tr>
            <td><?php echo $event->time;?></td>
            <td><?php echo ucwords(str_replace('_', ' ', $event->type));?></td>
            <td><?php echo ucwords($event->status);?></td>
        </tr>

        <?php 
            endforeach;
        else:
        ?>

        <tr>
            <td colspan="3">No events found.</td>
        </tr>

        <?php 
        endif;
        ?>

    </table>
</section>
<section id="property_options" class="mb-3 pb-4">
    <h2 class="pb-2">Options</h2>
    <a href="actions/scan_property.php" class="btn btn-primary">Rescan Property</a>

    <?php
    // Conditional Status Change Button
    if($property->status == ' archived'){
        $button_href = 'actions/archive_property_and_children.php?id='.$property->id;
        $button_text = 'Archive Property & Children';
        $button_class = 'btn-outline-secondary';
    }else{
        $button_href = 'actions/archive_property_and_children.php?id='.$property->id;
        $button_text = 'Activate Property & Children';
        $button_class = 'btn-outline-success';
    }
    ?>

    <a href="<?php echo $button_href;?>" class="btn <?php echo $button_class;?>"><?php echo $button_text;?></a>

</section>