<?php
// src/models/Team.php

class Team {
    public ?int $id;
    public string $name;
    public ?string $sport;
    public ?string $type;
    public ?int $trainer_id;
    public ?string $locality;
    public ?string $created_at;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->name = $data['name'] ?? '';
        $this->sport = $data['sport'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->trainer_id = isset($data['trainer_id']) ? (int)$data['trainer_id'] : null;
        $this->locality = $data['locality'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sport' => $this->sport,
            'type' => $this->type,
            'trainer_id' => $this->trainer_id,
            'locality' => $this->locality,
            'created_at' => $this->created_at
        ];
    }
}
