<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

$config = [
    "displayErrorDetails" => true
];
$app = new Slim\App(["settings" => $config]);

Registry\Middleware::register($app);
Registry\Router::register($app);
Registry\Dependency::register($app);

$app->run();