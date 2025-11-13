<?php
require_once __DIR__ . '/../controllers/StatisticsController.php';

return function($app) {
    $ctrl = new StatisticsController();

    // Endpoints de estadísticas
    $app->get('/api/stats/reservations-per-day', function($req, $res) use ($ctrl) {
        return $ctrl->reservationsPerDay($req, $res);
    });

    $app->get('/api/stats/avg-reservation-duration', function($req, $res) use ($ctrl) {
        return $ctrl->avgReservationDuration($req, $res);
    });

    $app->get('/api/stats/breakdown', function($req, $res) use ($ctrl) {
        return $ctrl->breakdownByActivityAndStatus($req, $res);
    });

    $app->get('/api/stats/top-fields', function($req, $res) use ($ctrl) {
        return $ctrl->topFields($req, $res);
    });

    $app->get('/api/stats/registrations-per-day', function($req, $res) use ($ctrl) {
        return $ctrl->registrationsPerDay($req, $res);
    });

    $app->get('/api/stats/cancellations-rate', function($req, $res) use ($ctrl) {
        return $ctrl->cancellationsRate($req, $res);
    });

    // CORREGIDO: usar query param en lugar de path param
    $app->get('/api/stats/field-utilization', function($req, $res) use ($ctrl) {
        return $ctrl->fieldUtilization($req, $res);
    });

    $app->get('/api/stats/top-teams', function($req, $res) use ($ctrl) {
        return $ctrl->topTeams($req, $res);
    });

    $app->get('/api/stats/top-users', function($req, $res) use ($ctrl) {
        return $ctrl->topUsers($req, $res);
    });

    $app->get('/api/stats/peak-hours', function($req, $res) use ($ctrl) {
        return $ctrl->peakHours($req, $res);
    });

    $app->get('/api/stats/teams/{team_id}/activity', function($req, $res, $args) use ($ctrl) {
        return $ctrl->teamActivity($req, $res, $args);
    });
};