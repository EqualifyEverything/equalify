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
        <div class="d-flex flex-column flex-shrink-0 p-3 bg-light sticky-top border-end" style="width: 250px;">
            <a href="index.php" class="d-flex text-success align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
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
                    <a href="index.php?view=alerts" class="nav-link <?php the_active_class('alerts');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2 bi bi-inbox-fill" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z"></path>
                        </svg>
                        Active 
                        <span class="badge bg-danger float-end">
                            <span id="alert_count">

                                <?php 
                                // Count active alerts.
                                $filtered_to_active_status = array(
                                    array(
                                        'name' => 'status',
                                        'value' => 'active'
                                    )
                                );
                                echo DataAccess::count_db_rows(
                                    'alerts', $filtered_to_active_status
                                );
                                ?>
                            
                            </span>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=alerts&preset=equalified" class="nav-link <?php the_active_class('equalified');?>">
                        <svg viewBox="-387.606 -871.444 1637.169 1556.107" xmlns="http://www.w3.org/2000/svg" fill="currentColor"  width="16" height="16" class="me-2 bi">
                            <path d="M 694.48 -64.946 C 694.48 -188.933 661.663 -312.902 599.152 -420.729 C 573.664 -464.707 544.71 -507.875 506.874 -542.58 C 458.726 -586.743 399.108 -619.143 335.904 -637.029 C 287.052 -650.855 236.121 -659.132 185.265 -659.132 C 173.805 -659.132 162.484 -659.073 151.07 -658.303 C 146.172 -657.973 139.943 -659.158 135.554 -656.412 C 128.511 -652.005 123.974 -638.092 120.342 -630.924 C 107.247 -605.062 97.266 -577.673 89.151 -549.94 C 57.942 -443.305 69.134 -331.392 119.417 -232.374 C 130.637 -210.289 144.546 -189.633 159.12 -169.587 C 163.611 -163.41 169.895 -151.143 176.984 -147.813 C 181.346 -145.757 188.999 -148.66 193.582 -149.324 C 208.036 -151.416 222.527 -153.063 237.018 -154.765 C 280.869 -159.934 327.041 -160.816 371.022 -156.694 C 445.888 -149.678 519.193 -135.883 590.05 -110.397 C 612.932 -102.162 635.38 -92.871 657.514 -82.862 C 669.879 -77.275 681.708 -69.523 694.48 -64.946 M 742.537 79.734 C 761.677 75.376 780.872 66.767 798.91 59.188 C 839.028 42.336 877.88 22.554 914.431 -0.941 C 1007.679 -60.869 1099.864 -144.874 1147.034 -246.022 C 1172.634 -300.918 1184.287 -360.565 1184.287 -420.729 C 1184.287 -474.079 1175.887 -525.959 1160.915 -577.238 C 1157.311 -589.576 1152.856 -601.619 1148.291 -613.636 C 1146.766 -617.654 1145.204 -624.61 1141.415 -627.136 C 1134.678 -631.619 1121.056 -631.673 1113.126 -632.744 C 1089.061 -635.993 1065.32 -637.294 1041.042 -637.294 C 929.587 -637.294 815.877 -598.469 736.067 -519.912 C 715.662 -499.826 696.919 -479.12 679.804 -456.217 C 674.998 -449.792 663.594 -438.828 662.726 -430.739 C 662.236 -426.125 667.273 -420.175 669.296 -416.18 C 675.553 -403.878 681.57 -391.457 687.217 -378.873 C 705.191 -338.817 719.322 -297.452 730.633 -255.122 C 750.66 -180.125 759.846 -98.622 754.468 -21.269 C 752.102 12.727 746.899 45.939 742.537 79.734 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, -56.54034, -388.24704)"/>
                            <path d="M 367.426 -388.26 C 248.503 -426.35 119.204 -432.746 -3.166 -408.642 C -55.168 -398.396 -107.199 -383.81 -153.805 -358.359 C -208.228 -328.631 -254.558 -285.983 -290.71 -236.301 C -321.733 -193.67 -346.714 -145.962 -363.589 -96.17 C -367.369 -85.024 -370.76 -73.804 -374.023 -62.503 C -375.252 -58.226 -378.154 -51.456 -376.814 -47.034 C -373.866 -37.298 -358.239 -25.697 -350.651 -19.208 C -327.64 0.473 -304.562 19.727 -278.566 35.596 C -184.302 93.141 -76.655 116.108 32.877 98.018 C 56.036 94.197 78.993 88.273 101.266 80.966 C 108.955 78.436 120.34 76.808 126.846 71.967 C 129.989 69.628 129.637 63.841 130.164 60.338 C 131.754 49.701 133.731 39.037 135.875 28.49 C 143.805 -10.527 154.858 -48.7 168.444 -86.161 C 195.41 -160.503 234.993 -231.997 283.927 -294.536 C 300.063 -315.146 316.624 -335.947 335.089 -354.592 C 344.607 -364.2 361.667 -376.367 367.426 -388.26 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, -87.491196, -22.999788)"/>
                            <path d="M -189.14 -72.794 L -188.216 -71.885 L -189.14 -72.794 M 334.863 162.878 C 343.559 177.91 356.58 191.66 368.012 204.736 C 392.188 232.389 417.945 258.112 445.763 282.225 C 542.208 365.821 670.603 434.285 800.642 443.057 C 857.617 446.896 916.246 436.131 969.765 417.378 C 1015.917 401.208 1059.944 378.642 1100.071 350.98 C 1110.163 344.028 1120.144 336.894 1129.644 329.169 C 1133.073 326.384 1138.627 323.218 1140.669 319.232 C 1144.32 312.126 1138.719 295.174 1137.416 287.539 C 1132.195 256.865 1123.453 226.401 1111.901 197.456 C 1071.2 95.462 997.49 13.422 897.679 -35.715 C 876.23 -46.269 853.855 -54.423 831.14 -61.875 C 823.635 -64.341 812.323 -70.165 804.339 -69.482 C 799.893 -69.1 796.169 -63.841 793.249 -60.965 C 784.774 -52.63 776.133 -44.514 767.372 -36.478 C 736.958 -8.58 703.438 17.243 668.486 39.409 C 568.057 103.095 453.849 148.056 334.863 162.878 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, 177.569305, -229.288773)"/>
                            <path d="M 77.45 -53.781 C 65.861 -47.948 56.324 -26.329 49.549 -15.564 C 27.037 20.206 7.787 57.477 -8.506 96.358 C -38.644 168.279 -55.861 244.531 -61.258 322.021 C -64.326 366.07 -63.439 409.711 -53.772 453.051 C -39.715 516.027 -8.774 576.302 32.859 625.939 C 62.691 661.508 95.878 692.718 132.9 720.954 C 142.123 727.987 151.818 734.284 161.55 740.59 C 165.44 743.12 170.578 747.642 175.412 747.979 C 183.48 748.543 196.252 739.672 203.137 735.913 C 225.197 723.874 246.637 711.191 266.904 696.34 C 356.169 630.943 421.36 534.545 439.797 425.753 C 444.548 397.718 447.116 369.537 447.116 341.129 C 447.116 333.649 449.852 318.117 445.241 311.683 C 442.893 308.389 436.738 306.679 433.254 304.732 C 422.312 298.617 410.889 293.376 399.984 287.188 C 367.851 268.936 336.817 248.563 307.568 226.096 C 238.764 173.229 179.201 109.552 130.746 38.123 C 111.117 9.196 91.072 -21.561 77.45 -53.781 Z" transform="matrix(0.839096, 0.543984, -0.543984, 0.839096, 219.804794, -48.889931)"/>
                        </svg>
                        Equalified 
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=alerts&preset=ignored" class="nav-link <?php the_active_class('ignored');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2 bi bi-bell-slash-fill" viewBox="0 0 16 16">
                            <path d="M5.164 14H15c-1.5-1-2-5.902-2-7 0-.264-.02-.523-.06-.776L5.164 14zm6.288-10.617A4.988 4.988 0 0 0 8.995 2.1a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 7c0 .898-.335 4.342-1.278 6.113l9.73-9.73zM10 15a2 2 0 1 1-4 0h4zm-9.375.625a.53.53 0 0 0 .75.75l14.75-14.75a.53.53 0 0 0-.75-.75L.625 15.625z"></path>
                        </svg>
                        Ignored 
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=alerts&preset=all" class="nav-link <?php the_active_class('all');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2 bi bi-inboxes-fill" viewBox="0 0 16 16">
                            <path d="M4.98 1a.5.5 0 0 0-.39.188L1.54 5H6a.5.5 0 0 1 .5.5 1.5 1.5 0 0 0 3 0A.5.5 0 0 1 10 5h4.46l-3.05-3.812A.5.5 0 0 0 11.02 1H4.98zM3.81.563A1.5 1.5 0 0 1 4.98 0h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 10H1.883A1.5 1.5 0 0 1 .394 8.686l-.39-3.124a.5.5 0 0 1 .106-.374L3.81.563zM.125 11.17A.5.5 0 0 1 .5 11H6a.5.5 0 0 1 .5.5 1.5 1.5 0 0 0 3 0 .5.5 0 0 1 .5-.5h5.5a.5.5 0 0 1 .496.562l-.39 3.124A1.5 1.5 0 0 1 14.117 16H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .121-.393z"></path>
                        </svg>
                        All
                    </a>
                </li>
                <hr class="navbar-divider my-3">
                <?php
                // Show various labels.
                $filtered_to_labels = array(
                    array(
                        'name' => 'meta_name',
                        'value' => 'label_%',
                        'operator' => 'LIKE'
                    )
                );
                $labels = DataAccess::get_db_rows(
                    'meta', $filtered_to_labels, 1, 1000
                )['content'];
                if(!empty($labels)): foreach($labels as $label):
                ?>

                <li class="nav-item">
                    <a href="index.php?view=alerts&label=<?php echo $label->meta_name;?>" class="nav-link <?php the_active_class($label->meta_name);?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi me-2 bi-tag-fill" viewBox="0 0 16 16">
                            <path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"></path>
                        </svg>
                        
                        <?php
                        // We'll get some data to make the label.
                        $label_meta = unserialize($label->meta_value);

                        // Let's extract the "title" meta, so we can use it 
                        // later and so we can use any label's meta_values to
                        // fitler the alerts.
                        foreach($label_meta as $k => $val) {
                            if($val['name'] == 'title') {
                                $the_title = $val['value'];
                                unset($label_meta[$k]);
                            }
                        }
                        echo $the_title;
                        ?>                        

                        <span class="badge text-bg-light float-end">
                            <span id="alert_count">

                                <?php 
                                // Count items, filtered to label_meta.
                                echo DataAccess::count_db_rows(
                                    'alerts', $label_meta
                                );
                                ?>
                            
                            </span>
                        </span>
                    </a>
                </li>

                <?php
                // End labels.
                endforeach; endif;
                ?>

                <li class="nav-item">
                    <a href="index.php?view=label_customizer" class="nav-link <?php the_active_class('label_customizer');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2 bi bi-plus-square-fill" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm6.5 4.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3a.5.5 0 0 1 1 0z"></path>
                        </svg>
                        Add Label
                    </a>
                </li>
                <hr class="navbar-divider my-3">
                <li class="nav-item">
                    <a href="index.php?view=sites" class="nav-link <?php the_active_class('sites');?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2 bi bi-diagram-3-fill" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zm-6 8A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm6 0A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm6 0a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1z"></path>
                        </svg>
                        Sites
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=integrations" class="nav-link <?php the_active_class('integrations');?>">
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