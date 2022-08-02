<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>Scan</h1>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="runScan()">Run Scan</button>
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
        <pre id="scan_log"></pre>
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
            handleScanLogPromises, 500
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

    // Populate the scan log
    async function populateScanLog(data) {
        
        // We populate #scan_log.
        scanLog = document.getElementById('scan_log');

        // Without data, we setup a fallback
        if(data == ''){
            scanLog.innerHTML = "\nNo scan is running.";

        // With data, we setup html and repeat in 3 secs.
        }else{
            scanLog.innerHTML = data;
            let timer = setTimeout(handleScanLogPromises, 2000);
        }

    }

    // Scan log promises.
    const handleScanLogPromises = () => {
        getScanLog()
        .then(populateScanLog)
    }

    // On load, trigger script.
    window.addEventListener('load', handleScanLogPromises);

</script>