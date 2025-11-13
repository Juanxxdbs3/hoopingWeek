<?php
// filepath: c:\xampp\htdocs\hooping_week\src\models\Match.php

class MatchModel {
    public ?int $id;
    public int $reservation_id;
    public int $team1_id;
    public int $team2_id;
    public bool $is_friendly;
    public ?int $championship_id;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->reservation_id = isset($data['reservation_id']) ? (int)$data['reservation_id'] : 0;
        $this->team1_id = isset($data['team1_id']) ? (int)$data['team1_id'] : 0;
        $this->team2_id = isset($data['team2_id']) ? (int)$data['team2_id'] : 0;
        $this->is_friendly = isset($data['is_friendly']) ? (bool)$data['is_friendly'] : true;
        $this->championship_id = isset($data['championship_id']) ? (int)$data['championship_id'] : null;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservation_id,
            'team1_id' => $this->team1_id,
            'team2_id' => $this->team2_id,
            'is_friendly' => $this->is_friendly,
            'championship_id' => $this->championship_id
        ];
    }
}