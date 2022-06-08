async function getScans() {
    console.log('getScans');
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
    console.log('getAlerts');

    const response = await fetch('actions/get_alerts.php', {
        method: 'GET', 
        cache: 'no-cache',
        headers: {
            'Content-Type': 'text/html'
        }
    });
    return response.text();
}

async function runScan() {
    console.log('runScan');

    const response = await fetch('actions/run_scan.php', {
        method: 'GET', 
        cache: 'no-cache',
        headers: {
            'Content-Type': 'text/html'
        }
    });
}

async function handleScans(data){
    console.log('handleScans');

    theScanRows = document.getElementById('the_scans_rows');

    if(!data.includes('Running') && !data.includes('Cued') && theScanRows){
        console.log('not running and not cued and scan rows');
        
        theScanRows.innerHTML = data;
        
    }

    if(data.includes('Running') && theScanRows){
        console.log('running and scan rows');

        setTimeout(function(){ 
            handlePromises();
            theScanRows.innerHTML = data;
        }, 1000);
        
    }

    if(!data.includes('Running') && data.includes('Cued')){
        console.log('not running and cued');

        runScan().
        then(handlePromises);

    }

    if(!data.includes('Running') && data.includes('Cued') && theScanRows ){
        console.log('not running and cued and scan rows');

        setTimeout(function(){ 
            handlePromises();
            theScanRows.innerHTML = data;
        }, 1000);
        
    }

}

async function handleAlerts(data) {
    console.log('handleAlerts');

    alertCount = document.getElementById('alert_count');

    if (alertCount)
        alertCount.innerHTML = data;
}

const handlePromises = () => {
    console.log('handlePromises');

    getScans()
    .then(handleScans)
    .then(getAlerts)
    .then(handleAlerts)

}

window.onload = function(){
    console.log('window');

    handlePromises();

}