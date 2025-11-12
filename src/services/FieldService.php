
<?php
// src/services/FieldService.php
require_once __DIR__ . '/../repositories/FieldRepository.php';

class FieldService {
    private FieldRepository $repo;

    public function __construct() {
        $this->repo = new FieldRepository();
    }

    public function create(array $data): array {
        // Validaciones básicas
        if (empty($data['name']) || empty($data['location'])) {
            throw new InvalidArgumentException("name y location son obligatorios");
        }

        // normalizar allowed_sports si viene como string
        if (isset($data['allowed_sports']) && is_string($data['allowed_sports'])) {
            $data['allowed_sports'] = json_decode($data['allowed_sports'], true);
        }

        $field = new Field([
            'name' => $data['name'],
            'location' => $data['location'],
            'width_meters' => $data['width_meters'] ?? null,
            'length_meters' => $data['length_meters'] ?? null,
            'surface_type' => $data['surface_type'] ?? null,
            'allowed_sports' => $data['allowed_sports'] ?? null,
            'people_capacity' => $data['people_capacity'] ?? null,
            'state' => $data['state'] ?? 'active',
            'is_open_to_public' => $data['is_open_to_public'] ?? true,
            'owner_entity' => $data['owner_entity'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);

        $id = $this->repo->create($field);
        
        // Recargar desde BD para obtener created_at generado
        $reloaded = $this->repo->findById($id);
        return $reloaded ? $reloaded->toArray() : ['id' => $id];
    }

    public function list(int $limit = 100, int $offset = 0): array {
        return $this->repo->findAll($limit, $offset);
    }

    public function getById(int $id): ?array {
        $f = $this->repo->findById($id);
        return $f ? $f->toArray() : null;
    }
}
