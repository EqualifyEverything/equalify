<?php
// Set Site ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Check for correct info
if($id == false || empty(get_site_url($db, $id)) == 1):
    throw new Exception('Site ID format is invalid or site does not exist.');

// Begin Content
else:
?>

<h1><?php echo get_site_url($db, $id);?></h1>

<h2>Pages</h2>
<table class="table">
    <thead>
        <tr>
            <th scope="col">URL</th>
            <th scope="col">WCAG Errors</th>
        </tr>
    </thead>
    <tbody>

    <?php
    $records = get_all_pages($db, $id);
    if(count($records) > 0 ):
        foreach($records as $record):    
    ?>
        <tr>
            <td><?php echo $record->url; ?></td>
            <td>
                
                <?php 
                // Link to page accessibility inspector
                $account_info = get_account_info($db, USER_ID);
                if($account_info->accessibility_testing_service == 'Little Forrest')
                    $wcag_inspector_url = 'https://inspector.littleforest.co.uk/InspectorWS/Inspector?url='.$record->url;
                    if($account_info->accessibility_testing_service == 'WAVE')
                    $wcag_inspector_url = 'https://wave.webaim.org/report#/'.$record->url
                ?>

                <a href="<?php echo $wcag_inspector_url;?>" target="_blank"><?php echo $record->wcag_errors; ?></a>
            </td>
        </tr>
    <?php 
        endforeach;
    else:
    ?>

        <tr>
            <td colspan="2">Site has no pages.</td>
        </tr>

    <?php 
    endif;
    ?>

    </tbody>
</table>

<?php
// Begin Alerts
if(!empty($id)):
?>
<hr id="alerts">
<h2>Related Alerts</h2>
<table class="table">
    <thead>
        <tr>
            <th scope="col">Time</th>
            <th scope="col">Alert</th>
        </tr>
    </thead>

    <?php
    $alerts = get_alerts_by_site($db, $id);
    if(count($alerts) > 0 ):
        foreach($alerts as $alert):    
    ?>

    <tr>
        <td><?php echo $alert->time;?></td>
        <td><?php echo $alert->alert;?></td>
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

<?php
// End Alerts
endif;
?>

<hr>
<h2 class="mb-3">Options</h2>
<a href="actions/delete_site_data.php?id=<?php echo $id;?>" class="btn btn-outline-danger">Delete Site + Related Pages and Alerts</a>
<div class="form-text">Deletion cannot be undone.</div>

<?php
// End Content
endif;
?>