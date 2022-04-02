<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>All Scans</h1>
        </div>
        <div>
            <button id="add_scan" class="btn btn-primary">Scan All Properties</button>
            <script>

                // Ansycronistically start scan.
                async function addScan() {
                    const response = await fetch('actions/scan_all_properties.php?action=add_scan', {
                        method: 'GET', 
                        cache: 'no-cache',
                        headers: {
                            'Content-Type': 'text/html'
                        }
                    });
                    console.log('hpp');
                    return response.text();
                }

                async function doScan() {
                    const response = await fetch('actions/scan_all_properties.php?action=do_scan', {
                        method: 'GET', 
                        cache: 'no-cache',
                        headers: {
                            'Content-Type': 'text/html'
                        }
                    });
                    return response.text();
                }

                async function getAlerts() {
                    const response = await fetch('actions/scan_all_properties.php?action=get_alerts', {
                        method: 'GET', 
                        cache: 'no-cache',
                        headers: {
                            'Content-Type': 'text/html'
                        }
                    });
                    return response.text();
                }

                async function refreshTable(data) {
                    document.getElementById('the_scans_rows').innerHTML = data;
                }

                async function refreshAlerts(data) {
                    document.getElementById('alert_count').innerHTML = data;
                }

                const handleScan = () => {
                    addScan()
                    .then(refreshTable)
                    .then(doScan)
                    .then(refreshTable)
                    .then(getAlerts)
                    .then(refreshAlerts);
                }
                
                // Event listener.
                document.getElementById('add_scan').addEventListener('click', handleScan)

            </script>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Status</th>
                <th scope="col">Properties Scanned</th>
            </tr>
        </thead>
        <tbody id="the_scans_rows">

            <?php

            // Show scans
            $scans = get_scans($db);
            the_scan_rows($scans);

            ?>

        </tbody>
    </table>
</section>