<?php
// filepath: c:\xampp\htdocs\hooping_week\src\repositories\MatchRepository.php
require_once __DIR__ . '/../models/Match.php';
require_once __DIR__ . '/../database/BDConnection.php';

class MatchRepository {
    public function create(MatchModel $m): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO matches (reservation_id, team1_id, team2_id, is_friendly, championship_id)
                    VALUES (:reservation_id, :team1_id, :team2_id, :is_friendly, :championship_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':reservation_id' => $m->reservation_id,
                ':team1_id' => $m->team1_id,
                ':team2_id' => $m->team2_id,
                ':is_friendly' => $m->is_friendly ? 1 : 0,
                ':championship_id' => $m->championship_id
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findById(int $id): ?MatchModel {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new MatchModel($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $where = [];
            $params = [];

            if (!empty($filters['reservation_id'])) {
                $where[] = "reservation_id = :reservation_id";
                $params[':reservation_id'] = (int)$filters['reservation_id'];
            }
            if (!empty($filters['team_id'])) {
                $where[] = "(team1_id = :team_id OR team2_id = :team_id)";
                $params[':team_id'] = (int)$filters['team_id'];
            }
            if (!empty($filters['championship_id'])) {
                $where[] = "championship_id = :championship_id";
                $params[':championship_id'] = (int)$filters['championship_id'];
            }
            if (isset($filters['is_friendly'])) {
                $where[] = "is_friendly = :is_friendly";
                $params[':is_friendly'] = (bool)$filters['is_friendly'] ? 1 : 0;
            }

            $sql = "SELECT * FROM matches";
            if (count($where) > 0) $sql .= " WHERE " . implode(" AND ", $where);
            $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(fn($r) => (new MatchModel($r))->toArray(), $rows);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findByReservationId(int $reservationId): ?MatchModel {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM matches WHERE reservation_id = :rid LIMIT 1");
            $stmt->execute([':rid' => $reservationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new MatchModel($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function count(array $filters = []): int {
        $pdo = BDConnection::getConnection();
        try {
            $where = [];
            $params = [];

            if (!empty($filters['team_id'])) {
                $where[] = "(team1_id = :team_id OR team2_id = :team_id)";
                $params[':team_id'] = (int)$filters['team_id'];
            }
            if (!empty($filters['championship_id'])) {
                $where[] = "championship_id = :championship_id";
                $params[':championship_id'] = (int)$filters['championship_id'];
            }
            if (isset($filters['is_friendly'])) {
                $where[] = "is_friendly = :is_friendly";
                $params[':is_friendly'] = (bool)$filters['is_friendly'] ? 1 : 0;
            }

            $sql = "SELECT COUNT(*) FROM matches";
            if (count($where) > 0) $sql .= " WHERE " . implode(" AND ", $where);

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function delete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM matches WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}