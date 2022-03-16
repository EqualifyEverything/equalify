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
                if(get_accessibility_testing_service($db, USER_ID) == 'Little Forrest')
                    $wcag_inspector_url = 'https://inspector.littleforest.co.uk/InspectorWS/Inspector?url='.$record->url;
                    if(get_accessibility_testing_service($db, USER_ID) == 'WAVE')
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
// Begin Events
if(!empty($id)):
?>
<hr id="events">
<h2>Related Events</h2>
<table class="table">
    <thead>
        <tr>
            <th scope="col">Time</th>
            <th scope="col">Type</th>
            <th scope="col">Policy</th>
        </tr>
    </thead>

    <?php
    $records = get_events_by_site($db, $id);
    if(count($records) > 0 ):
        foreach($records as $record):    
    ?>

    <tr>
        <td><?php echo $record->time;?></td>
        <td><?php echo ucfirst($record->type);?></td>
        <td>
        <a href="?view=policy_details&id=<?php echo $record->policy_id;?>">
          <?php echo get_policy_name($db, $record->policy_id);?>
        </a>
      </td>
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
<?php
// End Events
endif;
?>

<hr>
<h2 class="mb-3">Other Resources</h2>
<a href="actions/delete_site_data.php?id=<?php echo $id;?>" class="btn btn-outline-danger">Delete Site + Related Pages and Events</a>
<div class="form-text">Deletion cannot be undone.</div>

<?php
// End Content
endif;
?>