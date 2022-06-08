// Instead of using cron, we asycronistically run the scan
async function runScan() {
    const response = await fetch('actions/run_scan.php', {
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
    if (document.getElementById('alert_count'))
        document.getElementById('alert_count').innerHTML = data;
}

async function refreshScans(data) {

    document.getElementById('the_scans_rows').innerHTML = data;

}

const handleCuedScans = () => {
            
    runScan();


    timer = setInterval(function() {
        getScans()
        .then(refreshScans)
        .then(getAlerts)
        .then(refreshAlerts);
    }, 3000);

}

window.onload = function(){
    handleCuedScans();
};

