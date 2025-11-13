<?php
// src/services/ReservationService.php
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../repositories/FieldRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/OperatingHoursRepository.php';

class ReservationService {
    private ReservationRepository $repo;
    private FieldRepository $fieldRepo;
    private UserRepository $userRepo;
    private OperatingHoursRepository $operatingHoursRepo;

    public function __construct() {
        $this->repo = new ReservationRepository();
        $this->fieldRepo = new FieldRepository();
        $config = require __DIR__ . '/../config/config.php';
        $this->userRepo = new UserRepository($config['db']);
        $this->operatingHoursRepo = new OperatingHoursRepository();
    }

    /**
     * Validaciones mínimas: campos obligatorios, existencia de field/applicant,
     * formato de fechas (simple), duración positiva.
     * NOTA: No chequea conflictos (overlap). Eso lo hará el broker o un método aparte.
     */
    public function create(array $data): array {
        // campos obligatorios
        foreach (['field_id', 'applicant_id', 'activity_type', 'start_datetime', 'end_datetime'] as $f) {
            if (empty($data[$f])) {
                throw new InvalidArgumentException("$f es obligatorio");
            }
        }

        // validar field existe
        $field = $this->fieldRepo->findById((int)$data['field_id']);
        if (!$field) throw new RuntimeException("field_id no encontrado");

        // validar applicant existe
        $app = $this->userRepo->findById((int)$data['applicant_id']);
        if (!$app) throw new RuntimeException("applicant_id no encontrado");

        // validar datetimes y orden
        $start = strtotime($data['start_datetime']);
        $end = strtotime($data['end_datetime']);
        if ($start === false || $end === false) throw new InvalidArgumentException("Fechas inválidas (usar ISO)");
        if ($start >= $end) throw new InvalidArgumentException("start_datetime debe ser anterior a end_datetime");

        $duration = ($end - $start) / 3600.0;
        if ($duration <= 0) throw new InvalidArgumentException("duration must be > 0");

        $reservation = new Reservation([
            'field_id' => (int)$data['field_id'],
            'applicant_id' => (int)$data['applicant_id'],
            'activity_type' => $data['activity_type'],
            'start_datetime' => gmdate('Y-m-d H:i:s', $start), // store UTC-like string
            'end_datetime' => gmdate('Y-m-d H:i:s', $end),
            'duration_hours' => round($duration, 2),
            'status' => $data['status'] ?? 'pending',
            'priority' => isset($data['priority']) ? (int)$data['priority'] : 0,
            'request_date' => gmdate('Y-m-d H:i:s')
        ]);

        $id = $this->repo->create($reservation);
        $reservation->id = $id;

        $reloaded = $this->repo->findById($id);
        return $reloaded ? $reloaded->toArray() : $reservation->toArray();
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
        $r = $this->repo->findById($id);
        return $r ? $r->toArray() : null;
    }

    public function update(int $id, array $data): array {
        // Validar que existe
        if (!$this->repo->findById($id)) {
            throw new RuntimeException("Reserva no encontrada");
        }

        // Validar field si viene
        if (isset($data['field_id'])) {
            $field = $this->fieldRepo->findById((int)$data['field_id']);
            if (!$field) throw new RuntimeException("field_id no encontrado");
        }

        // Validar applicant si viene
        if (isset($data['applicant_id'])) {
            $app = $this->userRepo->findById((int)$data['applicant_id']);
            if (!$app) throw new RuntimeException("applicant_id no encontrado");
        }

        // Validar fechas si vienen
        if (isset($data['start_datetime']) && isset($data['end_datetime'])) {
            $start = strtotime($data['start_datetime']);
            $end = strtotime($data['end_datetime']);
            if ($start === false || $end === false) {
                throw new InvalidArgumentException("Fechas inválidas");
            }
            if ($start >= $end) {
                throw new InvalidArgumentException("start_datetime debe ser anterior a end_datetime");
            }
            $data['start_datetime'] = gmdate('Y-m-d H:i:s', $start);
            $data['end_datetime'] = gmdate('Y-m-d H:i:s', $end);
            $data['duration_hours'] = round(($end - $start) / 3600.0, 2);
        }

        $ok = $this->repo->update($id, $data);
        if (!$ok) throw new RuntimeException("No se pudo actualizar");
        
        $r = $this->repo->findById($id);
        return $r ? $r->toArray() : [];
    }

    public function changeStatus(int $id, string $status, ?int $approvedBy = null, ?string $rejectionReason = null): bool {
        // Validar estados permitidos
        $validStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];
        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Estado inválido. Permitidos: " . implode(', ', $validStatuses));
        }

        // Validar que existe
        if (!$this->repo->findById($id)) {
            throw new RuntimeException("Reserva no encontrada");
        }

        // Si se aprueba, validar que approved_by existe
        if ($status === 'approved' && $approvedBy !== null) {
            $approver = $this->userRepo->findById($approvedBy);
            if (!$approver) throw new RuntimeException("approved_by no encontrado");
        }

        return $this->repo->updateStatus($id, $status, $approvedBy, $rejectionReason);
    }

    public function delete(int $id, bool $force = false): bool {
        // Validar que existe
        $reservation = $this->repo->findById($id);
        if (!$reservation) {
            throw new RuntimeException("Reserva no encontrada");
        }
        
        // Hard delete: eliminar físicamente
        if ($force) {
            return $this->repo->hardDelete($id);
        }
        
        // Soft delete: marcar como eliminado
        return $this->repo->softDelete($id);
    }

    // === PARTICIPANTS ===

    public function getParticipants(int $reservationId): array {
        // Verificar que la reserva existe
        if (!$this->repo->findById($reservationId)) {
            throw new RuntimeException("Reserva no encontrada");
        }
        return $this->repo->getParticipants($reservationId);
    }

    public function addParticipant(int $reservationId, int $participantId, string $participantType = 'individual', ?int $teamId = null): array {
        // Validar reserva existe
        if (!$this->repo->findById($reservationId)) {
            throw new RuntimeException("Reserva no encontrada");
        }

        // Validar participante existe y es atleta
        $participant = $this->userRepo->findById($participantId);
        if (!$participant) {
            throw new RuntimeException("Participante no encontrado");
        }
        if ($participant->role_id !== 1) {
            throw new RuntimeException("El participante debe ser un atleta (role_id=1)");
        }

        // Validar tipos permitidos
        if (!in_array($participantType, ['individual', 'team_member'], true)) {
            throw new InvalidArgumentException("participant_type inválido. Permitidos: individual, team_member");
        }

        // Verificar que no existe ya
        if ($this->repo->participantExists($reservationId, $participantId)) {
            throw new RuntimeException("El participante ya está en esta reserva");
        }

        $id = $this->repo->addParticipant($reservationId, $participantId, $participantType, $teamId);
        return [
            'id' => $id,
            'reservation_id' => $reservationId,
            'participant_id' => $participantId,
            'participant_type' => $participantType,
            'team_id' => $teamId
        ];
    }

    public function removeParticipant(int $reservationId, int $participantId): bool {
        if (!$this->repo->participantExists($reservationId, $participantId)) {
            throw new RuntimeException("El participante no está en esta reserva");
        }
        return $this->repo->removeParticipant($reservationId, $participantId);
    }

    // === CHECK OVERLAP ===

    public function checkOverlap(int $fieldId, string $startDatetime, string $endDatetime, ?int $excludeReservationId = null): array {
        // Validar field existe
        $field = $this->fieldRepo->findById($fieldId);
        if (!$field) {
            throw new RuntimeException("Campo no encontrado");
        }

        // Validar formato de fechas
        $start = strtotime($startDatetime);
        $end = strtotime($endDatetime);
        if ($start === false || $end === false) {
            throw new InvalidArgumentException("Formato de fecha inválido");
        }
        if ($start >= $end) {
            throw new InvalidArgumentException("start_datetime debe ser anterior a end_datetime");
        }

        // Convertir a formato MySQL
        $startMysql = gmdate('Y-m-d H:i:s', $start);
        $endMysql = gmdate('Y-m-d H:i:s', $end);

        $conflicts = $this->repo->findOverlapping($fieldId, $startMysql, $endMysql, $excludeReservationId);

        return [
            'has_conflict' => count($conflicts) > 0,
            'conflicts' => $conflicts
        ];
    }

    public function getAvailability(int $fieldId, string $date): array {
        // Validar field existe
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
        $dayOfWeek = (int)date('w', $timestamp); // 0=domingo, 6=sábado

        // 1. Verificar si es festivo
        $holiday = $this->operatingHoursRepo->isHoliday($dateMysql);
        if ($holiday) {
            return [
                'field_id' => $fieldId,
                'date' => $dateMysql,
                'is_holiday' => true,
                'holiday_name' => $holiday['name'],
                'is_closed' => true,
                'operating_hours' => null,
                'reserved_slots' => [],
                'available_slots' => []
            ];
        }

        // 2. Verificar excepciones (mantenimiento, eventos especiales)
        $exception = $this->operatingHoursRepo->findException($fieldId, $dateMysql);
        if ($exception) {
            // Si hay excepción y marca cierre
            if ($exception['overrides_regular'] && !$exception['special_start_time']) {
                return [
                    'field_id' => $fieldId,
                    'date' => $dateMysql,
                    'is_exception' => true,
                    'exception_reason' => $exception['reason'],
                    'is_closed' => true,
                    'operating_hours' => null,
                    'reserved_slots' => [],
                    'available_slots' => []
                ];
            }

            // Excepción con horario especial
            $openTime = $exception['special_start_time'];
            $closeTime = $exception['special_end_time'];  // Corregido
        } else {
            // 3. Obtener horario regular
            $regularHours = $this->operatingHoursRepo->findByFieldAndDay($fieldId, $dayOfWeek);
            if (!$regularHours) {
                return [
                    'field_id' => $fieldId,
                    'date' => $dateMysql,
                    'is_closed' => true,
                    'reason' => 'No hay horario configurado para este día',
                    'operating_hours' => null,
                    'reserved_slots' => [],
                    'available_slots' => []
                ];
            }

            $openTime = $regularHours['start_time'];
            $closeTime = $regularHours['end_time'];
        }

        // 4. Obtener slots reservados
        $reservedSlots = $this->repo->getReservedSlots($fieldId, $dateMysql);

        // 5. Calcular slots disponibles
        $availableSlots = $this->calculateAvailableSlots($openTime, $closeTime, $reservedSlots);

        return [
            'field_id' => $fieldId,
            'date' => $dateMysql,
            'is_closed' => false,
            'operating_hours' => [
                'open' => $openTime,
                'close' => $closeTime
            ],
            'reserved_slots' => $reservedSlots,
            'available_slots' => $availableSlots
        ];
    }

    private function calculateAvailableSlots(string $openTime, string $closeTime, array $reservedSlots): array {
        $available = [];
        $currentStart = $openTime;

        foreach ($reservedSlots as $reserved) {
            if ($currentStart < $reserved['start_time']) {
                $available[] = [
                    'start' => $currentStart,
                    'end' => $reserved['start_time']
                ];
            }
            $currentStart = max($currentStart, $reserved['end_time']);
        }

        // Último slot disponible
        if ($currentStart < $closeTime) {
            $available[] = [
                'start' => $currentStart,
                'end' => $closeTime
            ];
        }

        return $available;
    }
}
