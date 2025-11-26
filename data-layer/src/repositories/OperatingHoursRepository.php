<?php
// filepath: c:\xampp\htdocs\hooping_week\data-layer\src\repositories\OperatingHoursRepository.php
require_once __DIR__ . '/../database/BDConnection.php';

class OperatingHoursRepository {
    
    /**
     * Busca horario regular de un campo para un día específico
     */
    public function findByFieldAndDay(int $fieldId, int $dayOfWeek): ?array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM field_operating_hours WHERE field_id = :fid AND day_of_week = :dow LIMIT 1");
            $stmt->execute([':fid' => $fieldId, ':dow' => $dayOfWeek]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Obtiene todos los horarios regulares de un campo
     */
    public function findAllByField(int $fieldId): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM field_operating_hours WHERE field_id = :fid ORDER BY day_of_week");
            $stmt->execute([':fid' => $fieldId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Busca si existe una excepción para un campo en una fecha específica
     */
    public function findException(int $fieldId, string $date): ?array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT id, field_id, specific_date as date, reason, overrides_regular, 
                           special_start_time as open_time, special_end_time as close_time
                    FROM field_schedule_exceptions 
                    WHERE field_id = :fid AND specific_date = :date 
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':fid' => $fieldId, ':date' => $date]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Obtiene excepciones de un campo en un rango de fechas
     */
    public function getExceptionsByRange(int $fieldId, string $startDate, string $endDate): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT id, field_id, specific_date as date, reason, overrides_regular, 
                           special_start_time as open_time, special_end_time as close_time
                    FROM field_schedule_exceptions
                    WHERE field_id = :field_id 
                    AND specific_date BETWEEN :start_date AND :end_date
                    ORDER BY specific_date ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':field_id' => $fieldId,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Verifica si una fecha es festivo
     */
    public function isHoliday(string $date): ?array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM holidays WHERE holiday_date = :date LIMIT 1");
            $stmt->execute([':date' => $date]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Crea horario regular para un campo
     */
    public function create(int $fieldId, int $dayOfWeek, string $openTime, string $closeTime): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("INSERT INTO field_operating_hours (field_id, day_of_week, start_time, end_time) 
                                   VALUES (:fid, :dow, :open, :close)");
            $stmt->execute([
                ':fid' => $fieldId,
                ':dow' => $dayOfWeek,
                ':open' => $openTime,
                ':close' => $closeTime
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Crea una excepción de horario para un campo
     */
    public function createException(int $fieldId, string $date, string $reason, bool $overridesRegular = false, ?string $openTime = null, ?string $closeTime = null): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO field_schedule_exceptions 
                    (field_id, specific_date, reason, overrides_regular, special_start_time, special_end_time) 
                    VALUES (:fid, :date, :reason, :override, :open, :close)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fid' => $fieldId,
                ':date' => $date,
                ':reason' => $reason,
                ':override' => $overridesRegular ? 1 : 0,
                ':open' => $openTime,
                ':close' => $closeTime
            ]);
            
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Actualiza una excepción existente
     */
    public function updateException(int $id, array $data): bool {
        $pdo = BDConnection::getConnection();
        try {
            $fields = [];
            $params = [':id' => $id];
            
            if (isset($data['reason'])) {
                $fields[] = 'reason = :reason';
                $params[':reason'] = $data['reason'];
            }
            if (isset($data['overrides_regular'])) {
                $fields[] = 'overrides_regular = :override';
                $params[':override'] = $data['overrides_regular'] ? 1 : 0;
            }
            if (isset($data['open_time'])) {
                $fields[] = 'special_start_time = :open';
                $params[':open'] = $data['open_time'];
            }
            if (isset($data['close_time'])) {
                $fields[] = 'special_end_time = :close';
                $params[':close'] = $data['close_time'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE field_schedule_exceptions SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Elimina una excepción (soft delete si existe columna, sino hard delete)
     */
    public function deleteException(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM field_schedule_exceptions WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    /**
     * Elimina horario regular de un día
     */
    public function deleteByFieldAndDay(int $fieldId, int $dayOfWeek): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM field_operating_hours WHERE field_id = :fid AND day_of_week = :dow");
            $stmt->execute([':fid' => $fieldId, ':dow' => $dayOfWeek]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}
