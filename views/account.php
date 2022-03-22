<div class="mb-3 pb-4 border-bottom">
    <h1>Account Settings</h1>
</div>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="alert alert-success" role="alert">Your account settings are updated.</div>';

// Set Variables
$account_info = get_account($db, USER_ID);
$credits = $account_info->credits;
$property_unreachable_alert = $account_info->property_unreachable_alert;
$wcag_2_1_page_error_alert = $account_info->wcag_2_1_page_error_alert;
$email_site_owner = $account_info->email_site_owner;
$scan_frequency = $account_info->scan_frequency;
$accessibility_testing_service = $account_info->accessibility_testing_service;
$wave_key = $account_info->wave_key;
?>

<form action="actions/update_account.php" method="post">
    <h2 class="py-3">WebOps Alerts</h2>
    <div class="form-check form-switch mb-3">
        <input type="hidden" name="property_unreachable_alert" id="property_unreachable_alert" value="<?php echo $property_unreachable_alert;?>">
        <input class="form-check-input" type="checkbox" role="switch" id="property_unreachable_alert_switch" <?php if($property_unreachable_alert == 1) echo 'checked';?>>
        <label class="form-check-label" for="property_unreachable_alert_switch">Property Unreachable</label>
        <script>
        document.getElementById('property_unreachable_alert_switch').addEventListener('change', function () {
            if ( this.checked ) {
                document.getElementById('property_unreachable_alert').value = 1;
            } else {
                document.getElementById('property_unreachable_alert').value = 0;
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
    <h2 class="py-3">WebOps Enforcement</h2>
    <div class="form-check form-switch mb-3">
        <input type="hidden" name="email_site_owner" id="email_site_owner" value="<?php echo $email_site_owner;?>">
        <input class="form-check-input" type="checkbox" role="switch" id="email_site_owner_switch" <?php if($email_site_owner == 1) echo 'checked';?> disabled>
        <label class="form-check-label" for="email_site_owner_switch">Send alert email to the webproperty's owner <span class="text-secondary">- Coming soon!</span></label>
        <script>
        document.getElementById('email_site_owner_switch').addEventListener('change', function () {
            if ( this.checked ) {
                document.getElementById('email_site_owner').value = 1;
            } else {
                document.getElementById('email_site_owner').value = 0;
            }
        });
        </script>
    </div>
    <h2 class="py-3">Scan Settings</h2>
    <div class="mb-3">
        <label class="form-label" for="scan_frequency">Scan Frequency <span class="text-secondary">- 5 credits per property scanned.</span></label>
        <select id="scan_frequency" class="form-select" name="scan_frequency" onchange="showDiv('hidden_div', this)">
            <option value="manually" <?php if($scan_frequency == 'manually') echo 'selected';?>>Manually</option>
            <option value="daily" <?php if($scan_frequency == 'daily') echo 'selected';?>>Daily</option>
            <option value="weekly" <?php if($scan_frequency == 'weekly') echo 'selected';?>>Weekly</option>
            <option value="monthly" <?php if($scan_frequency == 'monthly') echo 'selected';?>>Monthly</option>
            <option value="annually" <?php if($scan_frequency == 'annually') echo 'selected';?>>Annually</option>
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