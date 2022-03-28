<div class="mb-3 pb-4 border-bottom">
    <h1>Account Settings</h1>
</div>

<?php
// Set Variables
$account_info = get_account($db, USER_ID);
$credits = $account_info->credits;
$property_unreachable_alert = $account_info->property_unreachable_alert;
$scan_frequency = $account_info->scan_frequency;
?>

<form action="actions/update_account.php" method="post">
    <h2 class="py-3">Email Settings</h2>
    <div class="form-check form-switch mb-3">
        <input type="hidden" name="property_unreachable_alert" id="property_unreachable_alert" value="<?php echo $property_unreachable_alert;?>">
        <input class="form-check-input" type="checkbox" role="switch" id="property_unreachable_alert_switch" <?php if($property_unreachable_alert == 1) echo 'checked';?>>
        <label class="form-check-label" for="property_unreachable_alert_switch">Email me all alerts.</label>
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
    
    <h2 class="py-3">Scan Settings</h2>
    <div class="mb-3" style="max-width:500px;">
        <label class="form-label" for="scan_frequency">Scan Frequency <span class="text-secondary">- 1 credit per property scanned.</span></label>
        <select id="scan_frequency" class="form-select" name="scan_frequency" onchange="showDiv('hidden_div', this)">
            <option value="manually" <?php if($scan_frequency == 'manually') echo 'selected';?>>Manually</option>
            <option value="daily" <?php if($scan_frequency == 'daily') echo 'selected';?>>Daily</option>
            <option value="weekly" <?php if($scan_frequency == 'weekly') echo 'selected';?>>Weekly</option>
            <option value="monthly" <?php if($scan_frequency == 'monthly') echo 'selected';?>>Monthly</option>
            <option value="annually" <?php if($scan_frequency == 'annually') echo 'selected';?>>Annually</option>
        </select>
    </div>
    <button class="btn btn-primary my-3" type="submit">Save Settings</button>
</form>
<hr>
<p><?php echo $credits;?> Credits Remain - <a href="mailto:blake@edupackd.dev">Request More Credits</a>