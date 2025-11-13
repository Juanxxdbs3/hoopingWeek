<?php
// src/controllers/ChampionshipController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/ChampionshipService.php';

class ChampionshipController {
    private ChampionshipService $service;
    public function __construct() { $this->service = new ChampionshipService(); }

    public function create(Request $req, Response $res): Response {
        $body = $req->getParsedBody() ?? [];
        try {
            $c = $this->service->create($body);
            $res->getBody()->write(json_encode(['ok'=>true,'championship'=>$c]));
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
        $limit = isset($q['limit']) ? (int)$q['limit'] : 100;
        $offset = isset($q['offset']) ? (int)$q['offset'] : 0;
        $r = $this->service->list($limit, $offset);
        $res->getBody()->write(json_encode(['ok'=>true,'championships'=>$r]));
        return $res->withHeader('Content-Type','application/json');
    }

    public function getById(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $c = $this->service->getById($id);
        if (!$c) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'no encontrado']));
            return $res->withHeader('Content-Type','application/json')->withStatus(404);
        }
        $res->getBody()->write(json_encode(['ok'=>true,'championship'=>$c]));
        return $res->withHeader('Content-Type','application/json');
    }

    public function addTeam(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $body = $req->getParsedBody() ?? [];
        $teamId = isset($body['team_id']) ? (int)$body['team_id'] : null;
        if (!$teamId) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'team_id requerido']));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        try {
            $ok = $this->service->addTeam($id, $teamId);
            if (!$ok) {
                $res->getBody()->write(json_encode(['ok'=>false,'error'=>'ya existe o no agregado']));
                return $res->withHeader('Content-Type','application/json')->withStatus(409);
            }
            $res->getBody()->write(json_encode(['ok'=>true,'added'=>true]));
            return $res->withHeader('Content-Type','application/json')->withStatus(201);
        } catch (\RuntimeException $e) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $res->withHeader('Content-Type','application/json')->withStatus(409);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function listTeams(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        try {
            $teams = $this->service->listTeams($id);
            $res->getBody()->write(json_encode(['ok'=>true,'teams'=>$teams]));
            return $res->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function removeTeam(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $teamId = (int)$args['team_id'];
        try {
            $ok = $this->service->removeTeam($id, $teamId);
            if (!$ok) {
                $res->getBody()->write(json_encode(['ok'=>false,'error'=>'no encontrado o no eliminado']));
                return $res->withHeader('Content-Type','application/json')->withStatus(404);
            }
            $res->getBody()->write(json_encode(['ok'=>true,'removed'=>true]));
            return $res->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function update(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $body = $req->getParsedBody() ?? [];
        try {
            $c = $this->service->update($id, $body);
            $res->getBody()->write(json_encode(['ok'=>true,'championship'=>$c]));
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
            $this->service->delete($id);
            $res->getBody()->write(json_encode(['ok'=>true,'message'=>'Championship eliminado']));
            return $res->withHeader('Content-Type','application/json');
        } catch (\RuntimeException $e) {
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>$e->getMessage()]));
            return $res->withHeader('Content-Type','application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok'=>false,'error'=>'error interno']));
            return $res->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }
}
