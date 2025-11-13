<?php
// filepath: c:\xampp\htdocs\hooping_week\src\repositories\OperatingHoursRepository.php
require_once __DIR__ . '/../database/BDConnection.php';

class OperatingHoursRepository {
    
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

    public function findException(int $fieldId, string $date): ?array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM field_schedule_exceptions WHERE field_id = :fid AND specific_date = :date LIMIT 1");
            $stmt->execute([':fid' => $fieldId, ':date' => $date]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

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

    public function createException(int $fieldId, string $date, string $reason, bool $overridesRegular = false, ?string $openTime = null, ?string $closeTime = null): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("INSERT INTO field_schedule_exceptions 
                                   (field_id, specific_date, reason, overrides_regular, special_start_time, special_end_time) 
                                   VALUES (:fid, :date, :reason, :override, :open, :close)");
            $stmt->execute([
                ':fid' => $fieldId,
                ':date' => $date,
                ':reason' => $reason,
                ':override' => $overridesRegular ? 1 : 0,
                ':open' => $openTime,
                ':close' => $closeTime  // Era special_close_time, ahora special_end_time
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}