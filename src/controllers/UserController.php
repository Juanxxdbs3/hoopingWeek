<?php
// src/controllers/UserController.php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/UserService.php';

class UserController {
    private UserService $service;

    public function __construct(array $dbConfig) {
        $this->service = new UserService($dbConfig);
    }

    // Nota: usamos los tipos PSR correctos
    public function register(Request $request, Response $response): Response {
        $body = $request->getParsedBody() ?? [];
        try {
            $user = $this->service->register($body);
            $payload = ['ok' => true, 'user' => $user];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        } catch (\Throwable $e) {
            // Log internal error (no exponer detalles en prod)
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function list(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $users = $this->service->list($limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'users' => $users]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $user = $this->service->getById($id);
        if (!$user) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'no encontrado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $response->getBody()->write(json_encode(['ok' => true, 'user' => $user]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
