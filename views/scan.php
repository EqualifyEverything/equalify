<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>Scan</h1>
        </div>
        <div class="btn-group">
            <button id="run_scan" type="button" class="btn btn-primary" onclick="runScan()">Run Scan</button>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#scanSchedule">
                        Schedule Scan
                    </button>
                </li>
            </ul>
        </div>
    </div>
    <div id="terminal" class="bg-dark text-white px-5">
        <pre id="scan_log" class="pt-3 pb-5"></pre>
    </div>
    <div class="modal fade" id="scanSchedule" tabindex="-1" aria-labelledby="scanScheduleLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" action="actions/save_scan_schedule.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="scanScheduleLabel">Scan Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="scanScheduleOptions" class="form-label">When should the automatically scan run?</label>
                    <select id="scanScheduleOptions" class="form-select form-select mb-3" aria-label="scheudle options" name="scan_schedule">

                        <?php
                        // Create options.
                        $options = array(
                            'manually', 'hourly', 'daily', 'weekly', 'monthly'
                        );
                        foreach($options as $option){

                            // Make saved option selected.
                            $selected = '';
                            $saved_option = DataAccess::get_meta_value(
                                'scan_schedule'
                            );
                            if($saved_option == $option)
                                $selected = 'selected';

                            // Return option.
                            echo '<option value="'.$option.'" '.$selected.'>'.ucfirst($option).'</option>';

                        }
                        ?>

                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</section>
<script>

    // Run the scan.
    function runScan(){

        // Trigger run_scan.php
        const xhttp = new XMLHttpRequest();
        xhttp.open('POST', 'actions/scan.php');
        xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhttp.send();

        // Let's clear the scan log element to show
        // we're doing something.
        scanLog = document.getElementById('scan_log');
        scanLog.innerHTML = "\nLoading...";

        // Let's get the scan log after a second to 
        // prepare.
        let timer = setTimeout(
            handleScanPromises, 500
        );


    }

    // Get the scan log.
    async function getScanLog(){
        const response = await fetch('actions/get_scan_log.php', {
            method: 'GET', 
            cache: 'no-cache',
            headers: {
                'Content-Type': 'text/html'
            }
        });
        return response.text();
    }

    // Update scan log.
    async function updateScanLog(data) {
        
        // We update #scan_log
        scanLog = document.getElementById('scan_log');

        // Sometimes there's no data.
        if(data == ''){

            // Add a fallback message.
            scanLog.innerHTML = "\nNo scan is running.";

        // With data, we setup html and repeat in 3 secs.
        }else{

            // Populate the scan log.
            scanLog.innerHTML = data;

        }

    }

    // Get the alert count.
    async function getAlertCount(){
        const response = await fetch('actions/get_alert_count.php', {
            method: 'GET', 
            cache: 'no-cache',
            headers: {
                'Content-Type': 'text/html'
            }
        });
        return response.text();
    }

    // Update alert count.
    async function updateAlertCount(data) {
        
        // We update #alert_count.
        alertCount = document.getElementById('alert_count');

        // Update alert count.
        alertCount.innerHTML = data;

    }

    // Get the scan status.
    async function getScanStatus(){
        const response = await fetch('actions/get_scan_status.php', {
            method: 'GET', 
            cache: 'no-cache',
            headers: {
                'Content-Type': 'text/html'
            }
        });
        return response.text();
    }

    // Update scan button.
    async function updateScanButton(data) {
        
        // We update #run_scan
        scanButton = document.getElementById('run_scan');

        // Sometimes there's no data.
        if(data == ''){

            // Make sure the scan button is enabled.
            scanButton.disabled = false

        // With data, we setup html and repeat in 3 secs.
        }else{

            // Disable the scan button.
            scanButton.disabled = true;

            // Hit promisses again after 2 seconds.
            let timer = setTimeout(handleScanPromises, 2000);

        }

    }

    // Scan log promises.
    const handleScanPromises = () => {
        getScanLog()
        .then(updateScanLog)
        .then(getAlertCount)
        .then(updateAlertCount)
        .then(getScanStatus)
        .then(updateScanButton)
    }

    // On load, trigger script.
    window.addEventListener('load', handleScanPromises);

</script>