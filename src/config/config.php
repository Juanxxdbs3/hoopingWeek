<?php
// src/config/config.php

require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar .env (si no lo hace en otro lado)
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

// Normalizar las variables que usas en .env
return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'database' => $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? 'hooping_week',
        'username' => $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        'pool_size' => (int)($_ENV['DB_POOL_SIZE'] ?? 3)
    ]
];
