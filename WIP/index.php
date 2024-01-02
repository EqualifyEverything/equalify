<?php
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
    <link href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="theme.css" rel="stylesheet">
</head>

<body>
    <div id="accessibilityAnnouncer" class="visually-hidden" aria-live="assertive"></div>
    <header class="py-3 border-bottom  border-secondary">
        <div class="container d-flex flex-wrap justify-content-center">
            <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
                <img src="logo.svg" height="40" class="me-2" alt="Equalify Logo" aria-hidden="true">
                <span class="fs-4 visually-hidden">Equalify</span>
            </a>
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="index.php?view=reports" class="<?php the_active_class('reports');?> nav-link" aria-current="page">Reports</a></li>
                <li class="nav-item"><a href="index.php?view=settings" class="<?php the_active_class('settings');?> nav-link">Settings</a></li>
                <li class="nav-item"><a href="#" class="<?php the_active_class('account');?> nav-link">My Account</a></li>
            </ul>
        </div>
    </header>
    <main>
        
        <?php
        // Select the view.
        if(!empty($_GET['view'])){
            require_once 'views/'.$_GET['view'].'.php';
        }else{

            // This is the defaul view
            require_once 'views/report.php';

        }
        ?>

    </main>
    <footer class="py-4 mt-4 text-center">
        Equalify Release Candidate 2
    </footer>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>