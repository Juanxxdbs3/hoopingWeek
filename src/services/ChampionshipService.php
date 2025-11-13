<?php
// filepath: c:\xampp\htdocs\hooping_week\src\services\ChampionshipService.php
require_once __DIR__ . '/../repositories/ChampionshipRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/TeamRepository.php';

class ChampionshipService {
    private ChampionshipRepository $repo;
    private UserRepository $userRepo;
    private TeamRepository $teamRepo;

    public function __construct() {
        $this->repo = new ChampionshipRepository();
        $config = require __DIR__ . '/../config/config.php';
        $this->userRepo = new UserRepository($config['db']);
        $this->teamRepo = new TeamRepository();
    }

    public function create(array $data): array {
        foreach (['name','organizer_id','sport','start_date','end_date'] as $f) {
            if (empty($data[$f])) {
                throw new InvalidArgumentException("$f es obligatorio");
            }
        }

        // Validar organizer existe
        $u = $this->userRepo->findById((int)$data['organizer_id']);
        if (!$u) {
            throw new RuntimeException("organizer_id no encontrado");
        }

        // Validar rol (trainer=2 o super_admin=4)
        $roleId = is_array($u) ? ($u['role_id'] ?? 0) : ($u->role_id ?? 0);
        if (!in_array((int)$roleId, [2, 4])) {
            throw new RuntimeException("organizer debe ser trainer o super_admin");
        }

        // Validar fechas
        $s = strtotime($data['start_date']);
        $e = strtotime($data['end_date']);
        if ($s === false || $e === false) {
            throw new InvalidArgumentException("Formato de fecha inválido (usar YYYY-MM-DD)");
        }
        if ($s > $e) {
            throw new InvalidArgumentException("start_date debe ser anterior a end_date");
        }

        $champ = new Championship([
            'name' => $data['name'],
            'organizer_id' => (int)$data['organizer_id'],
            'sport' => $data['sport'],
            'start_date' => date('Y-m-d', $s),
            'end_date' => date('Y-m-d', $e),
            'status' => $data['status'] ?? 'planning'
        ]);

        $id = $this->repo->create($champ);
        $champ->id = $id;
        $reloaded = $this->repo->findById($id);
        return $reloaded ? $reloaded->toArray() : $champ->toArray();
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
        $c = $this->repo->findById($id);
        return $c ? $c->toArray() : null;
    }

    public function addTeam(int $champId, int $teamId): bool {
        // Validar championship existe
        $c = $this->repo->findById($champId);
        if (!$c) {
            throw new RuntimeException("Championship no encontrado");
        }

        // Validar team existe
        $t = $this->teamRepo->findById($teamId);
        if (!$t) {
            throw new RuntimeException("Team no encontrado");
        }

        return $this->repo->addTeam($champId, $teamId);
    }

    public function listTeams(int $champId): array {
        // Validar championship existe
        $c = $this->repo->findById($champId);
        if (!$c) {
            throw new RuntimeException("Championship no encontrado");
        }

        return $this->repo->listTeams($champId);
    }

    public function removeTeam(int $champId, int $teamId): bool {
        return $this->repo->removeTeam($champId, $teamId);
    }

    public function update(int $id, array $data): array {
        // Validar que existe
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new RuntimeException("Championship no encontrado");
        }

        // Validar campos obligatorios si se envían
        if (isset($data['organizer_id'])) {
            $u = $this->userRepo->findById((int)$data['organizer_id']);
            if (!$u) {
                throw new RuntimeException("organizer_id no encontrado");
            }
            $roleId = is_array($u) ? ($u['role_id'] ?? 0) : ($u->role_id ?? 0);
            if (!in_array((int)$roleId, [2, 4])) {
                throw new RuntimeException("organizer debe ser trainer o super_admin");
            }
        }

        // Validar fechas si se envían
        $startDate = $data['start_date'] ?? $existing->start_date;
        $endDate = $data['end_date'] ?? $existing->end_date;
        
        $s = strtotime($startDate);
        $e = strtotime($endDate);
        if ($s === false || $e === false) {
            throw new InvalidArgumentException("Formato de fecha inválido (usar YYYY-MM-DD)");
        }
        if ($s > $e) {
            throw new InvalidArgumentException("start_date debe ser anterior a end_date");
        }

        // Validar status válido
        $validStatuses = ['planning', 'active', 'finished', 'cancelled'];
        $status = $data['status'] ?? $existing->status;
        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException("status debe ser: " . implode(', ', $validStatuses));
        }

        $champ = new Championship([
            'id' => $id,
            'name' => $data['name'] ?? $existing->name,
            'organizer_id' => isset($data['organizer_id']) ? (int)$data['organizer_id'] : $existing->organizer_id,
            'sport' => $data['sport'] ?? $existing->sport,
            'start_date' => date('Y-m-d', $s),
            'end_date' => date('Y-m-d', $e),
            'status' => $status,
            'created_at' => $existing->created_at
        ]);

        $this->repo->update($champ);
        $updated = $this->repo->findById($id);
        return $updated ? $updated->toArray() : $champ->toArray();
    }

    public function delete(int $id): bool {
        // Validar que existe
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new RuntimeException("Championship no encontrado");
        }

        return $this->repo->delete($id);
    }
}