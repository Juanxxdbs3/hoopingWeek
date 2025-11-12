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

    public function update(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        try {
            $user = $this->service->update($id, $body);
            $response->getBody()->write(json_encode(['ok' => true, 'user' => $user]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $response->withHeader('Content-Type','application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $response->withHeader('Content-Type','application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function changeState(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        $stateId = isset($body['state_id']) ? (int)$body['state_id'] : null;
        try {
            if ($stateId === null) throw new InvalidArgumentException("state_id requerido");
            $this->service->changeState($id, $stateId);
            $response->getBody()->write(json_encode(['ok'=>true]));
            return $response->withHeader('Content-Type','application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $response->withHeader('Content-Type','application/json')->withStatus(400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function changeAthleteState(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        // athlete_state_id puede ser null (pasar explícito)
        $astateId = array_key_exists('athlete_state_id', $body)
            ? ($body['athlete_state_id'] === null ? null : (int)$body['athlete_state_id'])
            : null;
        try {
            // Permitimos enviar { "athlete_state_id": null } para limpiar
            if (!array_key_exists('athlete_state_id', $body)) {
                throw new InvalidArgumentException("athlete_state_id requerido");
            }
            $this->service->changeAthleteState($id, $astateId);
            $response->getBody()->write(json_encode(['ok'=>true]));
            return $response->withHeader('Content-Type','application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $response->withHeader('Content-Type','application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $response->withHeader('Content-Type','application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    // Nuevos endpoints
    public function listByRole(Request $request, Response $response, array $args): Response {
        $roleParam = $args['role']; // puede ser id numérico o nombre
        $roleId = ctype_digit($roleParam) ? (int)$roleParam : null;
        $users = $this->service->listByRole($roleId, $roleParam);
        $response->getBody()->write(json_encode(['ok'=>true,'users'=>$users]));
        return $response->withHeader('Content-Type','application/json');
    }

    public function listByState(Request $request, Response $response, array $args): Response {
        $stateParam = $args['state'];
        $stateId = ctype_digit($stateParam) ? (int)$stateParam : null;
        $users = $this->service->listByState($stateId, $stateParam);
        $response->getBody()->write(json_encode(['ok'=>true,'users'=>$users]));
        return $response->withHeader('Content-Type','application/json');
    }

    public function listAthletesByAthleteState(Request $request, Response $response, array $args): Response {
        $astateParam = $args['athlete_state'];
        $astateId = $astateParam === 'null' ? null : (ctype_digit($astateParam) ? (int)$astateParam : null);
        $users = $this->service->listAthletesByAthleteState($astateId, $astateParam);
        $response->getBody()->write(json_encode(['ok'=>true,'users'=>$users]));
        return $response->withHeader('Content-Type','application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $query = $request->getQueryParams();
        $force = isset($query['force']) && ($query['force'] == '1' || $query['force'] == 'true');
        try {
            $ok = $this->service->delete($id, $force);
            if (!$ok) {
                $response->getBody()->write(json_encode(['ok'=>false,'error'=>'no se pudo eliminar']));
                return $response->withHeader('Content-Type','application/json')->withStatus(400);
            }
            $response->getBody()->write(json_encode(['ok'=>true]));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }
}
