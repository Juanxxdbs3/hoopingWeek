<?php
// src/repositories/UserRepository.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../database/BDConnection.php';

class UserRepository {
    protected array $dbConfig;

    public function __construct(array $dbConfig) {
        $this->dbConfig = $dbConfig;
    }

    public function create(User $user): int {
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO users (email, phone, password_hash, role_id, first_name, last_name, state_id, created_at, height, birth_date, athlete_state_id)
                    VALUES (:email, :phone, :password_hash, :role_id, :first_name, :last_name, :state_id, :created_at, :height, :birth_date, :athlete_state_id)";
            $stmt = $pdo->prepare($sql);
            $now = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $stmt->execute([
                ':email' => $user->email,
                ':phone' => $user->phone,
                ':password_hash' => $user->passwordHash,
                ':role_id' => $user->role_id,
                ':first_name' => $user->first_name,
                ':last_name' => $user->last_name,
                ':state_id' => $user->state_id,
                ':created_at' => $now,
                ':height' => $user->height,
                ':birth_date' => $user->birth_date,
                ':athlete_state_id' => $user->athlete_state_id
            ]);
            return (int)$pdo->lastInsertId();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    private function baseSelect(): string {
        return "SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
                       u.role_id, r.name AS role_name,
                       u.state_id, s.name AS state_name,
                       u.athlete_state_id, s2.name AS athlete_state_name,
                       u.created_at, u.height, u.birth_date
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN user_states s ON u.state_id = s.id
                LEFT JOIN user_states s2 ON u.athlete_state_id = s2.id";
    }

    public function findByEmail(string $email): ?User {
        $pdo = BDConnection::getConnection();
        try {
            $sql = $this->baseSelect() . " WHERE u.email = :email LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch();
            return $row ? new User($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findAll(int $limit = 100, int $offset = 0): array {
        $pdo = BDConnection::getConnection();
        try {
            $sql = $this->baseSelect() . " ORDER BY u.id DESC LIMIT :lim OFFSET :off";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $out = [];
            foreach ($rows as $r) { $out[] = (new User($r))->toArray(); }
            return $out;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findByRole(?int $roleId, string $roleParam, int $limit=100, int $offset=0): array {
        $pdo = BDConnection::getConnection();
        try {
            if ($roleId !== null) {
                $sql = $this->baseSelect()." WHERE u.role_id = :rid ORDER BY u.id DESC LIMIT :lim OFFSET :off";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':rid',$roleId,PDO::PARAM_INT);
            } else {
                $sql = $this->baseSelect()." WHERE r.name = :rname ORDER BY u.id DESC LIMIT :lim OFFSET :off";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':rname',$roleParam,PDO::PARAM_STR);
            }
            $stmt->bindValue(':lim',(int)$limit,PDO::PARAM_INT);
            $stmt->bindValue(':off',(int)$offset,PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            return array_map(fn($r)=>(new User($r))->toArray(), $rows);
        } finally { BDConnection::releaseConnection($pdo); }
    }

    public function findByState(?int $stateId, string $stateParam, int $limit=100, int $offset=0): array {
        $pdo = BDConnection::getConnection();
        try {
            if ($stateId !== null) {
                $sql = $this->baseSelect()." WHERE u.state_id = :sid ORDER BY u.id DESC LIMIT :lim OFFSET :off";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':sid',$stateId,PDO::PARAM_INT);
            } else {
                $sql = $this->baseSelect()." WHERE s.name = :sname ORDER BY u.id DESC LIMIT :lim OFFSET :off";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':sname',$stateParam,PDO::PARAM_STR);
            }
            $stmt->bindValue(':lim',(int)$limit,PDO::PARAM_INT);
            $stmt->bindValue(':off',(int)$offset,PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            return array_map(fn($r)=>(new User($r))->toArray(), $rows);
        } finally { BDConnection::releaseConnection($pdo); }
    }

    public function findAthletesByAthleteState(?int $athleteStateId, string $param, int $limit=100, int $offset=0): array {
        $pdo = BDConnection::getConnection();
        try {
            $where = "u.role_id = 1"; // athlete
            if ($athleteStateId !== null) {
                $where .= " AND u.athlete_state_id = :asid";
            } elseif ($param !== 'null') {
                // nombre del estado
                $where .= " AND s2.name = :astname";
            } else {
                $where .= " AND u.athlete_state_id IS NULL";
            }
            $sql = $this->baseSelect()." WHERE $where ORDER BY u.id DESC LIMIT :lim OFFSET :off";
            $stmt = $pdo->prepare($sql);
            if ($athleteStateId !== null) {
                $stmt->bindValue(':asid',$athleteStateId,PDO::PARAM_INT);
            } elseif ($param !== 'null') {
                $stmt->bindValue(':astname',$param,PDO::PARAM_STR);
            }
            $stmt->bindValue(':lim',(int)$limit,PDO::PARAM_INT);
            $stmt->bindValue(':off',(int)$offset,PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            return array_map(fn($r)=>(new User($r))->toArray(), $rows);
        } finally { BDConnection::releaseConnection($pdo); }
    }

    public function findById(int $id): ?User {
        $pdo = BDConnection::getConnection();
        try {
            $sql = $this->baseSelect() . " WHERE u.id = :id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row ? new User($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function update(int $id, array $fields): bool {
        $pdo = BDConnection::getConnection();
        try {
            $allowed = ['first_name','last_name','phone','height','birth_date','role_id','state_id','athlete_state_id'];
            $sets = [];
            $params = [':id' => $id];
            foreach ($allowed as $col) {
                if (array_key_exists($col, $fields)) {
                    $sets[] = "`$col` = :$col";
                    $params[":$col"] = $fields[$col];
                }
            }
            if (!$sets) return false;
            $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function updateStateId(int $id, int $stateId): bool {
        return $this->update($id, ['state_id' => $stateId]);
    }

    public function updateAthleteStateId(int $id, ?int $stateId): bool {
        return $this->update($id, ['athlete_state_id' => $stateId]);
    }

    public function softDelete(int $id): bool {
        // state_id = 2 (inactive) asumiendo catálogo
        return $this->updateStateId($id, 2);
    }

    public function hardDelete(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}
