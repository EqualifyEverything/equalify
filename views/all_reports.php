<?php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader);

$routes = [
    'all-reports' => 'all-reports.html.twig',
    // 'Settings' => 'Settings.html.twig'
];

$route = $_GET['route'] ?? 'all-reports';
$template = $routes[$route] ?? 'all-reports.html.twig';

echo $twig->render($template);
// require_once 'vendor/autoload.php';

// $loader = new \Twig\Loader\ArrayLoader([
//     'index' => 'Hello {{ name }}!',
// ]);
// $twig = new \Twig\Environment($loader);

// echo $twig->render('index', ['name' => 'Equalify User']);

