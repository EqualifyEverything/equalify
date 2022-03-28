<?php
// Set Property ID with optional fallboack.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if($id == false)
    throw new Exception('Format of property ID "'.$id.'" is invalid');

// Check if Property exists.
$property = get_property($db, $id);

if(empty($property) == 1)
    throw new Exception('There is no record of property "'.$id.'"');
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
        the_property_status($db, $property);
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
$child_count = count($children);
if($child_count > 0 ):
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

    <?php
    // Scan Button
    if($property->status == 'active' || $property->status == 'unscanned'){
        echo '<a href="actions/scan.php?id=" class="btn btn-primary">';
        if($property->status == 'active'){
            echo 'Re-scan ';
        }else{
            echo 'Scan ';
        }
        $property_count = $child_count+1;
        if($property_count > 1){
            echo 'Properties';
        }else{
            echo 'Property';
        }
        echo '</a>';

    }
    ?>

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
            <td colspan="2">No alerts yet...</td>
        </tr>

        <?php 
        endif;
        ?>

    </table>
</section>
<section id="scans" class="mb-3 pb-4">
    <h2>Scans</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Status</th>
            </tr>
        </thead>

        <?php
        $scans = get_scans_by_property($db, $id);
        if(count($scans) > 0 ):
            foreach($scans as $scan):    
        ?>

        <tr>
            <td><?php echo $scan->time;?></td>
            <td><?php echo ucwords($scan->status);?></td>
        </tr>

        <?php 
            endforeach;
        else:
        ?>

        <tr>
            <td colspan="2">No scans yet!</td>
        </tr>

        <?php 
        endif;
        ?>

    </table>
</section>
<section id="property_options" class="mb-3 pb-4">
    <h2 class="pb-2">Options</h2>
    <?php
    // Conditional Status Change Button
    if($property->status == 'archived'){
        $button_text = 'Activate';
        $button_class = 'btn-outline-success';
    }else{
        $button_text = 'Archive All Properties';
        $button_class = 'btn-outline-dark';
    }
    ?>

    <a href="actions/toggle_property_status.php?id=<?php echo $property->id;?>&current_status=<?php echo $property->status;?>" class="btn <?php echo $button_class;?>"><?php echo $button_text;?></a>

    <?php
    // Optional Add Property
    if($property->type == 'static')
        echo '<a href="?view=property_adder&type=static&parent='.$property->url.'" class="btn btn-outline-dark">Add Child Property</a>';
    ?>

</section>