<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Equalify is a platform developed to integrate various
 * services that manage websites.
 * 
 * The heart of the app is in actions/process_site.php -
 * which also contains more info about our architecture.
 * 
 * You'll see comment sections like this at the top of
 * many files to remind us of basic operating principles
 * that drive the Equalify project forward.
 * 
 * While Blake Bertuccelli established Equalify's
 * copyright in 2022, this program is free software: you
 * can redistribute it and/or modify it under the terms of
 * the GNU Affero General Public License as published by
 * the Free Foundation, either version 3 of the License,
 * or (at your option) anylater version.
 * 
 * This program is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU Affero General Public
 * License for more details.
 * 
 * You should have received a copy of the GNU Affero
 * General Public License along with this program. If not, 
 * see <https://www.gnu.org/licenses/>.
**********************************************************/

// Add dependencies.
require_once 'models/hooks.php';
require_once 'config.php';
require_once 'models/db.php';
require_once 'models/view_components.php';
require_once 'models/integrations.php';

// We check to make sure all the DB tables are installed.
require_once 'install.php';

// We also check to see if we can run the scan on every
// page load.
require_once 'scan.php';

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
                <svg viewBox="1409.501 -1033.757 1999.787 2000.193"  width="40" height="40" class="me-2">
                    <ellipse style="fill-rule: evenodd; stroke: rgba(0, 0, 0, 0); stroke-opacity: 0; stroke-width: 0px; fill: rgb(25, 135, 84);" cx="2411.173" cy="-35.364" rx="995.702" ry="997.687"/>
                    <path style="stroke: none; fill: rgb(255, 255, 255);" d="M 2664.065 -79.361 C 2664.065 -208.49 2630.413 -337.602 2566.311 -449.903 C 2540.175 -495.705 2510.484 -540.664 2471.685 -576.808 C 2422.312 -622.803 2361.177 -656.547 2296.365 -675.175 C 2246.27 -689.574 2194.043 -698.194 2141.893 -698.194 C 2130.142 -698.194 2118.533 -698.133 2106.828 -697.331 C 2101.806 -696.987 2095.418 -698.221 2090.918 -695.362 C 2083.695 -690.772 2079.043 -676.282 2075.319 -668.816 C 2061.89 -641.882 2051.655 -613.357 2043.334 -584.473 C 2011.331 -473.415 2022.808 -356.859 2074.37 -253.733 C 2085.875 -230.732 2100.138 -209.219 2115.083 -188.342 C 2119.688 -181.909 2126.132 -169.133 2133.402 -165.665 C 2137.875 -163.523 2145.722 -166.547 2150.422 -167.238 C 2165.244 -169.417 2180.103 -171.133 2194.963 -172.905 C 2239.93 -178.289 2287.277 -179.207 2332.377 -174.914 C 2409.148 -167.607 2484.318 -153.24 2556.977 -126.697 C 2580.442 -118.12 2603.461 -108.444 2626.158 -98.02 C 2638.838 -92.201 2650.968 -84.127 2664.065 -79.361 M 2713.344 71.32 C 2732.971 66.782 2752.655 57.816 2771.152 49.922 C 2812.29 32.371 2852.132 11.769 2889.613 -12.701 C 2985.233 -75.114 3079.765 -162.604 3128.135 -267.947 C 3154.387 -325.121 3166.338 -387.243 3166.338 -449.903 C 3166.338 -505.466 3157.723 -559.498 3142.37 -612.904 C 3138.675 -625.753 3134.105 -638.296 3129.424 -650.811 C 3127.86 -654.996 3126.258 -662.241 3122.373 -664.871 C 3115.465 -669.54 3101.496 -669.596 3093.364 -670.712 C 3068.687 -674.096 3044.342 -675.451 3019.446 -675.451 C 2905.154 -675.451 2788.55 -635.015 2706.71 -553.2 C 2685.785 -532.281 2666.566 -510.716 2649.015 -486.863 C 2644.087 -480.171 2632.393 -468.753 2631.503 -460.328 C 2631 -455.523 2636.165 -449.326 2638.24 -445.165 C 2644.656 -432.353 2650.826 -419.417 2656.617 -406.311 C 2675.048 -364.592 2689.539 -321.512 2701.137 -277.426 C 2721.674 -199.317 2731.094 -114.433 2725.579 -33.872 C 2723.153 1.534 2717.817 36.124 2713.344 71.32 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, 247.189316, -1462.56958)"/>
                    <path style="stroke: none; fill: rgb(255, 255, 255);" d="M 2324.732 -412.072 C 2202.784 -451.742 2070.195 -458.404 1944.711 -433.3 C 1891.386 -422.629 1838.031 -407.438 1790.24 -380.931 C 1734.432 -349.97 1686.923 -305.553 1649.851 -253.81 C 1618.039 -209.411 1592.422 -159.724 1575.118 -107.867 C 1571.242 -96.259 1567.765 -84.573 1564.419 -72.804 C 1563.158 -68.349 1560.182 -61.298 1561.556 -56.693 C 1564.58 -46.553 1580.604 -34.471 1588.385 -27.712 C 1611.982 -7.214 1635.647 12.838 1662.304 29.365 C 1758.967 89.297 1869.353 113.217 1981.671 94.377 C 2005.42 90.397 2028.961 84.227 2051.8 76.617 C 2059.685 73.982 2071.36 72.287 2078.031 67.245 C 2081.254 64.809 2080.893 58.782 2081.434 55.134 C 2083.064 44.056 2085.091 32.949 2087.29 21.965 C 2095.422 -18.671 2106.756 -58.428 2120.688 -97.443 C 2148.34 -174.868 2188.93 -249.328 2239.109 -314.461 C 2255.655 -335.926 2272.638 -357.589 2291.573 -377.008 C 2301.333 -387.014 2318.827 -399.686 2324.732 -412.072 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, 218.134308, -1084.893555)"/>
                    <path style="stroke: none; fill: rgb(255, 255, 255);" d="M 1753.764 -88.679 L 1754.712 -87.732 L 1753.764 -88.679 M 2291.1 156.768 C 2300.017 172.424 2313.369 186.744 2325.092 200.362 C 2349.883 229.162 2376.296 255.952 2404.821 281.065 C 2503.72 368.129 2635.382 439.433 2768.731 448.568 C 2827.155 452.567 2887.276 441.355 2942.157 421.824 C 2989.483 404.984 3034.63 381.482 3075.779 352.672 C 3086.128 345.432 3096.363 338.002 3106.105 329.957 C 3109.621 327.056 3115.316 323.759 3117.41 319.607 C 3121.154 312.207 3115.411 294.552 3114.074 286.6 C 3108.721 254.654 3099.756 222.926 3087.91 192.78 C 3046.173 86.556 2970.587 1.113 2868.237 -50.062 C 2846.242 -61.054 2823.298 -69.546 2800.005 -77.307 C 2792.309 -79.875 2780.709 -85.941 2772.522 -85.23 C 2767.963 -84.832 2764.144 -79.355 2761.15 -76.359 C 2752.459 -67.679 2743.598 -59.226 2734.614 -50.857 C 2703.425 -21.802 2669.053 5.092 2633.211 28.178 C 2530.227 94.505 2413.113 141.331 2291.1 156.768 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, 490.102539, -1296.240723)"/>
                    <path style="stroke: none; fill: rgb(255, 255, 255);" d="M 2024.608 -67.568 C 2012.724 -61.492 2002.944 -38.976 1995.997 -27.765 C 1972.912 9.489 1953.172 48.306 1936.465 88.8 C 1905.56 163.704 1887.905 243.119 1882.371 323.823 C 1879.225 369.699 1880.134 415.15 1890.047 460.288 C 1904.462 525.876 1936.19 588.651 1978.882 640.348 C 2009.473 677.392 2043.505 709.897 2081.469 739.304 C 2090.926 746.628 2100.868 753.187 2110.848 759.754 C 2114.837 762.389 2120.105 767.099 2125.062 767.45 C 2133.336 768.037 2146.433 758.798 2153.493 754.883 C 2176.114 742.345 2198.1 729.136 2218.882 713.669 C 2310.418 645.559 2377.268 545.162 2396.174 431.857 C 2401.046 402.659 2403.679 373.31 2403.679 343.723 C 2403.679 335.933 2406.485 319.757 2401.757 313.056 C 2399.349 309.625 2393.037 307.844 2389.465 305.817 C 2378.244 299.448 2366.531 293.99 2355.348 287.545 C 2322.398 268.536 2290.574 247.318 2260.581 223.919 C 2190.026 168.859 2128.948 102.541 2079.26 28.149 C 2059.131 -1.978 2038.576 -34.01 2024.608 -67.568 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, 535.128113, -1109.247803)"/>
                </svg>
                <span id="brand" class="fs-4 fw-bolder">Equalify</span>
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
                    <a href="index.php?view=label_customizer" class="nav-link <?php the_active_view('label_customizer');?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi me-2 bi-plus-circle-dotted" viewBox="0 0 16 16">
                        <path d="M8 0c-.176 0-.35.006-.523.017l.064.998a7.117 7.117 0 0 1 .918 0l.064-.998A8.113 8.113 0 0 0 8 0zM6.44.152c-.346.069-.684.16-1.012.27l.321.948c.287-.098.582-.177.884-.237L6.44.153zm4.132.271a7.946 7.946 0 0 0-1.011-.27l-.194.98c.302.06.597.14.884.237l.321-.947zm1.873.925a8 8 0 0 0-.906-.524l-.443.896c.275.136.54.29.793.459l.556-.831zM4.46.824c-.314.155-.616.33-.905.524l.556.83a7.07 7.07 0 0 1 .793-.458L4.46.824zM2.725 1.985c-.262.23-.51.478-.74.74l.752.66c.202-.23.418-.446.648-.648l-.66-.752zm11.29.74a8.058 8.058 0 0 0-.74-.74l-.66.752c.23.202.447.418.648.648l.752-.66zm1.161 1.735a7.98 7.98 0 0 0-.524-.905l-.83.556c.169.253.322.518.458.793l.896-.443zM1.348 3.555c-.194.289-.37.591-.524.906l.896.443c.136-.275.29-.54.459-.793l-.831-.556zM.423 5.428a7.945 7.945 0 0 0-.27 1.011l.98.194c.06-.302.14-.597.237-.884l-.947-.321zM15.848 6.44a7.943 7.943 0 0 0-.27-1.012l-.948.321c.098.287.177.582.237.884l.98-.194zM.017 7.477a8.113 8.113 0 0 0 0 1.046l.998-.064a7.117 7.117 0 0 1 0-.918l-.998-.064zM16 8a8.1 8.1 0 0 0-.017-.523l-.998.064a7.11 7.11 0 0 1 0 .918l.998.064A8.1 8.1 0 0 0 16 8zM.152 9.56c.069.346.16.684.27 1.012l.948-.321a6.944 6.944 0 0 1-.237-.884l-.98.194zm15.425 1.012c.112-.328.202-.666.27-1.011l-.98-.194c-.06.302-.14.597-.237.884l.947.321zM.824 11.54a8 8 0 0 0 .524.905l.83-.556a6.999 6.999 0 0 1-.458-.793l-.896.443zm13.828.905c.194-.289.37-.591.524-.906l-.896-.443c-.136.275-.29.54-.459.793l.831.556zm-12.667.83c.23.262.478.51.74.74l.66-.752a7.047 7.047 0 0 1-.648-.648l-.752.66zm11.29.74c.262-.23.51-.478.74-.74l-.752-.66c-.201.23-.418.447-.648.648l.66.752zm-1.735 1.161c.314-.155.616-.33.905-.524l-.556-.83a7.07 7.07 0 0 1-.793.458l.443.896zm-7.985-.524c.289.194.591.37.906.524l.443-.896a6.998 6.998 0 0 1-.793-.459l-.556.831zm1.873.925c.328.112.666.202 1.011.27l.194-.98a6.953 6.953 0 0 1-.884-.237l-.321.947zm4.132.271a7.944 7.944 0 0 0 1.012-.27l-.321-.948a6.954 6.954 0 0 1-.884.237l.194.98zm-2.083.135a8.1 8.1 0 0 0 1.046 0l-.064-.998a7.11 7.11 0 0 1-.918 0l-.064.998zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z"></path>
                    </svg>
                        Add Label
                    </a>
                </li>
                <hr class="navbar-divider my-3">
                <li class="nav-item">
                    <a href="index.php?view=sites" class="nav-link <?php the_active_view('sites');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi me-2 bi-diagram-3" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1zM0 11.5A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                        </svg>
                        Sites
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

</body>
</html>