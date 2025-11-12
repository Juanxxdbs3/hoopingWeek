<?php
// src/services/UserService.php
require_once __DIR__ . '/../repositories/UserRepository.php';

class UserService {
    private UserRepository $repo;

    // IDs por defecto (ajusta si cambian)
    private int $defaultRoleId = 1;   // athlete
    private int $defaultStateId = 1;  // active

    public function __construct(array $dbConfig) {
        $this->repo = new UserRepository($dbConfig);
    }

    public function register(array $data): array {
        // validaciones básicas
        if (empty($data['email']) || empty($data['first_name']) || empty($data['password'])) {
            throw new InvalidArgumentException("first_name, email y password requeridos");
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
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            // passwordHash guardado
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role_id' => isset($data['role_id']) ? (int)$data['role_id'] : $this->defaultRoleId,
            'state_id' => isset($data['state_id']) ? (int)$data['state_id'] : $this->defaultStateId,
            'height' => $data['height'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'athlete_state_id' => isset($data['athlete_state_id']) ? (int)$data['athlete_state_id'] : null
        ];
        $user = new User($userData);
        $id = $this->repo->create($user);
        $user->id = $id;
        // recargar para incluir nombres de catálogos
        $reloaded = $this->repo->findById($id);
        return $reloaded ? $reloaded->toArray() : $user->toArray();
    }

    public function list(int $limit = 100, int $offset = 0): array {
        return $this->repo->findAll($limit, $offset);
    }

    public function getById(int $id): ?array {
        $u = $this->repo->findById($id);
        return $u ? $u->toArray() : null;
    }

    public function update(int $id, array $data): array {
        // No se permite cambiar email aquí
        unset($data['email']);
        $ok = $this->repo->update($id, $data);
        if (!$ok) throw new RuntimeException("No se pudo actualizar (o sin cambios)");
        $u = $this->repo->findById($id);
        return $u ? $u->toArray() : [];
    }

    public function changeState(int $id, int $stateId): bool {
        // Aceptar todos los ids existentes (ajusta lista si quieres restringir)
        if (!in_array($stateId, [1,2,3,4,5], true)) {
            throw new InvalidArgumentException("state_id inválido");
        }
        return $this->repo->updateStateId($id, $stateId);
    }

    public function changeAthleteState(int $id, ?int $athleteStateId): bool {
        if (!in_array($athleteStateId, [null,4,5], true)) {
            throw new InvalidArgumentException("athlete_state_id inválido");
        }
        $u = $this->repo->findById($id);
        if (!$u) throw new RuntimeException("Usuario no encontrado");
        if ($u->role_id !== 1) {
            throw new RuntimeException("Solo aplicable a athletes");
        }
        return $this->repo->updateAthleteStateId($id, $athleteStateId);
    }

    public function delete(int $id, bool $force = false): bool {
        return $force ? $this->repo->hardDelete($id) : $this->repo->softDelete($id);
    }

    public function listByRole(?int $roleId, string $roleParam): array {
        return $this->repo->findByRole($roleId, $roleParam);
    }

    public function listByState(?int $stateId, string $stateParam): array {
        return $this->repo->findByState($stateId, $stateParam);
    }

    public function listAthletesByAthleteState(?int $athleteStateId, string $param): array {
        return $this->repo->findAthletesByAthleteState($athleteStateId, $param);
    }
}
