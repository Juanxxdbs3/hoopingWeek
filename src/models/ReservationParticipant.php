<?php
// filepath: c:\xampp\htdocs\hooping_week\src\models\ReservationParticipant.php

class ReservationParticipant {
    public ?int $id;
    public int $reservation_id;
    public int $participant_id;
    public string $participant_type; // 'individual' o 'team_member'
    public ?int $team_id;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->reservation_id = isset($data['reservation_id']) ? (int)$data['reservation_id'] : 0;
        $this->participant_id = isset($data['participant_id']) ? (int)$data['participant_id'] : 0;
        $this->participant_type = $data['participant_type'] ?? 'individual';
        $this->team_id = isset($data['team_id']) ? (int)$data['team_id'] : null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'participant_id' => $this->participant_id,
            'participant_type' => $this->participant_type,
            'team_id' => $this->team_id
        ];
    }
}