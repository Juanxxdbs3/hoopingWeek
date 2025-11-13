<?php
require_once __DIR__ . '/../repositories/StatisticsRepository.php';

class StatisticsService {
    private StatisticsRepository $repo;
    
    public function __construct() { 
        $this->repo = new StatisticsRepository(); 
    }

    private function normalizeRange(?string $start, ?string $end): array {
        // Si no se pasan fechas, últimos 30 días
        if (!$end) $end = date('Y-m-d') . ' 23:59:59';
        if (!$start) $start = date('Y-m-d', strtotime($end . ' -29 days')) . ' 00:00:00';
        
        // Validar formato
        if (strtotime($start) === false || strtotime($end) === false) {
            throw new InvalidArgumentException("Fechas inválidas (usar YYYY-MM-DD)");
        }
        if (strtotime($start) > strtotime($end)) {
            throw new InvalidArgumentException("start_date debe ser anterior a end_date");
        }
        
        return [$start, $end];
    }

    // 1. Reservas por día
    public function reservationsPerDay(?string $start, ?string $end, ?int $fieldId): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $rows = $this->repo->reservationsPerDay($s, $e, $fieldId);
        return [
            'data' => $rows,
            'meta' => [
                'start' => $s,
                'end' => $e,
                'field_id' => $fieldId,
                'count' => count($rows),
                'timestamp' => date('c')
            ]
        ];
    }

    // 2. Duración promedio
    public function avgReservationDuration(?string $start, ?string $end): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $avg = $this->repo->avgReservationDuration($s, $e);
        return [
            'avg_hours' => round($avg, 2),
            'meta' => [
                'start' => $s,
                'end' => $e,
                'timestamp' => date('c')
            ]
        ];
    }

    // 3. Breakdown por actividad y status
    public function breakdownByActivityAndStatus(?string $start, ?string $end): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $rows = $this->repo->breakdownByActivityAndStatus($s, $e);
        return [
            'data' => $rows,
            'meta' => [
                'start' => $s,
                'end' => $e,
                'timestamp' => date('c')
            ]
        ];
    }

    // 4. Top campos
    public function topFields(?string $start, ?string $end, int $limit = 10): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $rows = $this->repo->topFields($s, $e, $limit);
        return [
            'data' => $rows,
            'meta' => [
                'start' => $s,
                'end' => $e,
                'limit' => $limit,
                'count' => count($rows),
                'timestamp' => date('c')
            ]
        ];
    }

    // 5. Registros de usuarios
    public function registrationsPerDay(?string $start, ?string $end, ?int $roleId): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $rows = $this->repo->registrationsPerDay($s, $e, $roleId);
        return [
            'data' => $rows,
            'meta' => [
                'start' => $s,
                'end' => $e,
                'role_id' => $roleId,
                'count' => count($rows),
                'timestamp' => date('c')
            ]
        ];
    }

    // 6. Tasa de cancelaciones
    public function cancellationsRate(?string $start, ?string $end): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $pct = $this->repo->cancellationsRate($s, $e);
        return [
            'canceled_percentage' => round($pct, 2),
            'meta' => [
                'start' => $s,
                'end' => $e,
                'timestamp' => date('c')
            ]
        ];
    }

    // 7. Utilización de campo
    public function fieldUtilization(int $fieldId, ?string $start, ?string $end): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $data = $this->repo->fieldUtilization($fieldId, $s, $e);
        $data['meta'] = [
            'start' => $s,
            'end' => $e,
            'timestamp' => date('c')
        ];
        return $data;
    }

    // 8. Top equipos
    public function topTeams(?string $start, ?string $end, int $limit = 10): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $rows = $this->repo->topTeams($s, $e, $limit);
        return [
            'data' => $rows,
            'meta' => [
                'start' => $s,
                'end' => $e,
                'limit' => $limit,
                'count' => count($rows),
                'timestamp' => date('c')
            ]
        ];
    }

    // 9. Top usuarios
    public function topUsers(?string $start, ?string $end, int $limit = 10): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $rows = $this->repo->topUsers($s, $e, $limit);
        return [
            'data' => $rows,
            'meta' => [
                'start' => $s,
                'end' => $e,
                'limit' => $limit,
                'count' => count($rows),
                'timestamp' => date('c')
            ]
        ];
    }

    // 10. Horarios pico
    public function peakHours(?string $start, ?string $end): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $rows = $this->repo->peakHours($s, $e);
        return [
            'data' => $rows,
            'meta' => [
                'start' => $s,
                'end' => $e,
                'count' => count($rows),
                'timestamp' => date('c')
            ]
        ];
    }

    // 11. Actividad de equipo
    public function teamActivity(int $teamId, ?string $start, ?string $end): array {
        [$s, $e] = $this->normalizeRange($start, $end);
        $data = $this->repo->teamActivity($teamId, $s, $e);
        $data['meta'] = [
            'start' => $s,
            'end' => $e,
            'timestamp' => date('c')
        ];
        return $data;
    }
}