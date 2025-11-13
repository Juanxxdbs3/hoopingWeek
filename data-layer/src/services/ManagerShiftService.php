<?php
require_once __DIR__ . '/../repositories/ManagerShiftRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/FieldRepository.php';

class ManagerShiftService {
    private ManagerShiftRepository $repo;
    private UserRepository $userRepo;
    private FieldRepository $fieldRepo;

    public function __construct() {
        $this->repo = new ManagerShiftRepository();
        $config = require __DIR__ . '/../config/config.php';
        $this->userRepo = new UserRepository($config['db']);
        $this->fieldRepo = new FieldRepository();
    }

    public function create(array $data): array {
        // Validaciones mínimas
        foreach (['manager_id','field_id','day_of_week','start_time','end_time'] as $f) {
            if (!isset($data[$f]) && $data[$f] !== 0) {
                throw new InvalidArgumentException("$f es obligatorio");
            }
        }

        $mId = (int)$data['manager_id'];
        $fId = (int)$data['field_id'];
        $dow = (int)$data['day_of_week'];

        if ($dow < 0 || $dow > 6) {
            throw new InvalidArgumentException("day_of_week debe estar entre 0 (domingo) y 6 (sábado)");
        }

        // Validar manager existe y tiene rol field_manager (role_id = 3)
        $manager = $this->userRepo->findById($mId);
        if (!$manager) {
            throw new RuntimeException("manager_id no encontrado");
        }

        // Validar role_id = 3 (field_manager)
        $roleId = is_array($manager) ? ($manager['role_id'] ?? 0) : ($manager->role_id ?? 0);
        if ((int)$roleId !== 3) {
            throw new RuntimeException("manager_id debe tener rol field_manager");
        }

        // Validar field existe
        $field = $this->fieldRepo->findById($fId);
        if (!$field) {
            throw new RuntimeException("field_id no encontrado");
        }

        // Validar start < end (horas)
        $st = strtotime($data['start_time']);
        $en = strtotime($data['end_time']);
        if ($st === false || $en === false) {
            throw new InvalidArgumentException("start_time o end_time inválidos (usar HH:MM:SS)");
        }
        if ($st >= $en) {
            throw new InvalidArgumentException("start_time debe ser anterior a end_time");
        }

        // Validar unicidad (manager + field + día)
        if ($this->repo->existsSame($mId, $fId, $dow)) {
            throw new RuntimeException("Ya existe un turno para ese manager/field/día");
        }

        $shift = new ManagerShift([
            'manager_id' => $mId,
            'field_id' => $fId,
            'day_of_week' => $dow,
            'start_time' => date('H:i:s', $st),
            'end_time' => date('H:i:s', $en),
            'active' => isset($data['active']) ? (bool)$data['active'] : true,
            'note' => $data['note'] ?? null
        ]);

        $id = $this->repo->create($shift);
        $shift->id = $id;
        $reloaded = $this->repo->findById($id);
        return $reloaded ? $reloaded->toArray() : $shift->toArray();
    }

    public function list(array $filters = [], int $limit = 100, int $offset = 0): array {
        $rows = $this->repo->findAll($filters, $limit, $offset);
        return [
            'data' => $rows,
            'meta' => [
                'count' => count($rows),
                'limit' => $limit,
                'offset' => $offset,
                'timestamp' => date('c')
            ]
        ];
    }

    public function getById(int $id): ?array {
        $s = $this->repo->findById($id);
        return $s ? $s->toArray() : null;
    }

    public function update(int $id, array $data): array {
        // Validar que existe
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new RuntimeException("Manager shift no encontrado");
        }

        // Validar horarios si vienen
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $st = strtotime($data['start_time']);
            $en = strtotime($data['end_time']);
            if ($st === false || $en === false) {
                throw new InvalidArgumentException("Formato de hora inválido");
            }
            if ($st >= $en) {
                throw new InvalidArgumentException("start_time debe ser anterior a end_time");
            }
            $data['start_time'] = date('H:i:s', $st);
            $data['end_time'] = date('H:i:s', $en);
        }

        // Validar day_of_week si viene
        if (isset($data['day_of_week'])) {
            $dow = (int)$data['day_of_week'];
            if ($dow < 0 || $dow > 6) {
                throw new InvalidArgumentException("day_of_week debe estar entre 0 y 6");
            }
        }

        // Validar active si viene
        if (isset($data['active'])) {
            $data['active'] = (bool)$data['active'] ? 1 : 0;
        }

        $this->repo->update($id, $data);
        $updated = $this->repo->findById($id);
        return $updated ? $updated->toArray() : [];
    }

    public function delete(int $id): bool {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new RuntimeException("Manager shift no encontrado");
        }
        return $this->repo->delete($id);
    }
}