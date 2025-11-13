<?php
// src/repositories/ChampionshipRepository.php
require_once __DIR__ . '/../models/Championship.php';
require_once __DIR__ . '/../database/BDConnection.php';

class ChampionshipRepository {
    public function create(Championship $c): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO championships (name, organizer_id, sport, start_date, end_date, status)
                    VALUES (:name, :organizer_id, :sport, :start_date, :end_date, :status)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $c->name,
                ':organizer_id' => $c->organizer_id,
                ':sport' => $c->sport,
                ':start_date' => $c->start_date,
                ':end_date' => $c->end_date,
                ':status' => $c->status
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findById(int $id): ?Championship {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM championships WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new Championship($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findAll(int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM championships ORDER BY start_date DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) $out[] = (new Championship($r))->toArray();
            return $out;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countAll(): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM championships");
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // championship_teams operations
    public function addTeam(int $championshipId, int $teamId): bool {
        $pdo = BDConnection::getConnection();
        try {
            // evitar duplicados
            $stmt = $pdo->prepare("SELECT 1 FROM championship_teams WHERE championship_id = :cid AND team_id = :tid LIMIT 1");
            $stmt->execute([':cid'=>$championshipId, ':tid'=>$teamId]);
            if ($stmt->fetch()) return false;
            $ins = $pdo->prepare("INSERT INTO championship_teams (championship_id, team_id) VALUES (:cid, :tid)");
            $ins->execute([':cid'=>$championshipId, ':tid'=>$teamId]);
            return true;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function listTeams(int $championshipId): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT t.* FROM championship_teams ct JOIN teams t ON ct.team_id = t.id WHERE ct.championship_id = :cid");
            $stmt->execute([':cid'=>$championshipId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function removeTeam(int $championshipId, int $teamId): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM championship_teams WHERE championship_id = :cid AND team_id = :tid");
            $stmt->execute([':cid'=>$championshipId, ':tid'=>$teamId]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function update(Championship $c): bool {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "UPDATE championships SET 
                    name = :name, 
                    organizer_id = :organizer_id, 
                    sport = :sport, 
                    start_date = :start_date, 
                    end_date = :end_date, 
                    status = :status 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $c->id,
                ':name' => $c->name,
                ':organizer_id' => $c->organizer_id,
                ':sport' => $c->sport,
                ':start_date' => $c->start_date,
                ':end_date' => $c->end_date,
                ':status' => $c->status
            ]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function delete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM championships WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}
