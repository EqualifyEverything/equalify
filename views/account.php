<h1>Account</h1>

<?php
// Success Message
if(strpos($_SERVER['REQUEST_URI'], 'success'))
    echo '<div class="alert alert-success" role="alert">Your account was updated.</div>'
?>

<form action="actions/update_account.php" method="get">
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
    <button class="btn btn-primary" type="submit">Update Account</button>
</form>
<hr>
<p><?php echo get_account_credits($db, USER_ID);?> Credits Remain - <a href="mailto:blake@edupackd.dev">Request More Credits</a>