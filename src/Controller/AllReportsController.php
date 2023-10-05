<?php
require_once 'vendor/autoload.php';

// $loader = new \Twig\Loader\FilesystemLoader;
// $twig = new \Twig\Environment;
$loader = new \Twig\Loader\FilesystemLoader("/Users/jameswesleygoedert/RECENTER/mwm_Buildings/CodeSilo/L1CurrentWork/Decubing/equalify/templates");
$twig = new \Twig\Environment($loader);

$dummyReports = [
    [
        'title' => 'Report 1',
        'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
    ],
    [
        'title' => 'Report 2',
        'content' => 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
    ],
    [
        'title' => 'Report 3',
        'content' => 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
    ],
    [
        'title' => 'Report 4',
        'content' => 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
    ],
    [
        'title' => 'Report 5',
        'content' => 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
    ],
    [
        'title' => 'Report 6',
        'content' => 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
    ],
    // Add more dummy reports as needed
];
var_dump($dummyReports);

echo $twig->render('templates/all-reports.html.twig', ['reports' => $dummyReports]);