<?php

/**
 * Equalify is a platformed developed to integrate 
 * various services that manage websites.
 * 
 * Copyright (C) 2022 Blake Bertuccelli
 * 
 * This program is free software: you can 
 * redistribute it and/or modify it under the terms 
 * of the GNU Affero General Public License as 
 * published by the Free Software Foundation, either 
 * version 3 of the License, or (at your option) any 
 * later version.
 * 
 * This program is distributed in the hope that it 
 * will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU 
 * Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero 
 * General Public License along with this program.  
 * If not, see <https://www.gnu.org/licenses/>.
 */

// Add dependencies.
require_once 'models/hooks.php';
require_once 'config.php';
require_once 'models/db.php';
require_once 'models/view_components.php';
require_once 'models/integrations.php';

// We check to make sure all the DB tables are installed.
require_once 'actions/install.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta description="Equalify brings WebOps management into one dashboard with reporting and enforcement tools, integrated with your favorite services." />
    <title>Equalify | WebOps Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link href="theme.css" rel="stylesheet">
</head>
<body>

    <?php
    // Run "before_content" hook.
    $hook_system->run_hook('before_content');
    ?>

    <main>
        <div class="d-flex flex-column flex-shrink-0 p-3 bg-light sticky-top border-end" style="width: 230px;">
            <a href="index.php?view=alerts" class="d-flex text-success align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" fill="currentColor" class="bi me-2 bi-patch-check-fill" viewBox="0 0 16 16">
                    <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z"/>
                </svg>
                <span class="fs-2">Equalify</span>
            </a>
            <ul class="nav nav-pills flex-column mb-auto mt-5">
                <li class="nav-item">
                    <a href="index.php?view=alerts" class="nav-link <?php the_active_view('alerts');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi me-2 bi-exclamation-diamond" viewBox="0 0 16 16">
                            <path d="M6.95.435c.58-.58 1.52-.58 2.1 0l6.515 6.516c.58.58.58 1.519 0 2.098L9.05 15.565c-.58.58-1.519.58-2.098 0L.435 9.05a1.482 1.482 0 0 1 0-2.098L6.95.435zm1.4.7a.495.495 0 0 0-.7 0L1.134 7.65a.495.495 0 0 0 0 .7l6.516 6.516a.495.495 0 0 0 .7 0l6.516-6.516a.495.495 0 0 0 0-.7L8.35 1.134z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        Alerts 
                        <span class="badge bg-danger float-end">
                            <span id="alert_count">

                                <?php echo DataAccess::count_alerts();?>
                            
                            </span>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=sites" class="nav-link <?php the_active_view('sites');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi me-2 bi-diagram-3" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1zM0 11.5A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                        </svg>
                        Sites
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=scans" class="nav-link <?php the_active_view('scans');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi me-2 bi-arrow-repeat" viewBox="0 0 16 16">
                            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                        </svg>
                        Scans
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=integrations" class="nav-link <?php the_active_view('integrations');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi me-2 bi-plugin" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a7 7 0 1 1 2.898 5.673c-.167-.121-.216-.406-.002-.62l1.8-1.8a3.5 3.5 0 0 0 4.572-.328l1.414-1.415a.5.5 0 0 0 0-.707l-.707-.707 1.559-1.563a.5.5 0 1 0-.708-.706l-1.559 1.562-1.414-1.414 1.56-1.562a.5.5 0 1 0-.707-.706l-1.56 1.56-.707-.706a.5.5 0 0 0-.707 0L5.318 5.975a3.5 3.5 0 0 0-.328 4.571l-1.8 1.8c-.58.58-.62 1.6.121 2.137A8 8 0 1 0 0 8a.5.5 0 0 0 1 0Z"/>
                        </svg>
                        Integrations
                    </a>
                </li>
            </ul>

            <?php
            // As soon as you use Equalify, you know the toll
            // you will be asked to support the service.
            $usage_meta = DataAccess::get_meta_value('usage');
            if(!empty($usage_meta)):
            ?>

            <div class="border-top mt-3 pt-3">
                <p>
                    🎉 <strong><?php echo $usage_meta;?> pages scanned.</strong>
                </p>
                <p>
                <script type="text/javascript" defer src="https://donorbox.org/install-popup-button.js"></script>
                <script>window.DonorBox = { widgetLinkClassName: 'custom-dbox-popup' }</script>
                    If the scans are useful to you, <a class="custom-dbox-popup" href="https://donorbox.org/keep-scans-free">donate to the project</a> to keep the service free and maintained.
                </p>
            </div>
            
            <?php
            endif;
            ?>

        </div>
        <div class="container py-3">

            <?php
        
            // Success Message
            the_success_message();

            // Show View
            if(!empty($_GET['view'])){
                require_once 'views/'.$_GET['view'].'.php';
            }else{
                require_once get_default_view();
            }

            ?>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.5/dist/umd/popper.min.js" integrity="sha384-Xe+8cL9oJa6tN/veChSP7q+mnSPaj5Bcu9mPX5F5xIGE0DVittaqT5lorf0EI7Vk" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.min.js" integrity="sha384-kjU+l4N0Yf4ZOJErLsIcvOU2qSb74wXpOhqTvwVx3OElZRweTnQ6d31fXEoRD1Jy" crossorigin="anonymous"></script>
    <script src="script.js"></script>

    <script>

    // // Instead of using cron, we asycronistically run the scan
    // // every page load (I hope this changes soon..)
    // async function runScan() {
    //     const response = await fetch('actions/run_scan.php', {
    //         method: 'GET', 
    //         cache: 'no-cache',
    //         headers: {
    //             'Content-Type': 'text/html'
    //         }
    //     });
    // }

    // async function getScans() {
    //     const response = await fetch('actions/get_scans.php', {
    //         method: 'GET', 
    //         cache: 'no-cache',
    //         headers: {
    //             'Content-Type': 'text/html'
    //         }
    //     });
    //     return response.text();
    // }

    // async function getAlerts() {
    //     const response = await fetch('actions/get_alerts.php', {
    //         method: 'GET', 
    //         cache: 'no-cache',
    //         headers: {
    //             'Content-Type': 'text/html'
    //         }
    //     });
    //     return response.text();
    // }

    // async function refreshAlerts(data) {
    //     if (document.getElementById('alert_count'))
    //         document.getElementById('alert_count').innerHTML = data;
    // }

    // async function refreshScans(data) {

    //     document.getElementById('the_scans_rows').innerHTML = data;

    // }

    // const handleQueuedScans = () => {
                
    //     runScan();

    // <?php
    // // We only rant to refresh the page if a scan
    // // is running or queued and we're on the scanning
    // // view. This minimizes DB calls.
    // $filter_by_running_scans = array(
    //     array(
    //         'name' => 'status',
    //         'value' => 'running'
    //     ),
    // );
    // $running_scans = DataAccess::get_scans($filter_by_running_scans);
    // if($_GET['view'] == 'scans' && !empty($running_scans)):
    // ?>

    //     timer = setInterval(function() {
    //         getScans()
    //         .then(refreshScans)
    //         .then(getAlerts)
    //         .then(refreshAlerts);
    //     }, 3000);

    // <?php
    // endif;
    // ?>
    
    // }

    // window.onload = function(){
    //     handleQueuedScans();
    // };


    </script>

</body>
</html>