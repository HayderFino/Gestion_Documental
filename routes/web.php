<?php

use app\helpers\Router;
use app\controllers\AuthController;
use app\controllers\DashboardController;
use app\controllers\ExpedienteController;

$router = new Router();

// Auth Routes
$router->add('GET', '/login', [AuthController::class, 'showLogin']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);

// Protected Routes
$router->add('GET', '/', [DashboardController::class, 'index']);
$router->add('GET', '/dashboard', [DashboardController::class, 'index']);

// Expedientes
$router->add('GET', '/expedientes', [ExpedienteController::class, 'index']);
$router->add('GET', '/expedientes/crear', [ExpedienteController::class, 'create']);
$router->add('POST', '/expedientes/guardar', [ExpedienteController::class, 'store']);
$router->add('GET', '/expedientes/editar/{id}', [ExpedienteController::class, 'edit']);
$router->add('POST', '/expedientes/actualizar/{id}', [ExpedienteController::class, 'update']);

// Préstamos
$router->add('GET', '/prestamos', [\app\controllers\PrestamoController::class, 'index']);
$router->add('GET', '/prestamos/solicitar', [\app\controllers\PrestamoController::class, 'create']);
$router->add('POST', '/prestamos/guardar', [\app\controllers\PrestamoController::class, 'store']);
$router->add('GET', '/prestamos/ver-solicitud/{id}', [\app\controllers\PrestamoController::class, 'verSolicitud']);
$router->add('GET', '/prestamos/aprobar/{id}', [\app\controllers\PrestamoController::class, 'aprobarPrestamo']);
$router->add('GET', '/prestamos/entregar/{id}', [\app\controllers\PrestamoController::class, 'entregar']);
$router->add('GET', '/prestamos/solicitar-devolucion/{id}', [\app\controllers\PrestamoController::class, 'entregar']);
$router->add('POST', '/prestamos/procesar-entrega/{id}', [\app\controllers\PrestamoController::class, 'procesarEntrega']);
$router->add('GET', '/prestamos/devolver/{id}', [\app\controllers\PrestamoController::class, 'devolver']);
$router->add('POST', '/prestamos/procesar-devolucion/{id}', [\app\controllers\PrestamoController::class, 'procesarDevolucion']);
$router->add('POST', '/prestamos/rechazar-devolucion/{id}', [\app\controllers\PrestamoController::class, 'rechazarDevolucion']);

// Usuarios
$router->add('GET', '/usuarios', [\app\controllers\UsuarioController::class, 'index']);

// Auditoría
$router->add('GET', '/auditoria', [\app\controllers\AuditoriaController::class, 'index']);

return $router;
