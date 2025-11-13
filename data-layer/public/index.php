<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

// Cargar configuración
$config = require __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/database/BDConnection.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Inicializar pool de conexiones
BDConnection::init($config['db']);

// Cargar rutas (solo las que existen)
$userRoutes = require __DIR__ . '/../src/routes/userRoutes.php';
$userRoutes($app, $config);

$fieldRoutes = require __DIR__ . '/../src/routes/fieldRoutes.php';
$fieldRoutes($app);

$teamRoutes = require __DIR__ . '/../src/routes/TeamRoutes.php';  // Nota: T mayúscula
$teamRoutes($app);

$reservationRoutes = require __DIR__ . '/../src/routes/ReservationRoutes.php';  // Nota: R mayúscula
$reservationRoutes($app);

$operatingHoursRoutes = require __DIR__ . '/../src/routes/operatingHoursRoutes.php';
$operatingHoursRoutes($app);

$matchRoutes = require __DIR__ . '/../src/routes/matchRoutes.php';
$matchRoutes($app);

$championshipRoutes = require __DIR__ . '/../src/routes/championshipRoutes.php';
$championshipRoutes($app);

$managerShiftRoutes = require __DIR__ . '/../src/routes/managerShiftRoutes.php';
$managerShiftRoutes($app);

$statisticsRoutes = require __DIR__ . '/../src/routes/statisticsRoutes.php';
$statisticsRoutes($app);

// Health check endpoint
$app->get('/health', function (Request $req, Response $res) {
    $res->getBody()->write(json_encode([
        'service' => 'data-layer',
        'status' => 'healthy',
        'timestamp' => date('c'),
        'db_pool_size' => BDConnection::getPoolSize()
    ]));
    return $res->withHeader('Content-Type', 'application/json');
});

// Ruta raíz con test de BD
$app->get('/', function (Request $req, Response $res) {
    try {
        $conn = BDConnection::getConnection();
        $stmt = $conn->query("SELECT 1 AS ok");
        $r = $stmt->fetch();
        BDConnection::releaseConnection($conn);

        $payload = [
            'app' => 'Hooping Week Data Layer',
            'version' => '1.0.0',
            'status' => 'running',
            'db_ok' => (bool)($r['ok'] ?? false),
            'pool_available' => BDConnection::getPoolSize(),
            'docs' => '/api/docs'
        ];
        $res->getBody()->write(json_encode($payload));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Throwable $e) {
        $payload = [
            'app' => 'Hooping Week Data Layer',
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        $res->getBody()->write(json_encode($payload));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->run();
