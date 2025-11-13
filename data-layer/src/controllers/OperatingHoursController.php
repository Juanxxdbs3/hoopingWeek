<?php
// filepath: c:\xampp\htdocs\hooping_week\src\controllers\OperatingHoursController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/OperatingHoursService.php';

class OperatingHoursController {
    private OperatingHoursService $service;

    public function __construct() {
        $this->service = new OperatingHoursService();
    }

    public function getByField(Request $request, Response $response, array $args): Response {
        $fieldId = (int)$args['field_id'];
        
        try {
            $hours = $this->service->getByField($fieldId);
            $response->getBody()->write(json_encode(['ok' => true, 'operating_hours' => $hours]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function create(Request $request, Response $response, array $args): Response {
        $fieldId = (int)$args['field_id'];
        $body = (array)$request->getParsedBody();
        
        try {
            $dayOfWeek = isset($body['day_of_week']) ? (int)$body['day_of_week'] : null;
            $openTime = $body['open_time'] ?? null;
            $closeTime = $body['close_time'] ?? null;

            if ($dayOfWeek === null || !$openTime || !$closeTime) {
                throw new \InvalidArgumentException("day_of_week, open_time y close_time son requeridos");
            }

            $result = $this->service->create($fieldId, $dayOfWeek, $openTime, $closeTime);
            $response->getBody()->write(json_encode(['ok' => true, 'operating_hour' => $result]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createException(Request $request, Response $response, array $args): Response {
        $fieldId = (int)$args['field_id'];
        $body = (array)$request->getParsedBody();
        
        try {
            $date = $body['date'] ?? null;
            $reason = $body['reason'] ?? 'Sin especificar';
            $overridesRegular = isset($body['overrides_regular']) ? (bool)$body['overrides_regular'] : false;
            $openTime = $body['open_time'] ?? null;
            $closeTime = $body['close_time'] ?? null;

            if (!$date) {
                throw new \InvalidArgumentException("date es requerido");
            }

            $result = $this->service->createException($fieldId, $date, $reason, $overridesRegular, $openTime, $closeTime);
            $response->getBody()->write(json_encode(['ok' => true, 'exception' => $result]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getException(Request $request, Response $response, array $args): Response {
        $fieldId = (int)$args['field_id'];
        $query = $request->getQueryParams();
        $date = $query['date'] ?? null;
        
        try {
            if (!$date) {
                throw new \InvalidArgumentException("Parámetro 'date' es requerido");
            }

            $exception = $this->service->getException($fieldId, $date);
            if (!$exception) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Excepción no encontrada']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode(['ok' => true, 'exception' => $exception]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}