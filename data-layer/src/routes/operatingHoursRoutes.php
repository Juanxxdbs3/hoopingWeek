<?php
// filepath: c:\xampp\htdocs\hooping_week\data-layer\src\routes\operatingHoursRoutes.php
require_once __DIR__ . '/../controllers/OperatingHoursController.php';

return function($app) {
    $ctrl = new OperatingHoursController();

    // Horarios regulares
    $app->get('/api/fields/{field_id}/operating-hours', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getByField($req, $res, $args);
    });

    $app->post('/api/fields/{field_id}/operating-hours', function($req, $res, $args) use ($ctrl) {
        return $ctrl->create($req, $res, $args);
    });

    // Nuevo: Borrar horario regular de un día específico (ej: quitar disponibilidad los domingos)
    $app->delete('/api/fields/{field_id}/operating-hours/{day_of_week}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->deleteOperatingHour($req, $res, $args);
    });

    // Excepciones (festivos, mantenimiento)
    $app->post('/api/fields/{field_id}/exceptions', function($req, $res, $args) use ($ctrl) {
        return $ctrl->createException($req, $res, $args);
    });

    $app->get('/api/fields/{field_id}/exceptions', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getException($req, $res, $args);
    });

    $app->get('/api/fields/{field_id}/exceptions/range', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getExceptionsByRange($req, $res, $args);
    });

    // Nuevo: Borrar excepción por ID (útil si te equivocaste de fecha)
    $app->delete('/api/exceptions/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->deleteException($req, $res, $args);
    });

    $app->get('/api/fields/{field_id}/reserved-slots', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getReservedSlots($req, $res, $args);
    });
};