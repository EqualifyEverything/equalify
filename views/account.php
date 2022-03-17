<h1 class="mb-3 pb-4 border-bottom">Account Settings</h1>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="alert alert-success" role="alert">Your account was updated.</div>';

// Set Variables
$account_info = get_account_info($db, USER_ID);
$credits = $account_info->credits;
$site_unreachable_alert = $account_info->site_unreachable_alert;
$wcag_2_1_page_error_alert = $account_info->wcag_2_1_page_error_alert;
$testing_frequency = $account_info->testing_frequency;
$accessibility_testing_service = $account_info->accessibility_testing_service;
$wave_key = $account_info->wave_key;
?>

<form action="actions/update_account.php" method="post">
    <h2 class="py-3">WebOps Alerts</h2>
    <div class="form-check form-switch mb-3">
        <input type="hidden" name="site_unreachable_alert" id="site_unreachable_alert" value="<?php echo $site_unreachable_alert;?>">
        <input class="form-check-input" type="checkbox" role="switch" id="site_unreachable_alert_switch" <?php if($site_unreachable_alert == 1) echo 'checked';?>>
        <label class="form-check-label" for="site_unreachable_alert_switch">Site Unreachable</label>
        <script>
        document.getElementById('site_unreachable_alert_switch').addEventListener('change', function () {
            if ( this.checked ) {
                document.getElementById('site_unreachable_alert').value = 1;
            } else {
                document.getElementById('site_unreachable_alert').value = 0;
            }
        });
        </script>
    </div>
    <div class="form-check form-switch mb-3">
        <input type="hidden" name="wcag_2_1_page_error_alert" id="wcag_2_1_page_error_alert" value="<?php echo $wcag_2_1_page_error_alert;?>">
        <input class="form-check-input" type="checkbox" role="switch" id="wcag_2_1_page_error_alert_switch" <?php if($wcag_2_1_page_error_alert == true) echo 'checked';?> >
        <label class="form-check-label" for="wcag_2_1_page_error_alert_switch">WCAG 2.1 Page Error</label>
        <script>
        document.getElementById('wcag_2_1_page_error_alert_switch').addEventListener('change', function () {
            if ( this.checked ) {
                document.getElementById('wcag_2_1_page_error_alert').value = 1;
            } else {
                document.getElementById('wcag_2_1_page_error_alert').value = 0;
            }
        });
        </script>
    </div>
    <h2 class="py-3">Testing Settings</h2>
    <div class="mb-3">
        <label class="form-label" for="testing_frequency">Testing Frequency</label>
        <select id="testing_frequency" class="form-select" name="testing_frequency" onchange="showDiv('hidden_div', this)">
            <option value="daily" <?php if($testing_frequency == 'daily') echo 'selected';?>>Daily</option>
            <option value="weekly" <?php if($testing_frequency == 'weekly') echo 'selected';?>>Weekly</option>
            <option value="monthly" <?php if($testing_frequency == 'monthly') echo 'selected';?>>Monthly</option>
            <option value="annually" <?php if($testing_frequency == 'annually') echo 'selected';?>>Annually</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label class="form-label" for="accessibility_testing_service">Accessibility Testing Service</label>
        <select id="accessibility_testing_service" class="form-select" name="accessibility_testing_service" onchange="showDiv('hidden_div', this)">
            <option value="Little Forrest" <?php if($accessibility_testing_service == 'Little Forrest') echo 'selected'; ?>>Little Forrest</option>
            <option value="WAVE" <?php if($accessibility_testing_service == 'WAVE') echo 'selected'; ?>>WAVE</option>
        </select>
        <script>
        document.getElementById('accessibility_testing_service').addEventListener('change', function () {
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
                $wave_key
            ) ||
            $accessibility_testing_service == 'Little Forrest'
        ) echo 'style="display: none;"';
        ?>
    >
    
        <label class="form-label" for="wave_key">WAVE Key</label>
        <input class="form-control" type="text" name="wave_key" value="<?php echo $wave_key; ?>" rquired>
    </div>
    <button class="btn btn-primary my-3" type="submit">Save Settings</button>
</form>
<hr>
<p><?php echo $credits;?> Credits Remain - <a href="mailto:blake@edupackd.dev">Request More Credits</a>