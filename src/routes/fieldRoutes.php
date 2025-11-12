<?php
// src/routes/fieldRoutes.php
require_once __DIR__ . '/../controllers/FieldController.php';
require_once __DIR__ . '/../database/BDConnection.php';  // Añadir si usas catálogos

return function($app) {
    $ctrl = new FieldController();

    $app->post('/api/fields', function($req, $res) use ($ctrl) {
        return $ctrl->createField($req, $res);
    });

    $app->get('/api/fields', function($req, $res) use ($ctrl) {
        return $ctrl->getAllFields($req, $res);
    });

    $app->get('/api/fields/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getFieldById($req, $res, $args);
    });
};