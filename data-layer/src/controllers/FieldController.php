<?php
// src/controllers/FieldController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/FieldService.php';
require_once __DIR__ . '/../services/ReservationService.php';

class FieldController {
    private FieldService $fieldService;
    private ReservationService $reservationService;

    public function __construct() {
        $this->fieldService = new FieldService();
        $this->reservationService = new ReservationService();
    }

    public function createField(Request $request, Response $response): Response {
        $body = $request->getParsedBody() ?? [];
        try {
            $field = $this->fieldService->create($body);
            $response->getBody()->write(json_encode(['ok' => true, 'field' => $field]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAllFields(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $fields = $this->fieldService->list($limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $fields]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFieldById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $field = $this->fieldService->getById($id);
            if (!$field) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Campo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'field' => $field]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function listByState(Request $request, Response $response, array $args): Response {
        $state = $args['state'];
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->fieldService->listByState($state, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listByLocation(Request $request, Response $response, array $args): Response {
        $location = $args['location'];
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->fieldService->listByLocation($location, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function search(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $filters = [
            'location' => $params['location'] ?? null,
            'state' => $params['state'] ?? null,
            'sport' => $params['sport'] ?? null
        ];
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->fieldService->search($filters, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        
        try {
            $field = $this->fieldService->update($id, $body);
            $response->getBody()->write(json_encode(['ok' => true, 'field' => $field]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function changeState(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        $state = $body['state'] ?? null;
        
        try {
            if (!$state) throw new InvalidArgumentException("state requerido");
            $this->fieldService->changeState($id, $state);
            $response->getBody()->write(json_encode(['ok' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $params = $request->getQueryParams();
        $force = isset($params['force']) && $params['force'] === 'true';
        
        try {
            $ok = $this->fieldService->delete($id, $force);
            if (!$ok) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Campo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'message' => $force ? 'Eliminado permanentemente' : 'Desactivado']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /*
    public function getAvailability(Request $request, Response $response, array $args): Response {
        $fieldId = (int)$args['id'];
        $query = $request->getQueryParams();
        $date = $query['date'] ?? date('Y-m-d');

        try {
            $result = $this->reservationService->getAvailability($fieldId, $date);
            $response->getBody()->write(json_encode(['ok' => true, 'availability' => $result]));
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
    */
}
