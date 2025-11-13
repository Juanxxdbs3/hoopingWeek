<?php
// src/models/ManagerShift.php
class ManagerShift {
    public ?int $id;
    public int $manager_id;
    public int $field_id;
    public int $day_of_week; // 0..6
    public string $start_time; // 'HH:MM:SS'
    public string $end_time;   // 'HH:MM:SS'
    public bool $active;
    public ?string $note;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->manager_id = isset($data['manager_id']) ? (int)$data['manager_id'] : 0;
        $this->field_id = isset($data['field_id']) ? (int)$data['field_id'] : 0;
        $this->day_of_week = isset($data['day_of_week']) ? (int)$data['day_of_week'] : 0;
        $this->start_time = $data['start_time'] ?? '00:00:00';
        $this->end_time = $data['end_time'] ?? '00:00:00';
        $this->active = isset($data['active']) ? (bool)$data['active'] : true;
        $this->note = $data['note'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'manager_id' => $this->manager_id,
            'field_id' => $this->field_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'active' => $this->active,
            'note' => $this->note
        ];
    }
}
