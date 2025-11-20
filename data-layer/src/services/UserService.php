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

        if (empty($data['id'])) {
            throw new InvalidArgumentException("id (documento) requerido");
        }
        if (!ctype_digit((string)$data['id'])) {
            throw new InvalidArgumentException("id inválido");
        }
        $userData['id'] = (int)$data['id'];

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
            'id' => (int)$data['id'],
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
        $users = $this->repo->findAll($limit, $offset);
        $total = $this->repo->count(); // Nuevo método
        
        return [
            'data' => $users,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($users)
            ]
        ];
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

    public function listByRole(?int $roleId, string $roleParam, int $limit = 100, int $offset = 0): array {
        $users = $this->repo->findByRole($roleId, $roleParam, $limit, $offset);
        $total = $this->repo->countByRole($roleId, $roleParam);
        
        return [
            'data' => $users,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($users)
            ]
        ];
    }

    public function listByState(?int $stateId, string $stateParam, int $limit = 100, int $offset = 0): array {
        $users = $this->repo->findByState($stateId, $stateParam, $limit, $offset);
        $total = $this->repo->countByState($stateId, $stateParam);
        
        return [
            'data' => $users,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($users)
            ]
        ];
    }

    public function listAthletesByAthleteState(?int $athleteStateId, string $param, int $limit = 100, int $offset = 0): array {
        $users = $this->repo->findAthletesByAthleteState($athleteStateId, $param, $limit, $offset);
        $total = $this->repo->countAthletesByAthleteState($athleteStateId, $param);
        
        return [
            'data' => $users,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($users)
            ]
        ];
    }

    public function authenticate(array $payload): array {
        $identifier = $payload['identifier'] ?? null;
        $password = $payload['password'] ?? null;

        if ($identifier === null || $password === null) {
            return ['ok' => false, 'status' => 400, 'error' => 'identifier and password required'];
        }

        // ✅ Usar el repo ya instanciado en __construct
        $row = null;
        
        // Intentar por id si es numérico
        if (ctype_digit((string)$identifier)) {
            $row = $this->repo->findAuthById((int)$identifier);
        }
        
        // Si no encontró, intentar por email
        if (!$row) {
            $row = $this->repo->findAuthByEmail((string)$identifier);
        }

        if (!$row) {
            return ['ok' => false, 'status' => 401, 'error' => 'Usuario no encontrado'];
        }

        $hash = $row['password_hash'] ?? null;

        // Verificar contraseña
        if ($hash === null) {
            // Usuario sin hash (caso temporal de admin sin password)
            if ($password !== "") {
                return ['ok' => false, 'status' => 401, 'error' => 'Credenciales inválidas'];
            }
        } else {
            if (!password_verify($password, $hash)) {
                return ['ok' => false, 'status' => 401, 'error' => 'Credenciales inválidas'];
            }
        }

        // Quitar campos sensibles
        unset($row['password_hash']);

        return ['ok' => true, 'user' => $row];
    }


}
