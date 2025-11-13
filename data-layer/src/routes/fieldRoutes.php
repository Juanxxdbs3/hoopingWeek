<?php
require_once __DIR__ . '/../controllers/FieldController.php';

return function($app) {
    $ctrl = new FieldController();

    $app->post('/api/fields', function($req, $res) use ($ctrl) {
        return $ctrl->createField($req, $res);
    });

    $app->get('/api/fields', function($req, $res) use ($ctrl) {
        return $ctrl->getAllFields($req, $res);
    });

    $app->get('/api/fields/search', function($req, $res) use ($ctrl) {
        return $ctrl->search($req, $res);
    });

    $app->get('/api/fields/state/{state}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->listByState($req, $res, $args);
    });

    $app->get('/api/fields/location/{location}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->listByLocation($req, $res, $args);
    });

    $app->get('/api/fields/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getFieldById($req, $res, $args);
    });

    $app->get('/api/fields/{id}/availability', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getAvailability($req, $res, $args);
    });

    $app->put('/api/fields/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->update($req, $res, $args);
    });

    $app->patch('/api/fields/{id}/state', function($req, $res, $args) use ($ctrl) {
        return $ctrl->changeState($req, $res, $args);
    });

    $app->delete('/api/fields/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->delete($req, $res, $args);
    });
};