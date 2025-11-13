<?php
// src/services/TeamService.php
require_once __DIR__ . '/../repositories/TeamRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php'; // para validar trainer

class TeamService {
    private TeamRepository $repo;
    private UserRepository $userRepo;

    public function __construct() {
        $this->repo = new TeamRepository();
        $this->userRepo = new UserRepository([]); // repo usa BDConnection internamente
    }

    public function create(array $data): array {
        if (empty($data['name'])) {
            throw new InvalidArgumentException("name es obligatorio");
        }

        // validar trainer si viene
        $trainerId = isset($data['trainer_id']) ? (int)$data['trainer_id'] : null;
        if ($trainerId !== null) {
            $trainer = $this->userRepo->findById($trainerId);
            if (!$trainer) throw new RuntimeException("trainer_id no encontrado");
            
            // Validar role_id = 2 (trainer)
            if ($trainer->role_id !== 2) {
                throw new RuntimeException("El usuario debe tener rol 'trainer' (role_id=2)");
            }
        }

        // evitar duplicados por nombre (opcional)
        $exists = $this->repo->findByName($data['name']);
        if ($exists) throw new RuntimeException("Ya existe un equipo con ese nombre");

        $team = new Team([
            'name' => $data['name'],
            'sport' => isset($data['sport']) ? strtolower(trim($data['sport'])) : null,
            'type' => $data['type'] ?? null,
            'trainer_id' => $trainerId,
            'locality' => $data['locality'] ?? null
        ]);

        $id = $this->repo->create($team);
        $reloaded = $this->repo->findById($id);
        return $reloaded ? $reloaded->toArray() : ['id' => $id];
    }

    public function list(int $limit = 100, int $offset = 0): array {
        $rows = $this->repo->findAll($limit, $offset);
        $total = $this->repo->countAll();
        return [
            'data' => $rows,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($rows),
                'timestamp' => date('c')
            ]
        ];
    }

    public function getById(int $id): ?array {
        $t = $this->repo->findById($id);
        return $t ? $t->toArray() : null;
    }

    public function update(int $id, array $data): array {
        // validar trainer si viene
        if (isset($data['trainer_id'])) {
            $trainerId = (int)$data['trainer_id'];
            $trainer = $this->userRepo->findById($trainerId);
            if (!$trainer) throw new RuntimeException("trainer_id no encontrado");
            if ($trainer->role_id !== 2) {
                throw new RuntimeException("El usuario debe tener rol 'trainer' (role_id=2)");
            }
        }

        $ok = $this->repo->update($id, $data);
        if (!$ok) throw new RuntimeException("No se pudo actualizar (o sin cambios)");
        
        $t = $this->repo->findById($id);
        return $t ? $t->toArray() : [];
    }

    public function delete(int $id): bool {
        // Verificar que existe
        if (!$this->repo->findById($id)) {
            throw new RuntimeException("Equipo no encontrado");
        }
        return $this->repo->delete($id);
    }

    public function listByTrainerId(int $trainerId, int $limit = 100, int $offset = 0): array {
        $teams = $this->repo->findByTrainerId($trainerId, $limit, $offset);
        $total = $this->repo->countByTrainerId($trainerId);
        
        return [
            'data' => $teams,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($teams)
            ]
        ];
    }

    public function listBySport(string $sport, int $limit = 100, int $offset = 0): array {
        // Normalizar deporte (lowercase, trim)
        $sport = strtolower(trim($sport));
        
        $teams = $this->repo->findBySport($sport, $limit, $offset);
        $total = $this->repo->countBySport($sport);
        
        return [
            'data' => $teams,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($teams)
            ]
        ];
    }

    public function getMembers(int $teamId): array {
        // Verificar que el equipo existe
        if (!$this->repo->findById($teamId)) {
            throw new RuntimeException("Equipo no encontrado");
        }
        return $this->repo->getMembers($teamId);
    }

    public function addMember(int $teamId, int $athleteId, ?string $joinDate = null): array {
        // Validar que equipo existe
        if (!$this->repo->findById($teamId)) {
            throw new RuntimeException("Equipo no encontrado");
        }

        // Validar que el usuario es atleta (role_id = 1)
        $athlete = $this->userRepo->findById($athleteId);
        if (!$athlete) throw new RuntimeException("Atleta no encontrado");
        if ($athlete->role_id !== 1) {
            throw new RuntimeException("El usuario debe tener rol 'athlete' (role_id=1)");
        }

        // Verificar que no existe ya
        if ($this->repo->memberExists($teamId, $athleteId)) {
            throw new RuntimeException("El atleta ya pertenece al equipo");
        }

        $id = $this->repo->addMember($teamId, $athleteId, $joinDate);
        return ['id' => $id, 'team_id' => $teamId, 'athlete_id' => $athleteId];
    }

    public function removeMember(int $teamId, int $athleteId): bool {
        if (!$this->repo->memberExists($teamId, $athleteId)) {
            throw new RuntimeException("El atleta no pertenece al equipo");
        }
        return $this->repo->removeMember($teamId, $athleteId);
    }

    public function getMemberDetails(int $teamId, int $athleteId): ?array {
        if (!$this->repo->findById($teamId)) {
            throw new RuntimeException("Equipo no encontrado");
        }
        return $this->repo->getMemberDetails($teamId, $athleteId);
    }
}
