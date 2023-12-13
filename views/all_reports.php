<?php

use Equalify\Controller\AllReportsController;

$BASE_PATH = __DIR__ . '/..';

require_once($BASE_PATH . '/init.php');
require_once($BASE_PATH . '/vendor/autoload.php');

$controller = new AllReportsController();
$controller->run();

