<?php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/StatisticsService.php';

class StatisticsController {
    private StatisticsService $service;
    
    public function __construct() { 
        $this->service = new StatisticsService(); 
    }

    // 1. Reservas por día
    public function reservationsPerDay(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        try {
            $out = $this->service->reservationsPerDay(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null,
                isset($q['field_id']) ? (int)$q['field_id'] : null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 2. Duración promedio
    public function avgReservationDuration(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        try {
            $out = $this->service->avgReservationDuration(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 3. Breakdown
    public function breakdownByActivityAndStatus(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        try {
            $out = $this->service->breakdownByActivityAndStatus(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 4. Top campos
    public function topFields(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        $limit = isset($q['limit']) ? (int)$q['limit'] : 10;
        try {
            $out = $this->service->topFields(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null,
                $limit
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 5. Registros de usuarios
    public function registrationsPerDay(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        try {
            $out = $this->service->registrationsPerDay(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null,
                isset($q['role_id']) ? (int)$q['role_id'] : null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 6. Tasa de cancelaciones
    public function cancellationsRate(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        try {
            $out = $this->service->cancellationsRate(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 7. Utilización de campo
    public function fieldUtilization(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        if (empty($q['field_id'])) {
            $res->getBody()->write(json_encode(['ok' => false, 'error' => 'field_id es requerido']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        try {
            $out = $this->service->fieldUtilization(
                (int)$q['field_id'],
                $q['start_date'] ?? null,
                $q['end_date'] ?? null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 8. Top equipos
    public function topTeams(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        $limit = isset($q['limit']) ? (int)$q['limit'] : 10;
        try {
            $out = $this->service->topTeams(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null,
                $limit
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 9. Top usuarios
    public function topUsers(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        $limit = isset($q['limit']) ? (int)$q['limit'] : 10;
        try {
            $out = $this->service->topUsers(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null,
                $limit
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 10. Horarios pico
    public function peakHours(Request $req, Response $res): Response {
        $q = $req->getQueryParams();
        try {
            $out = $this->service->peakHours(
                $q['start_date'] ?? null,
                $q['end_date'] ?? null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // 11. Actividad de equipo
    public function teamActivity(Request $req, Response $res, array $args): Response {
        $teamId = (int)$args['team_id'];
        $q = $req->getQueryParams();
        try {
            $out = $this->service->teamActivity(
                $teamId,
                $q['start_date'] ?? null,
                $q['end_date'] ?? null
            );
            $res->getBody()->write(json_encode(['ok' => true, 'stats' => $out]));
            return $res->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $res->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
}