<?php
// src/routes/userRoutes.php
//Endpoints para la dataLayerAPI
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

    $app->put('/api/users/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->update($req, $res, $args);
    });

    $app->patch('/api/users/{id}/state', function($req, $res, $args) use ($ctrl) {
        return $ctrl->changeState($req, $res, $args);
    });

    $app->patch('/api/users/{id}/athlete-state', function($req, $res, $args) use ($ctrl) {
        return $ctrl->changeAthleteState($req, $res, $args);
    });

    $app->delete('/api/users/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->delete($req, $res, $args);
    });

    $app->get('/api/users/role/{role}', function($req,$res,$args) use ($ctrl) {
        return $ctrl->listByRole($req,$res,$args);
    });

    $app->get('/api/users/state/{state}', function($req,$res,$args) use ($ctrl) {
        return $ctrl->listByState($req,$res,$args);
    });

    $app->get('/api/athletes/state/{athlete_state}', function($req,$res,$args) use ($ctrl) {
        return $ctrl->listAthletesByAthleteState($req,$res,$args);
    });

    // Catálogos (thin: acceso directo simple)
    $app->get('/api/roles', function($req,$res) {
        $pdo = BDConnection::getConnection();
        $rows = $pdo->query("SELECT id,name,description FROM roles ORDER BY id")->fetchAll();
        BDConnection::releaseConnection($pdo);
        $res->getBody()->write(json_encode(['ok'=>true,'roles'=>$rows]));
        return $res->withHeader('Content-Type','application/json');
    });

    $app->get('/api/user-states', function($req,$res) {
        $pdo = BDConnection::getConnection();
        $rows = $pdo->query("SELECT id,name,applies_to,description FROM user_states ORDER BY id")->fetchAll();
        BDConnection::releaseConnection($pdo);
        $res->getBody()->write(json_encode(['ok'=>true,'states'=>$rows]));
        return $res->withHeader('Content-Type','application/json');
    });

    //Endpoints registrados hasta ahora.
    $app->get('/routes', function ($request, $response) use ($app) {
    $routes = [];
    foreach ($app->getRouteCollector()->getRoutes() as $route) {
        $routes[] = [
            'method' => $route->getMethods(),
            'pattern' => $route->getPattern()
        ];
    }
    $response->getBody()->write(json_encode($routes, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

};
