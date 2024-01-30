<?php
// Let's get things going!
require_once 'init.php';

// Install if not installed
require_once('actions/install.php');

// Required components
require_once('components/active_class.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta description="Equalify manages web accessibility issues with integrations with your favorite services." />
    <title>Equalify | Accessibility Issue Management</title>
    <link href="assets/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="theme.css" rel="stylesheet">
</head>

<body>
    <a href="#main" class="visually-hidden-focusable">Skip to main content</a>
    <div id="accessibilityAnnouncer" class="visually-hidden" aria-live="assertive"></div>
    <header class="py-3 border-bottom  border-secondary-subtle">
        <div class="container d-flex flex-wrap justify-content-center">
            <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none" rel="home">
                <img src="logo.svg" height="40" class="me-2" alt="Equalify Logo">
            </a>
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="index.php?view=reports" class="<?php the_active_class('reports');?> nav-link" aria-current="page">Reports</a></li>
                <li class="nav-item"><a href="index.php?view=scans" class="<?php the_active_class('scans');?> nav-link" aria-current="page">Scans</a></li>
                <li class="nav-item"><a href="index.php?view=settings" class="<?php the_active_class('settings');?> nav-link">Settings</a></li>
                
                <?php
                // Only show account in "managed" mode
                if($GLOBALS["managed_mode"]){ 
                ?>
                
                <li class="nav-item"><a href="index.php?view=account" class="<?php the_active_class('account');?> nav-link">My Account</a></li>

                <?php
                }
                ?>

            </ul>
        </div>
    </header>
    <main id="main">
        
        <?php
        // Select the view.
        if(!empty($_GET['view'])){
            require_once 'views/'.$_GET['view'].'.php';
        }else{

            // This is the defaul view
            require_once 'views/reports.php';

        }
        ?>

    </main>
    <footer class="py-4 mt-4 text-center">
        Equalify Version 1 - Release Candidate 3
        <nav aria-label="Footer Links">
            <ul class="nav justify-content-center">
                <li class="nav-item">
                    <a href="https://github.com/equalifyEverything/v1/issues">Report an Issue</a>
                </li>
                <li class="nav-item">
                    <a href="https://github.com/EqualifyEverything/v1/blob/main/ACCESSIBILITY.md">Accessibility Statement</a>
                </li>
            </ul>
        </nav>
    </footer>
    <script src="assets/bootstrap/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>