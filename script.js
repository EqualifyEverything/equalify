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

async function runScan() {
    console.log('5b. runScan');

    const response = await fetch('actions/run_scan.php', {
        method: 'GET', 
        cache: 'no-cache',
        headers: {
            'Content-Type': 'text/html'
        }
    });
}

async function handleScans(data){
    console.log('4. handleScans');

    theScanRows = document.getElementById('the_scans_rows');

    if(!data.includes('Running') && !data.includes('Cued') && theScanRows){
        console.log('5. not running and not cued and scan rows');
        
        theScanRows.innerHTML = data;
        
    }

    if(data.includes('Running') && theScanRows){
        setTimeout(function(){ 
            console.log('5. running and scan rows');

            handlePromises();
            theScanRows.innerHTML = data;
        }, 5000);
        
    }

    if(!data.includes('Running') && data.includes('Cued')){
        console.log('5. not running and cued');

        runScan().
        then(handlePromises);

    }

    if(!data.includes('Running') && data.includes('Cued') && theScanRows ){

        setTimeout(function(){ 
            console.log('5. not running and cued and scan rows');

            handlePromises();
            theScanRows.innerHTML = data;
        }, 5000);
        
    }

}

async function handleAlerts(data) {
    console.log('7. handleAlerts');

    alertCount = document.getElementById('alert_count');

    if (alertCount)
        alertCount.innerHTML = data;
}

const handlePromises = () => {
    console.log('2. handlePromises');

    getScans()
    .then(handleScans)
    .then(getAlerts)
    .then(handleAlerts)

}

window.onload = function(){
    console.log('1. window');

    handlePromises();

}