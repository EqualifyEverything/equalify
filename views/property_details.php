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
        the_property_badge($db, $property);
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
                <th scope="col">Scanned</th>

            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo $property->url;?></td>
                <td><?php echo $property->scanned;?></td>
            </tr>

<?php
$children = get_property_children($db, $property->url);
$child_count = count($children);
if($child_count > 0 ):
    foreach($children as $child):    
?>
            <tr>
                <td><?php echo $child->url; ?></td>
                <td><?php echo $child->scanned; ?></td>
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
            <td colspan="2">No alerts yet...</td>
        </tr>

        <?php 
        endif;
        ?>

    </table>
</section>
<section id="property_options" class="mb-3 pb-4">
    <h2 class="pb-2">Options</h2>

    <?php
    // Set button to status conditions.
    if($property->status == 'archived'){
        $button_text = 'Activate';
        $button_class = 'btn-outline-success';
    }else{
        $button_text = 'Archive';
        $button_class = 'btn-outline-dark';
    }
    ?>

    <a href="actions/toggle_property_status.php?id=<?php echo $property->id;?>&old_status=<?php echo $property->status;?>" class="btn <?php echo $button_class;?>">
        <?php echo $button_text;?>
    </a>

    <?php
    // Optional Add Property
    if($property->type == 'static')
        echo '<a href="?view=property_adder&type=static&parent='.$property->url.'" class="btn btn-outline-dark">Add Child Property</a>';
    ?>

</section>