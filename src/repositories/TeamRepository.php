<?php
// src/repositories/TeamRepository.php
require_once __DIR__ . '/../models/Team.php';
require_once __DIR__ . '/../database/BDConnection.php';

class TeamRepository {
    public function create(Team $team): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO teams (name, sport, type, trainer_id, locality) 
                    VALUES (:name, :sport, :type, :trainer_id, :locality)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $team->name,
                ':sport' => $team->sport,
                ':type' => $team->type,
                ':trainer_id' => $team->trainer_id,
                ':locality' => $team->locality
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findById(int $id): ?Team {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new Team($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findAll(int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM teams ORDER BY id DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) $out[] = (new Team($r))->toArray();
            return $out;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countAll(): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM teams");
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // util: buscar por nombre (opcional)
    public function findByName(string $name): ?Team {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => $name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new Team($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function update(int $id, array $fields): bool {
        $pdo = BDConnection::getConnection();
        try {
            $allowed = ['name', 'sport', 'type', 'trainer_id', 'locality'];
            $sets = [];
            $params = [':id' => $id];
            
            foreach ($allowed as $col) {
                if (array_key_exists($col, $fields)) {
                    $sets[] = "`$col` = :$col";
                    $params[":$col"] = $fields[$col];
                }
            }
            
            if (!$sets) return false;
            
            $sql = "UPDATE teams SET " . implode(', ', $sets) . " WHERE id = :id";
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
            $stmt = $pdo->prepare("DELETE FROM teams WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findByTrainerId(int $trainerId, int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE trainer_id = :tid ORDER BY id DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':tid', $trainerId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($r) => (new Team($r))->toArray(), $rows);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countByTrainerId(int $trainerId): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE trainer_id = :tid");
            $stmt->execute([':tid' => $trainerId]);
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findBySport(string $sport, int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE sport LIKE :sport ORDER BY id DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':sport', "%$sport%", PDO::PARAM_STR); // Cambiado a LIKE
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($r) => (new Team($r))->toArray(), $rows);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countBySport(string $sport): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE sport LIKE :sport");
            $stmt->execute([':sport' => "%$sport%"]); // Cambiado a LIKE
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    // TeamMembership methods
    public function getMembers(int $teamId): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT tm.*, u.first_name, u.last_name, u.email, u.phone 
                    FROM team_memberships tm
                    JOIN users u ON tm.athlete_id = u.id
                    WHERE tm.team_id = :tid
                    ORDER BY tm.join_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':tid' => $teamId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function addMember(int $teamId, int $athleteId, ?string $joinDate = null, ?string $state = 'active'): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO team_memberships (team_id, athlete_id, join_date, state) VALUES (:tid, :aid, :jd, :st)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tid' => $teamId,
                ':aid' => $athleteId,
                ':jd' => $joinDate ?? date('Y-m-d'),
                ':st' => $state
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function removeMember(int $teamId, int $athleteId): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM team_memberships WHERE team_id = :tid AND athlete_id = :aid");
            $stmt->execute([':tid' => $teamId, ':aid' => $athleteId]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function memberExists(int $teamId, int $athleteId): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM team_memberships WHERE team_id = :tid AND athlete_id = :aid LIMIT 1");
            $stmt->execute([':tid' => $teamId, ':aid' => $athleteId]);
            return (bool)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function getMemberDetails(int $teamId, int $athleteId): ?array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "SELECT tm.*, 
                           u.first_name, u.last_name, u.email, u.phone, 
                           u.height, u.birth_date, u.state_id, us.name as state_name
                    FROM team_memberships tm
                    JOIN users u ON tm.athlete_id = u.id
                    LEFT JOIN user_states us ON u.state_id = us.id
                    WHERE tm.team_id = :tid AND tm.athlete_id = :aid
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':tid' => $teamId, ':aid' => $athleteId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}
