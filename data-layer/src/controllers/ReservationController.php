<?php
// src/controllers/ReservationController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/ReservationService.php';

class ReservationController {
    private ReservationService $service;

    public function __construct() {
        $this->service = new ReservationService();
    }

    public function createReservation(Request $request, Response $response): Response {
        $body = $request->getParsedBody() ?? [];
        try {
            $res = $this->service->create($body);
            $payload = ['ok' => true, 'reservation' => $res];
            $response->getBody()->write(json_encode($payload));
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

    public function listReservations(Request $request, Response $response): Response {
        $q = $request->getQueryParams();
        $filters = [];
        if (!empty($q['field_id'])) $filters['field_id'] = (int)$q['field_id'];
        if (!empty($q['applicant_id'])) $filters['applicant_id'] = (int)$q['applicant_id'];
        if (!empty($q['status'])) $filters['status'] = $q['status'];
        if (!empty($q['date_from'])) $filters['date_from'] = $q['date_from'];
        if (!empty($q['date_to'])) $filters['date_to'] = $q['date_to'];
        $limit = isset($q['limit']) ? (int)$q['limit'] : 100;
        $offset = isset($q['offset']) ? (int)$q['offset'] : 0;

        try {
            $res = $this->service->list($filters, $limit, $offset);
            $response->getBody()->write(json_encode(['ok' => true, 'reservations' => $res]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function getReservationById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $r = $this->service->getById($id);
            if (!$r) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'no encontrado']));
                return $response->withHeader('Content-Type','application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'reservation' => $r]));
            return $response->withHeader('Content-Type','application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type','application/json')->withStatus(500);
        }
    }

    public function updateReservation(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        
        try {
            $res = $this->service->update($id, $body);
            $response->getBody()->write(json_encode(['ok' => true, 'reservation' => $res]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function changeStatus(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        
        $status = $body['status'] ?? null;
        $approvedBy = isset($body['approved_by']) ? (int)$body['approved_by'] : null;
        $rejectionReason = $body['rejection_reason'] ?? null;
        $approvedAt = $body['approved_at'] ?? null;
        $rejectedAt = $body['rejected_at'] ?? null;
        $cancelledAt = $body['cancelled_at'] ?? null;
        $cancelledBy = isset($body['cancelled_by']) ? (int)$body['cancelled_by'] : null;
        $cancellationReason = $body['cancellation_reason'] ?? null;
        
        try {
            if (!$status) throw new \InvalidArgumentException("status es requerido");
            
            $this->service->changeStatus(
                $id, 
                $status, 
                $approvedBy, 
                $rejectionReason,
                $approvedAt,
                $rejectedAt,
                $cancelledAt,
                $cancelledBy,
                $cancellationReason
            );
            
            $response->getBody()->write(json_encode(['ok' => true, 'message' => 'Estado actualizado']));
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

    public function deleteReservation(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $query = $request->getQueryParams();
        $force = isset($query['force']) && $query['force'] === 'true';
        
        try {
            $this->service->delete($id, $force);
            $msg = $force ? 'Reserva eliminada permanentemente' : 'Reserva eliminada (soft delete)';
            $response->getBody()->write(json_encode(['ok' => true, 'message' => $msg]));
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

    // === PARTICIPANTS ===

    public function getParticipants(Request $request, Response $response, array $args): Response {
        $reservationId = (int)$args['id'];
        
        try {
            $participants = $this->service->getParticipants($reservationId);
            $response->getBody()->write(json_encode(['ok' => true, 'participants' => $participants]));
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

    public function addParticipant(Request $request, Response $response, array $args): Response {
        $reservationId = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        
        try {
            $participantId = isset($body['participant_id']) ? (int)$body['participant_id'] : null;
            if (!$participantId) {
                throw new \InvalidArgumentException("participant_id es requerido");
            }
            
            $participantType = $body['participant_type'] ?? 'individual';
            $teamId = isset($body['team_id']) ? (int)$body['team_id'] : null;
            
            $result = $this->service->addParticipant($reservationId, $participantId, $participantType, $teamId);
            $response->getBody()->write(json_encode(['ok' => true, 'participant' => $result]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function removeParticipant(Request $request, Response $response, array $args): Response {
        $reservationId = (int)$args['reservation_id'];
        $participantId = (int)$args['participant_id'];
        
        try {
            $this->service->removeParticipant($reservationId, $participantId);
            $response->getBody()->write(json_encode(['ok' => true, 'message' => 'Participante removido']));
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

    // === CHECK OVERLAP ===

    public function checkOverlap(Request $request, Response $response): Response {
        $body = (array)$request->getParsedBody();
        
        try {
            $fieldId = isset($body['field_id']) ? (int)$body['field_id'] : null;
            $startDatetime = $body['start_datetime'] ?? null;
            $endDatetime = $body['end_datetime'] ?? null;
            $excludeId = isset($body['exclude_reservation_id']) ? (int)$body['exclude_reservation_id'] : null;

            if (!$fieldId || !$startDatetime || !$endDatetime) {
                throw new \InvalidArgumentException("field_id, start_datetime y end_datetime son requeridos");
            }

            $result = $this->service->checkOverlap($fieldId, $startDatetime, $endDatetime, $excludeId);
            $response->getBody()->write(json_encode(['ok' => true, 'overlap' => $result]));
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
}
