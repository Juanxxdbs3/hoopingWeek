<?php
// src/models/User.php

class User {
    public ?int $id;
    public string $name;
    public string $email;
    public ?string $phone;
    public ?string $passwordHash;
    public string $role;        // athlete, trainer, admin_field, super_admin
    public string $state;       // active, inactive, suspended, injured
    public ?float $height;
    public ?string $birth_date; // 'YYYY-MM-DD'
    public ?string $athlete_state;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? null;
        $this->passwordHash = $data['password_hash'] ?? ($data['passwordHash'] ?? null);
        $this->role = $data['role'] ?? 'athlete';
        $this->state = $data['state'] ?? 'active';
        $this->height = isset($data['height']) ? (float)$data['height'] : null;
        $this->birth_date = $data['birth_date'] ?? null;
        $this->athlete_state = $data['athlete_state'] ?? null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'state' => $this->state,
            'height' => $this->height,
            'birth_date' => $this->birth_date,
            'athlete_state' => $this->athlete_state,
        ];
    }
}
