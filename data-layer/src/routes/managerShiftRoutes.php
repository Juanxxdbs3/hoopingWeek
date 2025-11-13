<?php
// src/routes/managerShiftRoutes.php
require_once __DIR__ . '/../controllers/ManagerShiftController.php';

return function($app) {
    $ctrl = new ManagerShiftController();

    $app->post('/api/manager-shifts', function($req,$res) use ($ctrl) { return $ctrl->create($req,$res); });
    $app->get('/api/manager-shifts', function($req,$res) use ($ctrl) { return $ctrl->list($req,$res); });
    $app->get('/api/manager-shifts/{id}', function($req,$res,$args) use ($ctrl) { return $ctrl->getById($req,$res,$args); });
    $app->put('/api/manager-shifts/{id}', function($req,$res,$args) use ($ctrl) { return $ctrl->update($req,$res,$args); });
    $app->delete('/api/manager-shifts/{id}', function($req,$res,$args) use ($ctrl) { return $ctrl->delete($req,$res,$args); });

    // helper route to list shifts by field
    $app->get('/api/fields/{field_id}/manager-shifts', function($req,$res,$args) use ($ctrl) {
        // pass query param field_id
        return $ctrl->list($req->withQueryParams(array_merge($req->getQueryParams(), ['field_id'=>$args['field_id']])), $res);
    });
};
