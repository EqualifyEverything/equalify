<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Scans</h1>
        </div>
        <div>
            <button id="add_scan" class="btn btn-primary">+ New Scan</button>
            <script>

                // Ansycronistically scan.
                async function cueScan() {
                    const response = await fetch('actions/cue_scan.php', {
                        method: 'GET', 
                        cache: 'no-cache',
                        headers: {
                            'Content-Type': 'text/html'
                        }
                    });
                }

                async function getScans() {
                    const response = await fetch('actions/get_scans.php', {
                        method: 'GET', 
                        cache: 'no-cache',
                        headers: {
                            'Content-Type': 'text/html'
                        }
                    });
                    return response.text();
                }

                async function getAlerts() {
                    const response = await fetch('actions/get_alerts.php', {
                        method: 'GET', 
                        cache: 'no-cache',
                        headers: {
                            'Content-Type': 'text/html'
                        }
                    });
                    return response.text();
                }

                async function refreshAlerts(data) {
                    document.getElementById('alert_count').innerHTML = data;
                }

                async function refreshScans(data) {
                    document.getElementById('the_scans_rows').innerHTML = data;
                }

                const handleScan = () => {
                    cueScan()
                    .then(getScans)
                    .then(refreshScans)
                    .then(getAlerts)
                    .then(refreshAlerts);
                }
                
                // Event listener.
                document.getElementById('add_scan').addEventListener('click', handleScan);

            </script>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody id="the_scans_rows">

            <?php

            // Show scans
            $scans = DataAccess::get_scans();
            the_scan_rows($scans);

            ?>

        </tbody>
    </table>
</section>