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
        $field = $this->service->getById($id);
        if (!$field) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'no encontrado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $response->getBody()->write(json_encode(['ok' => true, 'field' => $field]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
