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
}
