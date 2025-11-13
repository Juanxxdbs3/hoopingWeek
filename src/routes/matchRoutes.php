<?php
require_once __DIR__ . '/../controllers/MatchController.php';

return function($app) {
    $ctrl = new MatchController();

    $app->post('/api/matches', function($req, $res) use ($ctrl) {
        return $ctrl->createMatch($req, $res);
    });

    $app->get('/api/matches', function($req, $res) use ($ctrl) {
        return $ctrl->listMatches($req, $res);
    });

    $app->get('/api/matches/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getMatchById($req, $res, $args);
    });

    $app->get('/api/matches/by-reservation/{reservation_id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getByReservation($req, $res, $args);
    });

    $app->delete('/api/matches/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->deleteMatch($req, $res, $args);
    });
};