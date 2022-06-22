
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This script triggers the scan, asyncronistically. We're
 * doing it this way so that we don't have to depend on 
 * CRON, thus simplifying the app's installation.
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

async function getScans() {
    console.log('3. getScans');
    
    const response = await fetch('actions/get_scans.php', 
    {
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

    let theScanRows = 
        document.getElementById('the_scans_rows');
    
    if (theScanRows) {
        theScanRows.innerHTML = data;
    }

    const isQueued = data.includes('Queued');
    const isRunning = data.includes('Running');

    // don't start a new scan if one is already running.
    if (isQueued && !isRunning) {
        console.log('5a. is not running, is queued');
        console.log('5b. runScan');

        // Don't care about the response; just trigger
        // the scan.
        fetch('actions/process_scan.php', {
            method: 'GET', 
            cache: 'no-cache',
            headers: {
                'Content-Type': 'text/html'
            }
        });
    }

    // Rejecting here breaks the promise loop and stops 
    // pinging the server.
    if (!isQueued && !isRunning) {
        return Promise.reject(
            'No scans queued or running'
        );
    }
}

async function getAlerts() {
    console.log('6. getAlerts');

    const response = await fetch('actions/get_alerts.php',
    {
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
    .then(
        () => setTimeout(handlePromises, 5000),
        (rejectionReason) => console.log(
            'Loop broken: ' + rejectionReason)
    );

}

console.log('1. window');
window.addEventListener('load', handlePromises);