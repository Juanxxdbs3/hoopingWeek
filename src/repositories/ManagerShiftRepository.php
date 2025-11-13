<?php
// src/repositories/ManagerShiftRepository.php
require_once __DIR__ . '/../models/ManagerShift.php';
require_once __DIR__ . '/../database/BDConnection.php';

class ManagerShiftRepository {
    public function create(ManagerShift $s): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO manager_shifts (manager_id, field_id, day_of_week, start_time, end_time, active, note)
                    VALUES (:manager_id, :field_id, :day_of_week, :start_time, :end_time, :active, :note)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':manager_id' => $s->manager_id,
                ':field_id' => $s->field_id,
                ':day_of_week' => $s->day_of_week,
                ':start_time' => $s->start_time,
                ':end_time' => $s->end_time,
                ':active' => $s->active ? 1 : 0,
                ':note' => $s->note
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findById(int $id): ?ManagerShift {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM manager_shifts WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new ManagerShift($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $where = [];
            $params = [];

            if (!empty($filters['manager_id'])) { $where[] = "manager_id = :manager_id"; $params[':manager_id'] = (int)$filters['manager_id']; }
            if (!empty($filters['field_id']))   { $where[] = "field_id = :field_id";     $params[':field_id'] = (int)$filters['field_id']; }
            if (isset($filters['day_of_week'])) { $where[] = "day_of_week = :day_of_week"; $params[':day_of_week'] = (int)$filters['day_of_week']; }
            if (isset($filters['active']))      { $where[] = "active = :active";         $params[':active'] = $filters['active'] ? 1 : 0; }

            $sql = "SELECT * FROM manager_shifts";
            if (count($where) > 0) $sql .= " WHERE " . implode(" AND ", $where);
            $sql .= " ORDER BY field_id, day_of_week, start_time LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $out = [];
            foreach ($rows as $r) $out[] = (new ManagerShift($r))->toArray();
            return $out;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function update(int $id, array $data): bool {
        $pdo = BDConnection::getConnection();
        try {
            $fields = [];
            $params = [':id' => $id];
            $allowed = ['manager_id','field_id','day_of_week','start_time','end_time','active','note'];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = $data[$f];
                }
            }
            if (count($fields) === 0) return false;
            $sql = "UPDATE manager_shifts SET " . implode(", ", $fields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function delete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM manager_shifts WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // chequear unicidad manual (opcional antes de insert)
    public function existsSame(int $manager_id, int $field_id, int $day_of_week): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM manager_shifts WHERE manager_id = :m AND field_id = :f AND day_of_week = :d LIMIT 1");
            $stmt->execute([':m'=>$manager_id, ':f'=>$field_id, ':d'=>$day_of_week]);
            return (bool)$stmt->fetch();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}
