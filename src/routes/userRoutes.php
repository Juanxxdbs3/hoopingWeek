<?php
// src/routes/userRoutes.php
require_once __DIR__ . '/../controllers/UserController.php';

return function($app, $config) {
    $ctrl = new UserController($config['db']);

    $app->post('/api/users', function($req, $res) use ($ctrl) {
        return $ctrl->register($req, $res);
    });

    $app->get('/api/users', function($req, $res) use ($ctrl) {
        return $ctrl->list($req, $res);
    });

    $app->get('/api/users/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getById($req, $res, $args);
    });
};
