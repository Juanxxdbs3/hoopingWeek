<?php
// src/controllers/FieldController.php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once __DIR__ . '/../services/FieldService.php';
require_once __DIR__ . '/../services/ReservationService.php';

class FieldController {
    private FieldService $fieldService;
    private ReservationService $reservationService;

    public function __construct() {
        $this->fieldService = new FieldService();
        $this->reservationService = new ReservationService();
    }

    public function createField(Request $request, Response $response): Response {
        $body = $request->getParsedBody() ?? [];
        try {
            $field = $this->fieldService->create($body);
            $response->getBody()->write(json_encode(['ok' => true, 'field' => $field]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAllFields(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $fields = $this->fieldService->list($limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $fields]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFieldById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        try {
            $field = $this->fieldService->getById($id);
            if (!$field) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Campo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'field' => $field]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function listByState(Request $request, Response $response, array $args): Response {
        $state = $args['state'];
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->fieldService->listByState($state, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listByLocation(Request $request, Response $response, array $args): Response {
        $location = $args['location'];
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->fieldService->listByLocation($location, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function search(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $filters = [
            'location' => $params['location'] ?? null,
            'state' => $params['state'] ?? null,
            'sport' => $params['sport'] ?? null
        ];
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        
        $result = $this->fieldService->search($filters, $limit, $offset);
        $response->getBody()->write(json_encode(['ok' => true, 'fields' => $result['data'], 'meta' => $result['meta']]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Asegúrate de que este método exista y use el ID de la ruta
    public function update(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        
        try {
            // Validar si el body está vacío
            if (empty($body)) {
                throw new \InvalidArgumentException("El cuerpo de la solicitud no puede estar vacío");
            }

            $field = $this->fieldService->update($id, $body);
            
            // Si update devuelve array vacío significa que no encontró el ID (según tu servicio)
            if (empty($field)) {
                 $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Campo no encontrado']));
                 return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode(['ok' => true, 'field' => $field]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\RuntimeException $e) {
            // Captura errores de lógica de negocio
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400); // Bad Request
        } catch (\InvalidArgumentException $e) {
             $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
             return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Método específico para cambiar estado (PATCH)
    public function changeState(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $body = (array)$request->getParsedBody();
        $state = $body['state'] ?? null;
        
        try {
            if (!$state) throw new \InvalidArgumentException("state requerido");
            
            $success = $this->fieldService->changeState($id, $state);
            
            if (!$success) {
                 $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Campo no encontrado o estado inválido']));
                 return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode(['ok' => true, 'message' => "Estado actualizado a $state"]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // Método para hard/soft delete
    public function delete(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $params = $request->getQueryParams();
        $force = isset($params['force']) && $params['force'] === 'true';
        
        try {
            $ok = $this->fieldService->delete($id, $force);
            if (!$ok) {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Campo no encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $response->getBody()->write(json_encode(['ok' => true, 'message' => $force ? 'Eliminado permanentemente' : 'Desactivado (Soft Delete)']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
             // Capturar error de integridad referencial (si intentas borrar un campo con reservas)
             if ($e->getCode() == '23000') {
                $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Integrity constraint violation: El campo tiene registros asociados.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409); // Conflict
             }
             throw $e;
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'Error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /*
    public function getAvailability(Request $request, Response $response, array $args): Response {
        $fieldId = (int)$args['id'];
        $query = $request->getQueryParams();
        $date = $query['date'] ?? date('Y-m-d');

        try {
            $result = $this->reservationService->getAvailability($fieldId, $date);
            $response->getBody()->write(json_encode(['ok' => true, 'availability' => $result]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['ok' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $response->getBody()->write(json_encode(['ok' => false, 'error' => 'error interno']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    */
}
