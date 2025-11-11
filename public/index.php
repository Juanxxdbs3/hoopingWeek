<?php
declare(strict_types=1);
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Rutas
$app->get('/', function ($req, $res) {
    $res->getBody()->write('Hello from hooping.local!');
    return $res;
});

$app->run();
