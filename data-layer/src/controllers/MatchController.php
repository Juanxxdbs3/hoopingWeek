<?php
// src/controllers/MatchController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/MatchService.php';

class MatchController {
    private MatchService $service;

    public function __construct() {
        $this->service = new MatchService();
    }

    public function createMatch(Request $request, Response $response): Response {
        $body = $request->getParsedBody() ?? [];
        try {
            $m = $this->service->create($body);
            $response->getBody()->write(json_encode(['ok' => true, 'match' => $m]));
            return $response->withHeader('Content-Type','application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type','application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type','application/json')->withStatus(409);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function listMatches(Request $request, Response $response): Response {
        $q = $request->getQueryParams();
        $filters = [];
        if (!empty($q['reservation_id'])) $filters['reservation_id'] = (int)$q['reservation_id'];
        if (!empty($q['team_id'])) $filters['team_id'] = (int)$q['team_id'];
        if (!empty($q['championship_id'])) $filters['championship_id'] = (int)$q['championship_id'];
        
        // CORREGIDO: convertir string a boolean correctamente
        if (isset($q['is_friendly'])) {
            $filters['is_friendly'] = filter_var($q['is_friendly'], FILTER_VALIDATE_BOOLEAN);
        }
        
        $limit = isset($q['limit']) ? (int)$q['limit'] : 100;
        $offset = isset($q['offset']) ? (int)$q['offset'] : 0;

        try {
            $res = $this->service->list($filters, $limit, $offset);
            $response->getBody()->write(json_encode(['ok' => true, 'matches' => $res]));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function getMatchById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $m = $this->service->getById($id);
            if (!$m) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'no encontrado']));
                return $response->withHeader('Content-Type','application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'match' => $m]));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function getByReservation(Request $request, Response $response, array $args): Response {
        $rid = (int)$args['reservation_id'];
        try {
            $m = $this->service->getByReservationId($rid);
            if (!$m) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'no encontrado']));
                return $response->withHeader('Content-Type','application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'match' => $m]));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function deleteMatch(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $this->service->delete($id);
            $response->getBody()->write(json_encode(['ok' => true, 'message' => 'Match eliminado']));
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
}
