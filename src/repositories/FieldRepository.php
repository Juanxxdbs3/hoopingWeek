<?php
// src/repositories/FieldRepository.php
require_once __DIR__ . '/../models/Field.php';
require_once __DIR__ . '/../database/BDConnection.php';

class FieldRepository {
    public function create(Field $field): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO fields
                (name, location, width_meters, length_meters, surface_type, allowed_sports, people_capacity, state, is_open_to_public, owner_entity, notes)
                VALUES (:name, :location, :width_meters, :length_meters, :surface_type, :allowed_sports, :people_capacity, :state, :is_open_to_public, :owner_entity, :notes)";
            $stmt = $pdo->prepare($sql);
            $allowedJson = $field->allowed_sports ? json_encode($field->allowed_sports) : null;
            $stmt->execute([
                ':name' => $field->name,
                ':location' => $field->location,
                ':width_meters' => $field->width_meters,
                ':length_meters' => $field->length_meters,
                ':surface_type' => $field->surface_type,
                ':allowed_sports' => $allowedJson,
                ':people_capacity' => $field->people_capacity,
                ':state' => $field->state,
                ':is_open_to_public' => $field->is_open_to_public ? 1 : 0,
                ':owner_entity' => $field->owner_entity,
                ':notes' => $field->notes
            ]);
            $id = (int)$pdo->lastInsertId();
            return $id;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findById(int $id): ?Field {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT * FROM fields WHERE id = :id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new Field($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findAll(int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM fields ORDER BY id DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $arr = [];
            foreach ($rows as $r) $arr[] = (new Field($r))->toArray();
            return $arr;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function count(): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM fields");
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findByState(string $state, int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM fields WHERE state = :state ORDER BY id DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':state', $state, PDO::PARAM_STR);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($r) => (new Field($r))->toArray(), $rows);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countByState(string $state): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM fields WHERE state = :state");
            $stmt->execute([':state' => $state]);
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findByLocation(string $location, int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM fields WHERE location LIKE :loc ORDER BY id DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':loc', "%$location%", PDO::PARAM_STR);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($r) => (new Field($r))->toArray(), $rows);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countByLocation(string $location): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM fields WHERE location LIKE :loc");
            $stmt->execute([':loc' => "%$location%"]);
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function search(array $filters, int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $where = "1=1";
            $params = [];

            if (!empty($filters['location'])) {
                $where .= " AND location LIKE :location";
                $params[':location'] = "%" . $filters['location'] . "%";
            }
            if (!empty($filters['state'])) {
                $where .= " AND state = :state";
                $params[':state'] = $filters['state'];
            }
            if (!empty($filters['sport'])) {
                // Buscar en JSON allowed_sports
                $where .= " AND JSON_CONTAINS(allowed_sports, :sport, '$')";
                $params[':sport'] = json_encode($filters['sport']);
            }

            $sql = "SELECT * FROM fields WHERE $where ORDER BY id DESC LIMIT :lim OFFSET :off";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($r) => (new Field($r))->toArray(), $rows);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countSearch(array $filters): int {
        $pdo = BDConnection::getConnection();
        try {
            $where = "1=1";
            $params = [];

            if (!empty($filters['location'])) {
                $where .= " AND location LIKE :location";
                $params[':location'] = "%" . $filters['location'] . "%";
            }
            if (!empty($filters['state'])) {
                $where .= " AND state = :state";
                $params[':state'] = $filters['state'];
            }
            if (!empty($filters['sport'])) {
                $where .= " AND JSON_CONTAINS(allowed_sports, :sport, '$')";
                $params[':sport'] = json_encode($filters['sport']);
            }

            $sql = "SELECT COUNT(*) FROM fields WHERE $where";
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
            $allowed = ['name', 'location', 'width_meters', 'length_meters', 'surface_type', 
                        'allowed_sports', 'people_capacity', 'state', 'is_open_to_public', 
                        'owner_entity', 'notes'];
            $sets = [];
            $params = [':id' => $id];
            
            foreach ($allowed as $col) {
                if (array_key_exists($col, $fields)) {
                    if ($col === 'allowed_sports' && is_array($fields[$col])) {
                        $sets[] = "`$col` = :$col";
                        $params[":$col"] = json_encode($fields[$col]);
                    } elseif ($col === 'is_open_to_public') {
                        $sets[] = "`$col` = :$col";
                        $params[":$col"] = $fields[$col] ? 1 : 0;
                    } else {
                        $sets[] = "`$col` = :$col";
                        $params[":$col"] = $fields[$col];
                    }
                }
            }
            
            if (!$sets) return false;
            
            $sql = "UPDATE fields SET " . implode(', ', $sets) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function updateState(int $id, string $state): bool {
        return $this->update($id, ['state' => $state]);
    }

    public function softDelete(int $id): bool {
        return $this->updateState($id, 'inactive');
    }

    public function hardDelete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM fields WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}
