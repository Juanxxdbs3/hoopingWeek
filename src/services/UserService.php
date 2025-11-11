<?php
// src/services/UserService.php
require_once __DIR__ . '/../repositories/UserRepository.php';

class UserService {
    private UserRepository $repo;

    public function __construct(array $dbConfig) {
        $this->repo = new UserRepository($dbConfig);
    }

    public function register(array $data): array {
        // validaciones básicas
        if (empty($data['email']) || empty($data['name']) || empty($data['password'])) {
            throw new InvalidArgumentException("name, email y password requeridos");
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("email inválido");
        }

        // revisar email único
        if ($this->repo->findByEmail($data['email'])) {
            throw new RuntimeException("El email ya está en uso");
        }

        // crear User
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            // passwordHash guardado
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'athlete',
            'state' => $data['state'] ?? 'active',
            'height' => $data['height'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'athlete_state' => $data['athlete_state'] ?? null
        ];
        $user = new User($userData);
        $id = $this->repo->create($user);
        $user->id = $id;
        // devolver representación pública (sin password)
        return $user->toArray();
    }

    public function list(int $limit = 100, int $offset = 0): array {
        return $this->repo->findAll($limit, $offset);
    }

    public function getById(int $id): ?array {
        $u = $this->repo->findById($id);
        return $u ? $u->toArray() : null;
    }
}
