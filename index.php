<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Equalify is a platform developed to integrate various
 * services that manage websites.
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
 * or (at your option) any later version.
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

define('EQUALIFY_ROOT', getcwd());

// Add dependencies.
require_once 'config.php';
require_once 'models/db.php';
require_once 'models/view_components.php';
require_once 'models/integrations.php';

// We check to make sure all the DB tables are installed.
require_once 'install.php';

// We also check to see if we can run the scan on every
// page load.
require_once 'actions/run_scheduled_scan.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta description="Equalify manages web accessibility issues with integrations with your favorite services." />
    <title>Equalify | Accessibility Issue Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="theme.css" rel="stylesheet">
</head>
<body>
    <header class="py-3 mb-4">
        <div class="container d-flex flex-wrap justify-content-center">
            <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
                <img src="logo.svg" height="40" class="me-2" alt="Equalify Logo" aria-hidden="true">
                <span class="fs-4 visually-hidden">Equalify</span>
            </a>
            <ul class="nav nav-pills">
                <li class="nav-item"><a href="index.php?view=notice_editor" class="nav-link <?php the_active_class('notice_editor');?>">Add Notice</a></li>
                <li class="nav-item"><a href="index.php?view=reports" class="<?php the_active_class('reports');?> nav-link" aria-current="page">Reports</a></li>
                <li class="nav-item"><a href="index.php?view=settings" class="nav-link <?php the_active_class('settings');?>">Settings</a></li>
                <li class="nav-item"><a href="#" class="<?php the_active_class('account');?> nav-link">My Account</a></li>
            </ul>
        </div>
    </header>
    <main class="container">

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

    </main>
    <footer class="py-4 mt-4 text-center">
        Equalify Release Candidate 2
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
</body>
</html>