<?php
// Debug PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add Dependencies
require_once 'config.php';
require_once 'models/db.php';
require_once 'models/template.php';

// Setup DB Connection
$db = connect(
    DB_HOST, 
    DB_USERNAME,
    DB_PASSWORD,
    DB_NAME
);
$records = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Equalify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link href="theme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>
</head>
<body>
    <main>
        <div class="container">
            <header class="border-bottom mb-4">
                <nav class="navbar py-3 navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="index.php?view=log">✅ Equalify</a>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                <li>
                                    <a href="index.php?view=log" class="nav-link <?php if(current_view() == 'log' || current_view() == '') echo 'active" aria-current="page';?>">Log</a>
                                </li>
                                <li>
                                    <a href="index.php?view=sites" class="nav-link <?php if(current_view() == 'sites') echo 'active" aria-current="page';?>">Sites</a>
                                </li>
                                <li>
                                    <a href="index.php?view=policies" class="nav-link <?php if(current_view() == 'policies') echo 'active" aria-current="page';?>">Policies</a>
                                </li>
                            </ul>
                            <span class="navbar-text d-inline-block px-2"><?php echo get_account_credits($db, 1);?> Credits Remain </span>
                            <a href="?view=account" class="btn btn-outline-dark <?php if(current_view() == 'account') echo 'active" aria-current="page';?>">Account</a>
                        </div>
                    </div>
                </nav>
            </header>

            <?php
            // Show View
            if(!empty($_GET['view'])){
                require_once 'views/'.$_GET['view'].'.php';
            }else{
                require_once 'views/log.php';
            }
            ?>

        </div>
    </main>

</body>
</html>