<?php
// filepath: c:\xampp\htdocs\hooping_week\src\services\OperatingHoursService.php
require_once __DIR__ . '/../repositories/OperatingHoursRepository.php';
require_once __DIR__ . '/../repositories/FieldRepository.php';

class OperatingHoursService {
    private OperatingHoursRepository $repo;
    private FieldRepository $fieldRepo;

    public function __construct() {
        $this->repo = new OperatingHoursRepository();
        $this->fieldRepo = new FieldRepository();
    }

    public function getByField(int $fieldId): array {
        // Validar que el campo existe
        $field = $this->fieldRepo->findById($fieldId);
        if (!$field) {
            throw new RuntimeException("Campo no encontrado");
        }

        return $this->repo->findAllByField($fieldId);
    }

    public function create(int $fieldId, int $dayOfWeek, string $openTime, string $closeTime): array {
        // Validar campo existe
        $field = $this->fieldRepo->findById($fieldId);
        if (!$field) {
            throw new RuntimeException("Campo no encontrado");
        }

        // Validar day_of_week (0-6)
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new InvalidArgumentException("day_of_week debe estar entre 0 (domingo) y 6 (sábado)");
        }

        // Validar formato de horas
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $openTime) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $closeTime)) {
            throw new InvalidArgumentException("Formato de hora inválido. Usar HH:MM:SS");
        }

        // Validar que open < close
        if (strtotime($openTime) >= strtotime($closeTime)) {
            throw new InvalidArgumentException("open_time debe ser anterior a close_time");
        }

        // Verificar si ya existe horario para ese día
        $existing = $this->repo->findByFieldAndDay($fieldId, $dayOfWeek);
        if ($existing) {
            throw new RuntimeException("Ya existe un horario para este campo en este día. Use PUT para actualizar.");
        }

        $id = $this->repo->create($fieldId, $dayOfWeek, $openTime, $closeTime);
        
        return [
            'id' => $id,
            'field_id' => $fieldId,
            'day_of_week' => $dayOfWeek,
            'open_time' => $openTime,
            'close_time' => $closeTime
        ];
    }

    public function createException(int $fieldId, string $date, string $reason, bool $overridesRegular = false, ?string $openTime = null, ?string $closeTime = null): array {
        // Validar campo existe
        $field = $this->fieldRepo->findById($fieldId);
        if (!$field) {
            throw new RuntimeException("Campo no encontrado");
        }

        // Validar formato de fecha
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new InvalidArgumentException("Formato de fecha inválido (usar YYYY-MM-DD)");
        }
        $dateMysql = date('Y-m-d', $timestamp);

        // Validar horarios si se proporcionan
        if ($openTime && $closeTime) {
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $openTime) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $closeTime)) {
                throw new InvalidArgumentException("Formato de hora inválido. Usar HH:MM:SS");
            }
            if (strtotime($openTime) >= strtotime($closeTime)) {
                throw new InvalidArgumentException("open_time debe ser anterior a close_time");
            }
        }

        // Verificar si ya existe excepción para esa fecha
        $existing = $this->repo->findException($fieldId, $dateMysql);
        if ($existing) {
            throw new RuntimeException("Ya existe una excepción para este campo en esta fecha");
        }

        $id = $this->repo->createException($fieldId, $dateMysql, $reason, $overridesRegular, $openTime, $closeTime);
        
        return [
            'id' => $id,
            'field_id' => $fieldId,
            'date' => $dateMysql,
            'reason' => $reason,
            'overrides_regular' => $overridesRegular,
            'open_time' => $openTime,
            'close_time' => $closeTime
        ];
    }

    public function getException(int $fieldId, string $date): ?array {
        $field = $this->fieldRepo->findById($fieldId);
        if (!$field) {
            throw new RuntimeException("Campo no encontrado");
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new InvalidArgumentException("Formato de fecha inválido");
        }
        $dateMysql = date('Y-m-d', $timestamp);

        return $this->repo->findException($fieldId, $dateMysql);
    }

    public function getExceptionsByRange(int $fieldId, string $startDate, string $endDate): array {
        // Validar que field existe
        require_once __DIR__ . '/../repositories/FieldRepository.php';
        $fieldRepo = new FieldRepository();
        if (!$fieldRepo->findById($fieldId)) {
            throw new \RuntimeException("Campo no encontrado");
        }
        
        // Validar formato de fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            throw new \InvalidArgumentException("Formato de fecha inválido. Use YYYY-MM-DD");
        }
        
        return $this->repo->getExceptionsByRange($fieldId, $startDate, $endDate);
    }

    // Eliminar horario regular de un día
    public function deleteOperatingHour(int $fieldId, int $dayOfWeek): bool {
        // Validaciones básicas
        if ($dayOfWeek < 0 || $dayOfWeek > 6) return false;

        // Validar que el campo existe
        $field = $this->fieldRepo->findById($fieldId);
        if (!$field) {
            return false;
        }

        return $this->repo->deleteByFieldAndDay($fieldId, $dayOfWeek);
    }

    // Eliminar excepción por ID
    public function deleteException(int $id): bool {
        if (!is_int($id) || $id <= 0) return false;
        return $this->repo->deleteException($id);
    }
}