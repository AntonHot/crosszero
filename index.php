<?php

use Slim\Slim;

require_once 'vendor/autoload.php';
require_once 'app/bootstrap.php';

Slim::registerAutoloader();
$app = new Slim();

require_once 'app/routes.php';

$app->run();
