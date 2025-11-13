<?php
require_once __DIR__ . '/../controllers/ChampionshipController.php';

return function($app) {
    $ctrl = new ChampionshipController();

    $app->post('/api/championships', function($req,$res) use ($ctrl) { 
        return $ctrl->create($req,$res); 
    });
    
    $app->get('/api/championships', function($req,$res) use ($ctrl) { 
        return $ctrl->list($req,$res); 
    });
    
    $app->get('/api/championships/{id}', function($req,$res,$args) use ($ctrl) { 
        return $ctrl->getById($req,$res,$args); 
    });

    $app->put('/api/championships/{id}', function($req,$res,$args) use ($ctrl) { 
        return $ctrl->update($req,$res,$args); 
    });

    $app->delete('/api/championships/{id}', function($req,$res,$args) use ($ctrl) { 
        return $ctrl->delete($req,$res,$args); 
    });

    $app->post('/api/championships/{id}/teams', function($req,$res,$args) use ($ctrl) { 
        return $ctrl->addTeam($req,$res,$args); 
    });
    
    $app->get('/api/championships/{id}/teams', function($req,$res,$args) use ($ctrl) { 
        return $ctrl->listTeams($req,$res,$args); 
    });
    
    $app->delete('/api/championships/{id}/teams/{team_id}', function($req,$res,$args) use ($ctrl) { 
        return $ctrl->removeTeam($req,$res,$args); 
    });
};
