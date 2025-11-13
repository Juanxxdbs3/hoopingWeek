<?php
// src/controllers/ManagerShiftController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/ManagerShiftService.php';

class ManagerShiftController {
    private ManagerShiftService $service;
    public function __construct() { $this->service = new ManagerShiftService(); }

    public function create(Request $req, Response $res): Response {
        $body = $req->getParsedBody() ?? [];
        try {
            $s = $this->service->create($body);
            $res->getBody()->write(json_encode(['ok'=>true,'shift'=>$s]));
            return $res->withHeader('Content-Type','application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $res->withHeader('Content-Type','application/json')->withStatus(409);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function list(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        $filters = [];
        if (!empty($q['manager_id'])) $filters['manager_id'] = (int)$q['manager_id'];
        if (!empty($q['field_id'])) $filters['field_id'] = (int)$q['field_id'];
        if (isset($q['day_of_week'])) $filters['day_of_week'] = (int)$q['day_of_week'];
        if (isset($q['active'])) $filters['active'] = (int)$q['active'];

        $limit = isset($q['limit']) ? (int)$q['limit'] : 100;
        $offset = isset($q['offset']) ? (int)$q['offset'] : 0;

        try {
            $r = $this->service->list($filters, $limit, $offset);
            $res->getBody()->write(json_encode(['ok'=>true,'manager_shifts'=>$r]));
            return $res->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function getById(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $s = $this->service->getById($id);
        if (!$s) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'no encontrado']));
            return $res->withHeader('Content-Type','application/json')->withStatus(404);
        }
        $res->getBody()->write(json_encode(['ok'=>true,'manager_shift'=>$s]));
        return $res->withHeader('Content-Type','application/json');
    }

    public function update(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $body = $req->getParsedBody() ?? [];
        try {
            $shift = $this->service->update($id, $body);
            $res->getBody()->write(json_encode(['ok'=>true,'manager_shift'=>$shift]));
            return $res->withHeader('Content-Type','application/json');
        } catch (\InvalidArgumentException $e) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $res->withHeader('Content-Type','application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function delete(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        try {
            $ok = $this->service->delete($id);
            if (!$ok) {
                $res->getBody()->write(json_encode(['ok'=>false,'error'=>'no encontrado']));
                return $res->withHeader('Content-Type','application/json')->withStatus(404);
            }
            $res->getBody()->write(json_encode(['ok'=>true,'deleted'=>true]));
            return $res->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }
}
