<?php
// filepath: c:\xampp\htdocs\hooping_week\frontend-demo\create_reservation.php

header('Content-Type: application/json');

// URL del broker
define('BROKER_URL', 'http://localhost:5000/api/reservations/create-validated');

// Leer el JSON del body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Datos inválidos'
    ]);
    exit;
}

// Validar campos requeridos
$required = ['applicant_id', 'field_id', 'start_date', 'start_time', 'duration_hours', 'purpose'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => "Campo requerido: $field"
        ]);
        exit;
    }
}

// Llamar al broker usando cURL
$ch = curl_init(BROKER_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Ejecutar request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Manejo de errores de cURL
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error de conexión con el broker',
        'details' => $curlError
    ]);
    exit;
}

// Retornar la respuesta del broker
http_response_code($httpCode);
echo $response;
?>