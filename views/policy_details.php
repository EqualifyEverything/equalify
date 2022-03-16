<?php
// Get Option Policy Variables
if(!empty(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT))){
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $policy_details = get_policy_details($db, $id);
    $name = $policy_details->name;
    $action = $policy_details->action;
    $event = $policy_details->event;
    $tested = $policy_details->tested;
    $frequency = $policy_details->frequency;
}else{
    $id = '';
    $policy_details = '';
    $name = '';
    $action = '';
    $event = '';
    $tested = '';
    $frequency = '';
}
?>

<h1>Policy Details</h1>
<form action="actions/save_policy.php" method="get">
    <input type="hidden" name="id" value="<?php echo $id;?>">
    <input type="hidden" name="tested" value="<?php echo $tested;?>">
    <div class="mb-3">
        <label for="name" class="form-label">Policy Name</label>
        <input id="name" name="name" type="text" class="form-control" value="<?php echo $name;?>" required>
    </div>
    <div class="row mb-3">
        <div class="col">
            <label for="action" class="form-label">Policy Action</label>
            <select id="action" name="action" class="form-select" required>
                <option value="email_wp_site_admin" <?php if ($action == 'email_wp_site_admin') echo 'selected';?>>Delete</option>
                <option value="email_wp_site_admin" <?php if ($action == 'email_wp_site_admin') echo 'selected';?>>Email User Who Published Page</option>
                <option value="email_wp_site_admin" <?php if ($action == 'email_wp_site_admin') echo 'selected';?>>Email WordPress Site Admin</option>
                <option value="trigger_system_alert" <?php if ($action == 'trigger_system_alert') echo 'selected';?>>Trigger System Alert</option>
            </select>
        </div>
        <div class="col">
            <label for="event" class="form-label">WebOps Event</label>
            <select id="event" name="event" class="form-select" required>
                <option value="new_wcag_page_error" <?php if ($event == 'new_wcag_page_error') echo 'selected';?>>New WCAG Page Error</option>
                <option value="plugin_outdated" <?php if ($event == 'plugin_outdated') echo 'selected';?>>Plugin or Module is Outdated</option>
                <option value="site_outdated" <?php if ($event == 'site_outdated') echo 'selected';?>>Site is Outdated</option>       
            </select>
        </div>
        <div class="col">
            <label for="frequency" class="form-label">Enforce Policy</label>
            <select id="frequency" name="frequency" class="form-select" required>
                <option value="once" <?php if ($frequency == 'once') echo 'selected';?>>Once</option>
                <option value="daily" <?php if ($frequency == 'daily') echo 'selected';?>>Daily</option>
                <option value="weekly" <?php if ($frequency == 'weekly') echo 'selected';?>>Weekly</option>
                <option value="monthly" <?php if ($frequency == 'monthly') echo 'selected';?>>Monthly</option>
                <option value="annually" <?php if ($frequency == 'annually') echo 'selected';?>>Annually</option>       
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Save Policy</button>
</form>

<?php
// Begin Events
if(!empty($id)):
?>
<hr>
<h2>Related Events</h2>
<table class="table">
    <thead>
        <tr>
            <th scope="col">Time</th>
            <th scope="col">Type</th>
            <th scope="col">Site</th>
        </tr>
    </thead>

    <?php
    $records = get_events_by_policy($db, $id);
    if(count($records) > 0 ):
        foreach($records as $record):    
    ?>

    <tr>
        <td><?php echo $record->time;?></td>
        <td><?php echo ucfirst($record->type);?></td>
        <td>
          <a href="?view=site_details&id=<?php echo $record->site_id;?>">
            <?php echo get_site_url($db, $record->site_id);?>
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

<?php
// Begin Delete
if(!empty($id)):
?>
<hr>
<h2 class="mb-3">Other Resources</h2>
<a href="actions/delete_policy_data.php?id=<?php echo $id;?>" class="btn btn-outline-danger">Delete Policy and Related Events</a>
<div class="form-text">Deletion cannot be undone.</div>
<?php
// End Delete
endif;
?>