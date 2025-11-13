<?php
require_once __DIR__ . '/../database/BDConnection.php';

class StatisticsRepository {
    // 1. Reservas por día
    public function reservationsPerDay(string $start, string $end, ?int $fieldId = null): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT DATE(start_datetime) AS day, COUNT(*) AS reservations_count
                    FROM reservations
                    WHERE start_datetime BETWEEN :start AND :end";
            if ($fieldId !== null) $sql .= " AND field_id = :field_id";
            $sql .= " GROUP BY day ORDER BY day";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':start', $start);
            $stmt->bindValue(':end', $end);
            if ($fieldId !== null) $stmt->bindValue(':field_id', $fieldId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 2. Duración promedio de reservas
    public function avgReservationDuration(string $start, string $end): float {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, start_datetime, end_datetime))/3600.0 AS avg_hours
                                   FROM reservations
                                   WHERE start_datetime BETWEEN :start AND :end");
            $stmt->execute([':start'=>$start, ':end'=>$end]);
            $v = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($v['avg_hours']) ? (float)$v['avg_hours'] : 0.0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 3. Breakdown por actividad y status
    public function breakdownByActivityAndStatus(string $start, string $end): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT activity_type, status, COUNT(*) AS cnt
                                   FROM reservations
                                   WHERE start_datetime BETWEEN :start AND :end
                                   GROUP BY activity_type, status");
            $stmt->execute([':start'=>$start, ':end'=>$end]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 4. Top campos más usados
    public function topFields(string $start, string $end, int $limit = 10): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT r.field_id, f.name,
                                        SUM(TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime))/60.0 AS hours_reserved
                                   FROM reservations r
                                   JOIN fields f ON f.id = r.field_id
                                   WHERE r.start_datetime BETWEEN :start AND :end
                                   GROUP BY r.field_id
                                   ORDER BY hours_reserved DESC
                                   LIMIT :limit");
            $stmt->bindValue(':start', $start);
            $stmt->bindValue(':end', $end);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 5. Registros de usuarios por día
    public function registrationsPerDay(string $start, string $end, ?int $roleId = null): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
                    FROM users
                    WHERE created_at BETWEEN :start AND :end";
            if ($roleId !== null) { $sql .= " AND role_id = :role_id"; }
            $sql .= " GROUP BY day ORDER BY day";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':start', $start);
            $stmt->bindValue(':end', $end);
            if ($roleId !== null) $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 6. Tasa de cancelaciones
    public function cancellationsRate(string $start, string $end): float {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT SUM(status = 'canceled') AS canceled, COUNT(*) AS total
                                   FROM reservations
                                   WHERE start_datetime BETWEEN :start AND :end");
            $stmt->execute([':start'=>$start, ':end'=>$end]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = (int)($r['total'] ?? 0);
            $canceled = (int)($r['canceled'] ?? 0);
            return $total === 0 ? 0.0 : ($canceled / $total) * 100.0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 7. Utilización de campo (horas reservadas vs disponibles)
    public function fieldUtilization(int $fieldId, string $start, string $end): array {
        $pdo = BDConnection::getConnection();
        try {
            // Calcular horas reservadas
            $stmt = $pdo->prepare("SELECT SUM(TIMESTAMPDIFF(SECOND, start_datetime, end_datetime))/3600.0 AS hours_reserved
                                   FROM reservations
                                   WHERE field_id = :field_id 
                                   AND start_datetime BETWEEN :start AND :end
                                   AND status = 'approved'");
            $stmt->execute([':field_id'=>$fieldId, ':start'=>$start, ':end'=>$end]);
            $hoursReserved = (float)($stmt->fetch(PDO::FETCH_ASSOC)['hours_reserved'] ?? 0);

            // Calcular días en el rango
            $startDate = new DateTime($start);
            $endDate = new DateTime($end);
            $interval = $startDate->diff($endDate);
            $days = $interval->days + 1;

            // Calcular horas disponibles (simplificado: 24h * días)
            // TODO: usar field_operating_hours para cálculo real
            $hoursAvailable = $days * 24;

            $utilization = $hoursAvailable > 0 ? ($hoursReserved / $hoursAvailable) * 100 : 0;

            return [
                'field_id' => $fieldId,
                'hours_reserved' => round($hoursReserved, 2),
                'hours_available' => $hoursAvailable,
                'utilization_percentage' => round($utilization, 2)
            ];
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 8. Top equipos por reservas (CORREGIDO)
    public function topTeams(string $start, string $end, int $limit = 10): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT t.id, t.name, 
                           COUNT(DISTINCT m.id) AS matches_count,
                           SUM(TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime))/60.0 AS hours_used
                    FROM teams t
                    LEFT JOIN matches m ON (m.team1_id = t.id OR m.team2_id = t.id)
                    LEFT JOIN reservations r ON r.id = m.reservation_id 
                                                AND r.start_datetime BETWEEN :start AND :end
                    WHERE m.id IS NOT NULL
                    GROUP BY t.id
                    ORDER BY matches_count DESC, hours_used DESC
                    LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':start', $start);
            $stmt->bindValue(':end', $end);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 9. Top usuarios por reservas (CORREGIDO - usar applicant_id)
    public function topUsers(string $start, string $end, int $limit = 10): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT u.id, 
                                          CONCAT(u.first_name, ' ', u.last_name) AS name, 
                                          u.email, 
                                          COUNT(r.id) AS reservations_count,
                                          SUM(TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime))/60.0 AS hours_reserved
                                   FROM users u
                                   JOIN reservations r ON r.applicant_id = u.id
                                   WHERE r.start_datetime BETWEEN :start AND :end
                                   GROUP BY u.id
                                   ORDER BY reservations_count DESC
                                   LIMIT :limit");
            $stmt->bindValue(':start', $start);
            $stmt->bindValue(':end', $end);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 10. Horarios pico (horas más reservadas)
    public function peakHours(string $start, string $end): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT HOUR(start_datetime) AS hour, COUNT(*) AS reservations_count
                                   FROM reservations
                                   WHERE start_datetime BETWEEN :start AND :end
                                   GROUP BY hour
                                   ORDER BY reservations_count DESC");
            $stmt->execute([':start'=>$start, ':end'=>$end]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // 11. Actividad de equipos (CORREGIDO)
    public function teamActivity(int $teamId, string $start, string $end): array {
        $pdo = BDConnection::getConnection();
        try {
            // Contar matches del equipo
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT m.id) AS matches_count,
                                          SUM(TIMESTAMPDIFF(MINUTE, r.start_datetime, r.end_datetime))/60.0 AS hours_used
                                   FROM matches m
                                   JOIN reservations r ON r.id = m.reservation_id
                                   WHERE (m.team1_id = :team_id OR m.team2_id = :team_id)
                                   AND r.start_datetime BETWEEN :start AND :end");
            $stmt->execute([':team_id'=>$teamId, ':start'=>$start, ':end'=>$end]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $matches = (int)($result['matches_count'] ?? 0);
            $hours = (float)($result['hours_used'] ?? 0);

            return [
                'team_id' => $teamId,
                'matches_count' => $matches,
                'hours_used' => round($hours, 2),
                'total_activity' => $matches
            ];
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}