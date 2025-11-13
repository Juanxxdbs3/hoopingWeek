<?php
// src/models/Field.php

class Field {
    public ?int $id;
    public string $name;
    public string $location;
    public ?int $width_meters;
    public ?int $length_meters;
    public ?string $surface_type;
    public ?array $allowed_sports;
    public ?int $people_capacity;
    public string $state; // active, maintenance, inactive
    public bool $is_open_to_public;
    public ?string $owner_entity;
    public ?string $notes;
    public ?string $created_at;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->location = $data['location'] ?? '';
        $this->width_meters = isset($data['width_meters']) ? (int)$data['width_meters'] : null;
        $this->length_meters = isset($data['length_meters']) ? (int)$data['length_meters'] : null;
        $this->surface_type = $data['surface_type'] ?? null;
        $this->allowed_sports = isset($data['allowed_sports']) ? (is_array($data['allowed_sports']) ? $data['allowed_sports'] : json_decode($data['allowed_sports'], true)) : null;
        $this->people_capacity = isset($data['people_capacity']) ? (int)$data['people_capacity'] : null;
        $this->state = $data['state'] ?? 'active';
        $this->is_open_to_public = isset($data['is_open_to_public']) ? (bool)$data['is_open_to_public'] : true;
        $this->owner_entity = $data['owner_entity'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'width_meters' => $this->width_meters,
            'length_meters' => $this->length_meters,
            'surface_type' => $this->surface_type,
            'allowed_sports' => $this->allowed_sports,
            'people_capacity' => $this->people_capacity,
            'state' => $this->state,
            'is_open_to_public' => $this->is_open_to_public,
            'owner_entity' => $this->owner_entity,
            'notes' => $this->notes,
            'created_at' => $this->created_at
        ];
    }
}
