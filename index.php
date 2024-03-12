<?php
// Let's get things going!
require_once 'init.php';

// Install if not installed
require_once('actions/install.php');

// Required components
require_once('components/active_class.php');

// Get current title and active page for screenreader
require_once('helpers/get_page_title.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta description="Equalify manages web accessibility issues with integrations with your favorite services." />
    <title><?php echo get_page_title(); ?></title>
    <link href="assets/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" >
    <link href="theme.css" rel="stylesheet">
</head>

<body>
    <a href="#main" class="visually-hidden-focusable">Skip to main content</a>
    <header class="py-3 border-bottom  border-secondary-subtle">
        <div class="container d-flex flex-wrap justify-content-center">
            <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none" rel="home">
                <img src="logo.svg" height="40" class="me-2" alt="Equalify Logo">
            </a>
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a href="index.php?view=reports" class="<?php echo the_active_class('reports'); ?> nav-link" <?php echo (the_active_page() == 'reports' || the_active_page() == 'report') ? 'aria-current="page"' : ''; ?>>Reporting</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=scans" class="<?php echo the_active_class('scans'); ?> nav-link" <?php echo the_active_page() == 'scans' ? 'aria-current="page"' : ''; ?>>Scanning</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?view=discovery" class="<?php echo the_active_class('discovery'); ?> nav-link" <?php echo the_active_page() == 'discovery' ? 'aria-current="page"' : ''; ?>>Discovery</a>
                </li>
                <?php
                // Only show account in "managed" mode
                if ($GLOBALS["managed_mode"]) {
                ?>
                    <li class="nav-item"><a href="index.php?view=account" class="<?php the_active_class('account'); ?> nav-link"<?php echo the_active_page() == 'account' ? 'aria-current="page"' : ''; ?>>My Account</a></li>

                <?php
                }
                ?>

            </ul>
        </div>
    </header>
    <main id="main">

        <?php
        // Select the view.
        if (!empty($_GET['view'])) {
            require_once 'views/' . $_GET['view'] . '.php';
        } else {

            // This is the defaul view
            require_once 'views/reports.php';
        }
        ?>

    </main>
    <footer class="py-4 mt-4 text-center">
        Equalify Version 1 - Release Candidate 4
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
    <script src="assets/bootstrap/dist/js/bootstrap.min.js"dde></script>
</body>

</html>