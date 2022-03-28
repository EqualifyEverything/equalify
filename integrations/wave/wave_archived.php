<div class="form-check form-switch mb-3">
    <input type="hidden" name="wave_wcag_2_1_page_error_alert" id="wave_wcag_2_1_page_error_alert" value="<?php echo $account_info->wave_wcag_2_1_page_error_alert;?>">
    <input class="form-check-input" type="checkbox" role="switch" id="wave_wcag_2_1_page_error_alert_switch" <?php if($account_info->wave_wcag_2_1_page_error_alert == true) echo 'checked';?> >
    <label class="form-check-label" for="wave_wcag_2_1_page_error_alert_switch">Alert WCAG 2.1 page errors via <a target="_blank" href="https://wave.webaim.org/">WAVE's scan</a>.</label>
    <script>
    document.getElementById('wave_wcag_2_1_page_error_alert_switch').addEventListener('change', function () {
        if ( this.checked ) {
            document.getElementById('wave_wcag_2_1_page_error_alert').value = 1;
        } else {
            document.getElementById('wave_wcag_2_1_page_error_alert').value = 0;
        }
    });
    </script>
</div>

<div id="wave_key" class="mb-3">
    <label class="form-label" for="wave_key">WAVE Key</label>
    <input class="form-control" type="text" name="wave_key" value="<?php echo $account_info->wave_key; ?>" rquired>
</div>



