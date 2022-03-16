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

<h1 class="mb-3 pb-4 border-bottom">Settings</h1>

<form action="update_settings.php">
    <h2 class="py-3">WebOps Alerts</h2>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="wcag_2_1_page_error">
        <label class="form-check-label" for="wcag_2_1_page_error">WCAG 2.1 Page Error</label>
    </div>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="wcag_2_1_page_alert_or_warning">
        <label class="form-check-label" for="wcag_2_1_page_alert_or_warning">WCAG 2.1 Page Alert or Warning</label>
    </div>

    <h2 class="py-3">Testing Settings</h2>
    <div class="mb-3">
        <?php get_accessibility_testing_service($db, USER_ID);?>
        <label class="form-label" for="accessibility_testing_service">Accessibility Testing Service</label>
        <select id="accessibilityTestingService" class="form-select" name="accessibility_testing_service" aria-label="Select Accessibility Service" onchange="showDiv('hidden_div', this)">
            <option value="Little Forrest" <?php if(get_accessibility_testing_service($db, USER_ID) == 'Little Forrest') echo 'selected'; ?>>Little Forrest</option>
            <option value="WAVE" <?php if(get_accessibility_testing_service($db, USER_ID) == 'WAVE') echo 'selected'; ?>>WAVE</option>
        </select>
        <script>
        document.getElementById('accessibilityTestingService').addEventListener('change', function () {
            var style = this.value == 'WAVE' ? 'block' : 'none';
            document.getElementById('wave_key').style.display = style;
        });
        </script>
    </div>
    <div 
        id="wave_key" 
        class="mb-3" 
        
        <?php 
        if(
            empty(
                get_account_wave_key($db, USER_ID)
            ) ||
            get_accessibility_testing_service($db, USER_ID) == 'Little Forrest'
        ) echo 'style="display: none;"';
        ?>
    >
    
        <label class="form-label" for="wave_key">WAVE Key</label>
        <input class="form-control" type="text" name="wave_key" value="<?php echo get_account_wave_key($db, USER_ID); ?>" rquired>
    </div>
    <div class="mb-3">
        <label for="frequency" class="form-label">Test Frequency</label>
        <select id="frequency" name="frequency" class="form-select" required>
            <option value="daily" <?php if ($frequency == 'daily') echo 'selected';?>>Daily</option>
            <option value="weekly" <?php if ($frequency == 'weekly') echo 'selected';?>>Weekly</option>
            <option value="monthly" <?php if ($frequency == 'monthly') echo 'selected';?>>Monthly</option>
            <option value="annually" <?php if ($frequency == 'annually') echo 'selected';?>>Annually</option>       
        </select>
    </div>

    <button class="my-3 btn btn-primary">Save Settings</button>
</form>



