<?php
require_once __DIR__ . '/../controllers/TeamController.php';

return function($app) {
    $ctrl = new TeamController();

    $app->post('/api/teams', function($req, $res) use ($ctrl) {
        return $ctrl->createTeam($req, $res);
    });

    $app->get('/api/teams', function($req, $res) use ($ctrl) {
        return $ctrl->listTeams($req, $res);
    });

    $app->get('/api/teams/trainer/{trainer_id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->listByTrainer($req, $res, $args);
    });

    $app->get('/api/teams/sport/{sport}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->listBySport($req, $res, $args);
    });

    $app->get('/api/teams/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getTeamById($req, $res, $args);
    });

    $app->put('/api/teams/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->updateTeam($req, $res, $args);
    });

    $app->delete('/api/teams/{id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->deleteTeam($req, $res, $args);
    });

    // TeamMembership endpoints
    $app->get('/api/teams/{id}/members', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getMembers($req, $res, $args);
    });

    $app->post('/api/teams/{id}/members', function($req, $res, $args) use ($ctrl) {
        return $ctrl->addMember($req, $res, $args);
    });

    $app->delete('/api/teams/{team_id}/members/{athlete_id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->removeMember($req, $res, $args);
    });

    $app->get('/api/teams/{team_id}/members/{athlete_id}', function($req, $res, $args) use ($ctrl) {
        return $ctrl->getMemberDetails($req, $res, $args);
    });
};