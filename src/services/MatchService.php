<?php
// filepath: c:\xampp\htdocs\hooping_week\src\services\MatchService.php
require_once __DIR__ . '/../repositories/MatchRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../repositories/TeamRepository.php';

class MatchService {
    private MatchRepository $repo;
    private ReservationRepository $reservationRepo;
    private TeamRepository $teamRepo;

    public function __construct() {
        $this->repo = new MatchRepository();
        $this->reservationRepo = new ReservationRepository();
        $this->teamRepo = new TeamRepository();
    }

    public function create(array $data): array {
        // Validar campos obligatorios
        foreach (['reservation_id', 'team1_id', 'team2_id'] as $f) {
            if (empty($data[$f])) {
                throw new InvalidArgumentException("$f es obligatorio");
            }
        }

        // Validar reservation existe
        $reservation = $this->reservationRepo->findById((int)$data['reservation_id']);
        if (!$reservation) {
            throw new RuntimeException("reservation_id no encontrado");
        }

        // Validar que no existe match para esa reservation
        $existing = $this->repo->findByReservationId((int)$data['reservation_id']);
        if ($existing) {
            throw new RuntimeException("Ya existe un match vinculado a esa reservation_id");
        }

        // Validar teams existen y son distintos
        $t1 = $this->teamRepo->findById((int)$data['team1_id']);
        $t2 = $this->teamRepo->findById((int)$data['team2_id']);
        if (!$t1) throw new RuntimeException("team1_id no encontrado");
        if (!$t2) throw new RuntimeException("team2_id no encontrado");
        if ((int)$data['team1_id'] === (int)$data['team2_id']) {
            throw new InvalidArgumentException("team1_id y team2_id deben ser distintos");
        }

        $match = new MatchModel([
            'reservation_id' => (int)$data['reservation_id'],
            'team1_id' => (int)$data['team1_id'],
            'team2_id' => (int)$data['team2_id'],
            'is_friendly' => isset($data['is_friendly']) ? (bool)$data['is_friendly'] : true,
            'championship_id' => isset($data['championship_id']) ? (int)$data['championship_id'] : null
        ]);

        $id = $this->repo->create($match);
        $match->id = $id;
        $reloaded = $this->repo->findById($id);
        return $reloaded ? $reloaded->toArray() : $match->toArray();
    }

    public function list(array $filters = [], int $limit = 100, int $offset = 0): array {
        $rows = $this->repo->findAll($filters, $limit, $offset);
        $total = $this->repo->count($filters);
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
        $m = $this->repo->findById($id);
        return $m ? $m->toArray() : null;
    }

    public function getByReservationId(int $reservationId): ?array {
        $m = $this->repo->findByReservationId($reservationId);
        return $m ? $m->toArray() : null;
    }

    public function delete(int $id): bool {
        if (!$this->repo->findById($id)) {
            throw new RuntimeException("Match no encontrado");
        }
        return $this->repo->delete($id);
    }
}