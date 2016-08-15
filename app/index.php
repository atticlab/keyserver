<?php

use Phalcon\Mvc\Micro;

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Read the configuration
 */
require __DIR__ . "/config/debug.php";
$config = require __DIR__ . "/config/config.php";
$app = new Micro();

/**
 * Read auto-loader
 */
require __DIR__ . "/config/loader.php";
require __DIR__ . "/vendor/autoload.php";

/**
 * Read services
 */
require __DIR__ . "/config/services.php";

require __DIR__ . "/config/routes.php";

$app->handle();
