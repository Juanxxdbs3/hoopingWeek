<?php
// src/database/BDConnection.php

class BDConnection {
    private static array $pool = [];
    private static array $config = [];
    private static int $initialized = 0;

    public static function init(array $config) {
        // Esperamos que $config tenga: host, database, username, password, charset, pool_size
        self::$config = $config;
        $poolSize = (int)($config['pool_size'] ?? 3);

        // Sólo inicializar una vez
        if (self::$initialized) {
            return;
        }

        for ($i = 0; $i < $poolSize; $i++) {
            $conn = self::createConnection();
            if ($conn instanceof PDO) {
                self::$pool[] = $conn;
            }
        }
        self::$initialized = 1;
    }

    private static function createConnection() {
        $host = self::$config['host'] ?? '127.0.0.1';
        $db   = self::$config['database'] ?? self::$config['name'] ?? '';
        $user = self::$config['username'] ?? self::$config['user'] ?? '';
        $pass = self::$config['password'] ?? self::$config['pass'] ?? '';
        $charset = self::$config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // En desarrollo está bien mostrar el error; en producción loguear y no mostrar
            error_log("[DB] Error conexión: " . $e->getMessage());
            return null;
        }
    }

    public static function getConnection() {
        if (empty(self::$pool)) {
            throw new Exception("No hay conexiones disponibles en el pool");
        }
        // devolver una conexión (LIFO)
        return array_pop(self::$pool);
    }

    public static function releaseConnection($connection) {
        if ($connection instanceof PDO) {
            self::$pool[] = $connection;
        }
    }

    public static function getPoolSize() {
        return count(self::$pool);
    }

    // debug
    public static function debugStatus(): array {
        return [
            'pool_size' => self::getPoolSize()
        ];
    }
}
