<?php

// header('Access-Control-Allow-Origin: https://testhorario.intersoftalmeria.es');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

error_reporting(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config.php';

$containerBuilder = new DI\ContainerBuilder();

$containerBuilder->addDefinitions(__DIR__ . '/../app/container.php');

// Create DI container instance
$container = $containerBuilder->build();

$settings =  $container->get('settings');

$app = $container->get(Slim\App::class);

$app->setBasePath(API_BASE_PATH);

// Register routes
(require __DIR__ . '/../app/routes.php')($app);


// Register middleware
(require __DIR__ . '/../app/middleware.php')($app);

$app->run();