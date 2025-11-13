<?php
// filepath: c:\xampp\htdocs\hooping_week\src\repositories\ReservationRepository.php
require_once __DIR__ . '/../models\Reservation.php';
require_once __DIR__ . '/../database/BDConnection.php';

class ReservationRepository {
    public function create(Reservation $reservation): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO reservations 
                    (field_id, applicant_id, activity_type, start_datetime, end_datetime, 
                     duration_hours, status, priority, request_date, notes)
                    VALUES (:field_id, :applicant_id, :activity_type, :start_datetime, :end_datetime,
                            :duration_hours, :status, :priority, :request_date, :notes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':field_id' => $reservation->field_id,
                ':applicant_id' => $reservation->applicant_id,
                ':activity_type' => $reservation->activity_type,
                ':start_datetime' => $reservation->start_datetime,
                ':end_datetime' => $reservation->end_datetime,
                ':duration_hours' => $reservation->duration_hours,
                ':status' => $reservation->status,
                ':priority' => $reservation->priority,
                ':request_date' => $reservation->request_date,
                ':notes' => $reservation->notes
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findById(int $id): ?Reservation {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new Reservation($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $where = "soft_deleted = 0"; // Excluir eliminados
            $params = [];

            if (!empty($filters['field_id'])) {
                $where .= " AND field_id = :field_id";
                $params[':field_id'] = (int)$filters['field_id'];
            }
            if (!empty($filters['applicant_id'])) {
                $where .= " AND applicant_id = :applicant_id";
                $params[':applicant_id'] = (int)$filters['applicant_id'];
            }
            if (!empty($filters['status'])) {
                $where .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['date_from'])) {
                $where .= " AND start_datetime >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where .= " AND end_datetime <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $sql = "SELECT * FROM reservations WHERE $where ORDER BY start_datetime DESC LIMIT :lim OFFSET :off";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($r) => (new Reservation($r))->toArray(), $rows);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function count(array $filters = []): int {
        $pdo = BDConnection::getConnection();
        try {
            $where = "soft_deleted = 0"; // Excluir eliminados
            $params = [];

            if (!empty($filters['field_id'])) {
                $where .= " AND field_id = :field_id";
                $params[':field_id'] = (int)$filters['field_id'];
            }
            if (!empty($filters['applicant_id'])) {
                $where .= " AND applicant_id = :applicant_id";
                $params[':applicant_id'] = (int)$filters['applicant_id'];
            }
            if (!empty($filters['status'])) {
                $where .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['date_from'])) {
                $where .= " AND start_datetime >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where .= " AND end_datetime <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $sql = "SELECT COUNT(*) FROM reservations WHERE $where";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function update(int $id, array $fields): bool {
        $pdo = BDConnection::getConnection();
        try {
            $allowed = ['field_id', 'applicant_id', 'activity_type', 'start_datetime', 
                        'end_datetime', 'duration_hours', 'status', 'priority', 
                        'approved_by', 'rejection_reason', 'notes'];
            $sets = [];
            $params = [':id' => $id];
            
            foreach ($allowed as $col) {
                if (array_key_exists($col, $fields)) {
                    $sets[] = "`$col` = :$col";
                    $params[":$col"] = $fields[$col];
                }
            }
            
            if (!$sets) return false;
            
            $sql = "UPDATE reservations SET " . implode(', ', $sets) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function updateStatus(int $id, string $status, ?int $approvedBy = null, ?string $rejectionReason = null): bool {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "UPDATE reservations 
                    SET status = :status, approved_by = :approved_by, rejection_reason = :rejection_reason 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':status' => $status,
                ':approved_by' => $approvedBy,
                ':rejection_reason' => $rejectionReason
            ]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function delete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function softDelete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "UPDATE reservations SET soft_deleted = 1 WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function hardDelete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            // Eliminar primero participantes (FK constraint)
            $stmt1 = $pdo->prepare("DELETE FROM reservation_participants WHERE reservation_id = :id");
            $stmt1->execute([':id' => $id]);
            
            // Eliminar reserva
            $stmt2 = $pdo->prepare("DELETE FROM reservations WHERE id = :id");
            $stmt2->execute([':id' => $id]);
            return $stmt2->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // === PARTICIPANTS ===
    
    public function getParticipants(int $reservationId): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT rp.*, u.first_name, u.last_name, u.email 
                    FROM reservation_participants rp
                    JOIN users u ON rp.participant_id = u.id
                    WHERE rp.reservation_id = :rid
                    ORDER BY rp.id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':rid' => $reservationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function addParticipant(int $reservationId, int $participantId, string $participantType = 'individual', ?int $teamId = null): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO reservation_participants (reservation_id, participant_id, participant_type, team_id)
                    VALUES (:rid, :pid, :ptype, :tid)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':rid' => $reservationId,
                ':pid' => $participantId,
                ':ptype' => $participantType,
                ':tid' => $teamId
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function removeParticipant(int $reservationId, int $participantId): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM reservation_participants 
                                   WHERE reservation_id = :rid AND participant_id = :pid");
            $stmt->execute([':rid' => $reservationId, ':pid' => $participantId]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function participantExists(int $reservationId, int $participantId): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM reservation_participants 
                                   WHERE reservation_id = :rid AND participant_id = :pid LIMIT 1");
            $stmt->execute([':rid' => $reservationId, ':pid' => $participantId]);
            return (bool)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function addParticipantsBulk(int $reservationId, array $participants): void {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("INSERT INTO reservation_participants 
                                   (reservation_id, participant_id, participant_type, team_id)
                                   VALUES (:rid, :pid, :ptype, :tid)");
            
            foreach ($participants as $p) {
                $stmt->execute([
                    ':rid' => $reservationId,
                    ':pid' => $p['participant_id'],
                    ':ptype' => $p['participant_type'] ?? 'individual',
                    ':tid' => $p['team_id'] ?? null
                ]);
            }
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // === CHECK OVERLAP ===
    
    public function findOverlapping(int $fieldId, string $startDatetime, string $endDatetime, ?int $excludeReservationId = null): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT id, field_id, applicant_id, activity_type, start_datetime, end_datetime, status
                    FROM reservations
                    WHERE field_id = :field_id
                      AND soft_deleted = 0
                      AND status IN ('pending', 'approved')
                      AND NOT (end_datetime <= :start_new OR start_datetime >= :end_new)";
            
            $params = [
                ':field_id' => $fieldId,
                ':start_new' => $startDatetime,
                ':end_new' => $endDatetime
            ];
            
            if ($excludeReservationId !== null) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeReservationId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function getReservedSlots(int $fieldId, string $date): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT id, TIME(start_datetime) as start_time, TIME(end_datetime) as end_time, status
                    FROM reservations
                    WHERE field_id = :field_id
                      AND DATE(start_datetime) = :date
                      AND soft_deleted = 0
                      AND status IN ('pending', 'approved')
                    ORDER BY start_datetime";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':field_id' => $fieldId, ':date' => $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}