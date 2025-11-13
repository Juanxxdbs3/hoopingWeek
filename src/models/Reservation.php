<?php
// src/models/Reservation.php

class Reservation {
    public ?int $id;
    public int $field_id;
    public int $applicant_id;
    public string $activity_type;
    public string $start_datetime; // ISO string en UTC
    public string $end_datetime;   // ISO string en UTC
    public float $duration_hours;
    public string $status;
    public int $priority;
    public ?int $approved_by;
    public ?string $request_date;
    public ?string $rejection_reason;
    public ?string $notes;
    public int $soft_deleted;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->field_id = isset($data['field_id']) ? (int)$data['field_id'] : 0;
        $this->applicant_id = isset($data['applicant_id']) ? (int)$data['applicant_id'] : 0;
        $this->activity_type = $data['activity_type'] ?? '';
        $this->start_datetime = $data['start_datetime'] ?? null;
        $this->end_datetime = $data['end_datetime'] ?? null;
        $this->duration_hours = isset($data['duration_hours']) ? (float)$data['duration_hours'] : 0.0;
        $this->status = $data['status'] ?? 'pending';
        $this->priority = isset($data['priority']) ? (int)$data['priority'] : 0;
        $this->approved_by = isset($data['approved_by']) ? (int)$data['approved_by'] : null;
        $this->request_date = $data['request_date'] ?? null;
        $this->rejection_reason = $data['rejection_reason'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->soft_deleted = isset($data['soft_deleted']) ? (int)$data['soft_deleted'] : 0;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'field_id' => $this->field_id,
            'applicant_id' => $this->applicant_id,
            'activity_type' => $this->activity_type,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'duration_hours' => $this->duration_hours,
            'status' => $this->status,
            'priority' => $this->priority,
            'approved_by' => $this->approved_by,
            'request_date' => $this->request_date,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'soft_deleted' => $this->soft_deleted
        ];
    }
}
