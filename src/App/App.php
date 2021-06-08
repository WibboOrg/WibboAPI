<?php 
date_default_timezone_set('Europe/Paris');

require __DIR__ . '/../../vendor/autoload.php';
$baseDir = __DIR__ . '/../../';
$envFile = $baseDir . '.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable($baseDir);
    $dotenv->load();
}

$settings = require __DIR__ . '/Settings.php';
$app = new \Slim\App($settings);
require __DIR__ . '/Dependencies.php';
require __DIR__ . '/Middleware.php';
require __DIR__ . '/Routes.php';