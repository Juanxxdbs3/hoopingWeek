<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

// Cargar config y clase de BD
$config = require __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/database/BDConnection.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Inicializa el pool
BDConnection::init($config['db']);

//Routes
$userRoutes = require __DIR__ . '/../src/routes/userRoutes.php';
$userRoutes($app, $config);

$fieldRoutes = require __DIR__ . '/../src/routes/fieldRoutes.php';
$fieldRoutes($app);  // No necesita $config porque FieldService no lo usa

$teamRoutes = require __DIR__ . '/../src/routes/teamRoutes.php';
$teamRoutes($app);

$reservationRoutes = require __DIR__ . '/../src/routes/reservationRoutes.php';
$reservationRoutes($app);

$operatingHoursRoutes = require __DIR__ . '/../src/routes/operatingHoursRoutes.php';
$operatingHoursRoutes($app);

$matchRoutes = require __DIR__ . '/../src/routes/matchRoutes.php';
$matchRoutes($app);

$championshipRoutes = require __DIR__ . '/../src/routes/championshipRoutes.php';
$championshipRoutes($app);

$managerShiftRoutes = require __DIR__ . '/../src/routes/managerShiftRoutes.php';
$managerShiftRoutes($app);

// Ruta raíz que devuelve status (JSON)
$app->get('/', function (Request $req, Response $res) {
    try {
        $conn = BDConnection::getConnection();
        $stmt = $conn->query("SELECT 1 AS ok");
        $r = $stmt->fetch();
        BDConnection::releaseConnection($conn);

        $payload = [
            'ok' => true,
            'db_test' => $r['ok'] ?? null,
            'pool_available' => BDConnection::getPoolSize()
        ];
        $res->getBody()->write(json_encode($payload));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Throwable $e) {
        $payload = [
            'ok' => false,
            'error' => $e->getMessage()
        ];
        $res->getBody()->write(json_encode($payload));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

/*
Rutas de ejemplo
$app->get('/hola', function (Request $req, Response $res) {
    $res->getBody()->write('Hola mundo desde Slim con BD.');
    return $res;
});

$app->get('/db-status', function (Request $req, Response $res) {
    $payload = ['pool_size' => BDConnection::getPoolSize()];
    $res->getBody()->write(json_encode($payload));
    return $res->withHeader('Content-Type', 'application/json');
});
*/

try {
    $conn = BDConnection::getConnection();
    $stmt = $conn->query("SELECT 1 AS ok");
    $r = $stmt->fetch();
    BDConnection::releaseConnection($conn);
    /*
    echo "<pre>Conexión OK (test: " . ($r['ok'] ?? 'n/a') . ")\nPool disponible: " . BDConnection::getPoolSize() . "</pre>";*/
} catch(Throwable $e) {
    //echo "<pre>Error BD: " . $e->getMessage() . "</pre>";
}

$app->run();
