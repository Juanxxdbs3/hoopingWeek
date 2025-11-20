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
            // Validar que se haya enviado id (número documento)
            if (empty($user->id) || !is_numeric($user->id)) {
                throw new InvalidArgumentException("id (documento) requerido y numérico");
            }

            // Verificar unicidad de id
            if ($this->idExists((int)$user->id)) {
                throw new RuntimeException("El id proporcionado ya existe");
            }

            // Verificar email único también
            if ($this->findByEmail($user->email)) {
                throw new RuntimeException("El email ya está en uso");
            }

            $sql = "INSERT INTO users (id, email, phone, password_hash, role_id, first_name, last_name, state_id, created_at, height, birth_date, athlete_state_id)
                    VALUES (:id, :email, :phone, :password_hash, :role_id, :first_name, :last_name, :state_id, :created_at, :height, :birth_date, :athlete_state_id)";
            $stmt = $pdo->prepare($sql);
            $now = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $stmt->execute([
                ':id' => (int)$user->id,
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

            // No usamos lastInsertId, devolvemos el id proporcionado
            return (int)$user->id;
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

    public function count(): int {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countByRole(?int $roleId, string $roleParam): int {
        $pdo = BDConnection::getConnection();
        try {
            if ($roleId !== null) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = :rid");
                $stmt->execute([':rid' => $roleId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE r.name = :rname");
                $stmt->execute([':rname' => $roleParam]);
            }
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countByState(?int $stateId, string $stateParam): int {
        $pdo = BDConnection::getConnection();
        try {
            if ($stateId !== null) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE state_id = :sid");
                $stmt->execute([':sid' => $stateId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN user_states s ON u.state_id = s.id WHERE s.name = :sname");
                $stmt->execute([':sname' => $stateParam]);
            }
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function countAthletesByAthleteState(?int $athleteStateId, string $param): int {
        $pdo = BDConnection::getConnection();
        try {
            $where = "role_id = 1";
            $params = [];
            if ($athleteStateId !== null) {
                $where .= " AND athlete_state_id = :asid";
                $params[':asid'] = $athleteStateId;
            } elseif ($param !== 'null') {
                $sql = "SELECT COUNT(*) FROM users u LEFT JOIN user_states s2 ON u.athlete_state_id = s2.id WHERE u.role_id = 1 AND s2.name = :astname";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':astname' => $param]);
                return (int)$stmt->fetchColumn();
            } else {
                $where .= " AND athlete_state_id IS NULL";
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function idExists(int $id): bool {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch() !== false;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

        /**
     * Retorna fila con password_hash para autenticación (por email)
     * Devuelve array asociativo o null
     */
    public function findAuthByEmail(string $email): ?array {
    $pdo = BDConnection::getConnection();
    try {
        $sql = "SELECT u.*, r.name AS role_name, s.name AS state_name, s2.name AS athlete_state_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN user_states s ON u.state_id = s.id
                LEFT JOIN user_states s2 ON u.athlete_state_id = s2.id
                WHERE u.email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row : null;
    } finally {
        BDConnection::releaseConnection($pdo);
    }
}

public function findAuthById(int $id): ?array {
    $pdo = BDConnection::getConnection();
    try {
        $sql = "SELECT u.*, r.name AS role_name, s.name AS state_name, s2.name AS athlete_state_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN user_states s ON u.state_id = s.id
                LEFT JOIN user_states s2 ON u.athlete_state_id = s2.id
                WHERE u.id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row : null;
    } finally {
        BDConnection::releaseConnection($pdo);
    }
}


}

