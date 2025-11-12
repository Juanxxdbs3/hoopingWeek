<?php
// src/controllers/FieldController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/FieldService.php';

class FieldController {
    private FieldService $service;

    public function __construct() {
        $this->service = new FieldService();
    }

    public function createField(Request $request, Response $response): Response {
        $body = $request->getParsedBody() ?? [];
        try {
            $field = $this->service->create($body);
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
        $fields = $this->service->list($limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $fields]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFieldById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $field = $this->service->getById($id);
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
        
        $result = $this->service->listByState($state, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listByLocation(Request $request, Response $response, array $args): Response {
        $location = $args['location'];
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->service->listByLocation($location, $limit, $offset);
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
        
        $result = $this->service->search($filters, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        
        try {
            $field = $this->service->update($id, $body);
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
            $this->service->changeState($id, $state);
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
            $ok = $this->service->delete($id, $force);
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
}
