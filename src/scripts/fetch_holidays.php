<?php
// filepath: c:\xampp\htdocs\hooping_week\src\scripts\fetch_holidays.php
require_once __DIR__ . '/../database/BDConnection.php';

$config = require __DIR__ . '/../config/config.php';
BDConnection::init($config['db']);

$year = $argv[1] ?? date('Y');
$country = $argv[2] ?? 'CO'; // Colombia por defecto

$apiUrl = "https://date.nager.at/api/v3/publicholidays/{$year}/{$country}";

echo "Fetching holidays for {$country} - {$year}...\n";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error fetching data from API. HTTP Code: {$httpCode}\n");
}

$holidays = json_decode($response, true);
if (!$holidays) {
    die("Error parsing JSON response.\n");
}

$pdo = BDConnection::getConnection();
$inserted = 0;

try {
    $checkStmt = $pdo->prepare("SELECT id FROM holidays WHERE holiday_date = :date LIMIT 1");
    $insertStmt = $pdo->prepare("INSERT INTO holidays (holiday_date, name, is_national) VALUES (:date, :name, :is_national)");

    foreach ($holidays as $holiday) {
        $date = $holiday['date'];
        $name = $holiday['localName'] ?? $holiday['name'];
        $isNational = $holiday['global'] ?? true;

        // Check if exists
        $checkStmt->execute([':date' => $date]);
        if ($checkStmt->fetch()) {
            continue; // Skip duplicates
        }

        // Insert
        $insertStmt->execute([
            ':date' => $date,
            ':name' => $name,
            ':is_national' => $isNational ? 1 : 0
        ]);
        $inserted++;
    }

    echo "✓ Inserted {$inserted} holidays for {$year}\n";
} finally {
    BDConnection::releaseConnection($pdo);
}