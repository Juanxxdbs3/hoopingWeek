<?php
// src/services/FieldService.php
require_once __DIR__ . '/../repositories/FieldRepository.php';

class FieldService {
    private FieldRepository $repo;
    private array $validStates = ['active', 'maintenance', 'inactive'];

    public function __construct() {
        $this->repo = new FieldRepository();
    }

    private function validateState(string $state): void {
        if (!in_array($state, $this->validStates, true)) {
            throw new InvalidArgumentException("Estado inválido. Permitidos: " . implode(', ', $this->validStates));
        }
    }

    public function create(array $data): array {
        // Validaciones básicas
        if (empty($data['name']) || empty($data['location'])) {
            throw new InvalidArgumentException("name y location son obligatorios");
        }

        // Validar estado si viene
        if (isset($data['state'])) {
            $this->validateState($data['state']);
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
        $fields = $this->repo->findAll($limit, $offset);
        $total = $this->repo->count();
        
        return [
            'data' => $fields,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($fields)
            ]
        ];
    }

    public function getById(int $id): ?array {
        $f = $this->repo->findById($id);
        return $f ? $f->toArray() : null;
    }

    public function listByState(string $state, int $limit = 100, int $offset = 0): array {
        // Validar estado antes de consultar
        $this->validateState($state);
        
        $fields = $this->repo->findByState($state, $limit, $offset);
        $total = $this->repo->countByState($state);
        
        return [
            'data' => $fields,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($fields)
            ]
        ];
    }

    public function listByLocation(string $location, int $limit = 100, int $offset = 0): array {
        $fields = $this->repo->findByLocation($location, $limit, $offset);
        $total = $this->repo->countByLocation($location);
        
        return [
            'data' => $fields,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($fields)
            ]
        ];
    }

    public function search(array $filters, int $limit = 100, int $offset = 0): array {
        // Validar estado si viene en filtros
        if (!empty($filters['state'])) {
            $this->validateState($filters['state']);
        }
        
        $fields = $this->repo->search($filters, $limit, $offset);
        $total = $this->repo->countSearch($filters);
        
        return [
            'data' => $fields,
            'meta' => [
                'total_records' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($fields),
                'filters' => $filters
            ]
        ];
    }

    public function update(int $id, array $data): array {
        // Validar estado si viene
        if (isset($data['state'])) {
            $this->validateState($data['state']);
        }
        
        // Normalizar allowed_sports si viene como string
        if (isset($data['allowed_sports']) && is_string($data['allowed_sports'])) {
            $data['allowed_sports'] = json_decode($data['allowed_sports'], true);
        }
        
        $ok = $this->repo->update($id, $data);
        if (!$ok) throw new RuntimeException("No se pudo actualizar (o sin cambios)");
        
        $f = $this->repo->findById($id);
        return $f ? $f->toArray() : [];
    }

    public function changeState(int $id, string $state): bool {
        // La validación ya está aquí (mantén la existente)
        $this->validateState($state);
        return $this->repo->updateState($id, $state);
    }

    public function delete(int $id, bool $force = false): bool {
        return $force ? $this->repo->hardDelete($id) : $this->repo->softDelete($id);
    }
}
