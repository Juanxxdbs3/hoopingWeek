<?php
// filepath: c:\xampp\htdocs\hooping_week\src\routes\reservationRoutes.php
require_once __DIR__ . '/../controllers/ReservationController.php';

return function($app) {
    $ctrl = new ReservationController();

    $app->post('/api/reservations', function($req, $res) use ($ctrl) {
        return $ctrl->createReservation($req, $res);
    });

    $app->get('/api/reservations', function($req, $res) use ($ctrl) {
        return $ctrl->listReservations($req, $res);
    });

    $app->get('/api/reservations/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getReservationById($req, $res, $args);
    });

    $app->put('/api/reservations/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->updateReservation($req, $res, $args);
    });

    $app->patch('/api/reservations/{id}/status', function($req, $res, $args) use ($ctrl) {
        return $ctrl->changeStatus($req, $res, $args);
    });

    $app->delete('/api/reservations/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->deleteReservation($req, $res, $args);
    });

    // Participants endpoints
    $app->get('/api/reservations/{id}/participants', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getParticipants($req, $res, $args);
    });

    $app->post('/api/reservations/{id}/participants', function($req, $res, $args) use ($ctrl) {
        return $ctrl->addParticipant($req, $res, $args);
    });

    $app->delete('/api/reservations/{reservation_id}/participants/{participant_id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->removeParticipant($req, $res, $args);
    });

    // Check overlap
    $app->post('/api/reservations/check-overlap', function($req, $res) use ($ctrl) {
        return $ctrl->checkOverlap($req, $res);
    });
};