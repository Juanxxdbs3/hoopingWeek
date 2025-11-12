<?php
// src/models/User.php

class User {
    public ?int $id;
    public string $first_name;
    public string $last_name;
    public string $email;
    public ?string $phone;
    public ?string $passwordHash;
    public int $role_id;              // FK roles.id
    public ?string $role_name;        // JOIN (solo lectura)
    public int $state_id;             // FK user_states.id
    public ?string $state_name;       // JOIN (solo lectura)
    public ?int $athlete_state_id;    // FK user_states.id (nullable)
    public ?string $athlete_state_name;
    public ?float $height;
    public ?string $birth_date;       // 'YYYY-MM-DD'
    public ?string $created_at;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->first_name = $data['first_name'] ?? '';
        $this->last_name = $data['last_name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? null;
        $this->passwordHash = $data['password_hash'] ?? ($data['passwordHash'] ?? null);
        $this->role_id = isset($data['role_id']) ? (int)$data['role_id'] : 1;
        $this->role_name = $data['role_name'] ?? null;
        $this->state_id = isset($data['state_id']) ? (int)$data['state_id'] : 1;
        $this->state_name = $data['state_name'] ?? null;
        $this->athlete_state_id = isset($data['athlete_state_id']) ? (int)$data['athlete_state_id'] : null;
        $this->athlete_state_name = $data['athlete_state_name'] ?? null;
        $this->height = isset($data['height']) ? (float)$data['height'] : null;
        $this->birth_date = $data['birth_date'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    public function fullName(): string {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'role_id' => $this->role_id,
            'role_name' => $this->role_name,
            'state_id' => $this->state_id,
            'state_name' => $this->state_name,
            'athlete_state_id' => $this->athlete_state_id,
            'athlete_state_name' => $this->athlete_state_name,
            'height' => $this->height,
            'birth_date' => $this->birth_date,
            'created_at' => $this->created_at,
        ];
    }
}
