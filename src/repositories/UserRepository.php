<?php
// src/repositories/UserRepository.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../database/BDConnection.php';

class UserRepository {
    protected $dbConfig;

    public function __construct(array $dbConfig) {
        $this->dbConfig = $dbConfig;
    }

    public function create(User $user): int {
        // usa el pool
        $pdo = BDConnection::getConnection();
        try {
            $sql = "INSERT INTO users (email, phone, password_hash, role, name, state, created_at, height, birth_date, athlete_state)
                    VALUES (:email, :phone, :password_hash, :role, :name, :state, :created_at, :height, :birth_date, :athlete_state)";
            $stmt = $pdo->prepare($sql);
            $now = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $stmt->execute([
                ':email' => $user->email,
                ':phone' => $user->phone,
                ':password_hash' => $user->passwordHash,
                ':role' => $user->role,
                ':name' => $user->name,
                ':state' => $user->state,
                ':created_at' => $now,
                ':height' => $user->height,
                ':birth_date' => $user->birth_date,
                ':athlete_state' => $user->athlete_state
            ]);
            $id = (int)$pdo->lastInsertId();
            return $id;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findByEmail(string $email): ?User {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
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
            $stmt = $pdo->prepare("SELECT id, name, email, phone, role, state, created_at, height, birth_date, athlete_state FROM users ORDER BY id DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $arr = [];
            foreach ($rows as $r) $arr[] = $r;
            return $arr;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }

    public function findById(int $id): ?User {
        $pdo = BDConnection::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row ? new User($row) : null;
        } finally {
            BDConnection::releaseConnection($pdo);
        }
    }
}
