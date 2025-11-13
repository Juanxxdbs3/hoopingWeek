<?php
// src/models/Championship.php
class Championship {
    public ?int $id;
    public string $name;
    public int $organizer_id;
    public string $sport;
    public string $start_date; // YYYY-MM-DD
    public string $end_date;   // YYYY-MM-DD
    public string $status;
    public ?string $created_at;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->name = $data['name'] ?? '';
        $this->organizer_id = isset($data['organizer_id']) ? (int)$data['organizer_id'] : 0;
        $this->sport = $data['sport'] ?? '';
        $this->start_date = $data['start_date'] ?? null;
        $this->end_date = $data['end_date'] ?? null;
        $this->status = $data['status'] ?? 'planning';
        $this->created_at = $data['created_at'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'organizer_id' => $this->organizer_id,
            'sport' => $this->sport,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'created_at' => $this->created_at
        ];
    }
}
