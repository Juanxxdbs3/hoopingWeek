<?php
// src/controllers/TeamController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/TeamService.php';

class TeamController {
    private TeamService $service;

    public function __construct() {
        $this->service = new TeamService();
    }

    public function createTeam(Request $request, Response $response): Response {
        $body = $request->getParsedBody() ?? [];
        try {
            $team = $this->service->create($body);
            $payload = ['ok' => true, 'team' => $team];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
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

    public function listTeams(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        try {
            $res = $this->service->list($limit, $offset);
            $response->getBody()->write(json_encode(['ok' => true, 'teams' => $res]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function getTeamById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $team = $this->service->getById($id);
            if (!$team) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Equipo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'team' => $team]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateTeam(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        
        try {
            $team = $this->service->update($id, $body);
            $response->getBody()->write(json_encode(['ok' => true, 'team' => $team]));
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

    public function deleteTeam(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        
        try {
            $ok = $this->service->delete($id);
            $response->getBody()->write(json_encode(['ok' => true, 'message' => 'Equipo eliminado']));
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

    public function listByTrainer(Request $request, Response $response, array $args): Response {
        $trainerId = (int)$args['trainer_id'];
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->service->listByTrainerId($trainerId, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'teams' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listBySport(Request $request, Response $response, array $args): Response {
        $sport = $args['sport'];
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->service->listBySport($sport, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'teams' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getMembers(Request $request, Response $response, array $args): Response {
        $teamId = (int)$args['id'];
        
        try {
            $members = $this->service->getMembers($teamId);
            $response->getBody()->write(json_encode(['ok' => true, 'members' => $members]));
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

    public function addMember(Request $request, Response $response, array $args): Response {
        $teamId = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        $athleteId = isset($body['athlete_id']) ? (int)$body['athlete_id'] : null;
        $joinDate = $body['join_date'] ?? null;
        
        try {
            if (!$athleteId) throw new InvalidArgumentException("athlete_id requerido");
            
            $result = $this->service->addMember($teamId, $athleteId, $joinDate);
            $response->getBody()->write(json_encode(['ok' => true, 'membership' => $result]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function removeMember(Request $request, Response $response, array $args): Response {
        $teamId = (int)$args['team_id'];
        $athleteId = (int)$args['athlete_id'];
        
        try {
            $this->service->removeMember($teamId, $athleteId);
            $response->getBody()->write(json_encode(['ok' => true, 'message' => 'Atleta removido del equipo']));
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

    public function getMemberDetails(Request $request, Response $response, array $args): Response {
        $teamId = (int)$args['team_id'];
        $athleteId = (int)$args['athlete_id'];
        
        try {
            $member = $this->service->getMemberDetails($teamId, $athleteId);
            if (!$member) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Miembro no encontrado en este equipo']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'member' => $member]));
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
}
