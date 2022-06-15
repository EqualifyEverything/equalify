async function getScans() {
    console.log('3. getScans');
    
    const response = await fetch('actions/get_scans.php', {
        method: 'GET', 
        cache: 'no-cache',
        headers: {
            'Content-Type': 'text/html'
        }
    });

    return response.text();
}

async function handleScans(data){
    console.log('4. handleScans');

    let theScanRows = document.getElementById('the_scans_rows');
    
    if (theScanRows) {
        theScanRows.innerHTML = data;
    }

    const isQueued = data.includes('Queued');
    const isRunning = data.includes('Running');

    // don't start a new scan if one is already running
    if (isQueued && !isRunning) {
        console.log('5a. is not running, is queued');
        console.log('5b. runScan');

        // don't care about the response; just trigger the scan
        fetch('actions/run_scan.php', {
            method: 'GET', 
            cache: 'no-cache',
            headers: {
                'Content-Type': 'text/html'
            }
        });
    }
}

async function getAlerts() {
    console.log('6. getAlerts');

    const response = await fetch('actions/get_alerts.php', {
        method: 'GET', 
        cache: 'no-cache',
        headers: {
            'Content-Type': 'text/html'
        }
    });
    return response.text();
}

async function handleAlerts(data) {
    console.log('7. handleAlerts');

    alertCount = document.getElementById('alert_count');

    if (alertCount) {
        alertCount.innerHTML = data;
    }
}

const handlePromises = () => {
    console.log(''); // spacing for clarity
    console.log('2. handlePromises');

    getScans()
    .then(handleScans)
    .then(getAlerts)
    .then(handleAlerts)
    .then(() => setTimeout(handlePromises, 5000));

}

console.log('1. window');
window.addEventListener('load', handlePromises);