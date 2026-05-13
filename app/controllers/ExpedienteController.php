<?php

namespace app\controllers;

use app\models\Expediente;
use app\services\AuditService;

class ExpedienteController extends Controller {
    private $expedienteModel;
    private $auditService;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        $this->expedienteModel = new Expediente();
        $this->auditService = new AuditService();
    }

    private function checkAdmin() {
        if ($_SESSION['user_role'] !== 'Administrador') {
            $this->redirect('/expedientes');
        }
    }

    public function index() {
        $title = "Gestión de Expedientes";
        $active = "expedientes";
        
        $db = new \app\helpers\JsonDB('expedientes');
        $expedientes = $db->all();

        ob_start();
        ?>
        <div class="top-actions" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between;">
            <div class="search-box">
                <input type="text" placeholder="Buscar expediente..." class="form-control" style="width: 300px;">
            </div>
            <?php if ($_SESSION['user_role'] === 'Administrador'): ?>
            <a href="<?= $_ENV['BASE_URL'] ?>/expedientes/crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Expediente
            </a>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>N° Expediente</th>
                        <th>Título</th>
                        <th>Ubicación</th>
                        <th>Trámite</th>
                        <th>Estado</th>
                        <th>Tomos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expedientes as $exp): ?>
                    <tr>
                        <td><strong><?= $exp['numero_expediente'] ?></strong></td>
                        <td><?= $exp['titulo'] ?></td>
                        <td><code><?= $exp['ubicacion_fisica'] ?? 'N/A' ?></code></td>
                        <td><?= $exp['tramite_nombre'] ?? 'N/A' ?></td>
                        <td>
                            <span class="badge badge-<?= ($exp['estado'] == 'disponible') ? 'success' : 'warning' ?>">
                                <?= ucfirst($exp['estado']) ?>
                            </span>
                        </td>
                        <td><?= $exp['numero_tomos'] ?></td>
                        <td>
                            <?php if ($_SESSION['user_role'] === 'Administrador'): ?>
                            <a href="<?= $_ENV['BASE_URL'] ?>/expedientes/editar/<?= $exp['id'] ?>" class="btn btn-primary" style="padding: 4px 8px;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-secondary" style="padding: 4px 8px;" disabled title="Solo lectura">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($expedientes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">No hay expedientes registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    public function create() {
        $this->checkAdmin();
        $title = "Crear Nuevo Expediente";
        $active = "expedientes";
        
        ob_start();
        ?>
        <div class="table-container" style="max-width: 800px; margin: 0 auto;">
            <form action="<?= $_ENV['BASE_URL'] ?>/expedientes/guardar" method="POST">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>Número de Expediente</label>
                        <input type="text" name="numero_expediente" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Título / Asunto</label>
                        <input type="text" name="titulo" class="form-control" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Número de Tomos</label>
                        <input type="number" name="numero_tomos" class="form-control" value="1" min="1">
                    </div>
                    <div class="form-group">
                        <label>Foliación Total</label>
                        <input type="number" name="foliacion_total" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Ubicación Física</label>
                        <input type="text" name="ubicacion_fisica" class="form-control" placeholder="Estante/Caja/Carpeta">
                    </div>
                    <div class="form-group">
                        <label>Fecha Apertura</label>
                        <input type="date" name="fecha_apertura" class="form-control">
                    </div>
                </div>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/expedientes" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Expediente</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    public function store() {
        $this->checkAdmin();
        $db = new \app\helpers\JsonDB('expedientes');
        $auditDb = new \app\helpers\JsonDB('auditoria');
        
        $data = [
            'numero_expediente' => $_POST['numero_expediente'],
            'titulo' => $_POST['titulo'],
            'descripcion' => $_POST['descripcion'],
            'numero_tomos' => $_POST['numero_tomos'],
            'foliacion_total' => $_POST['foliacion_total'],
            'ubicacion_fisica' => $_POST['ubicacion_fisica'],
            'fecha_apertura' => $_POST['fecha_apertura'] ?: date('Y-m-d'),
            'estado' => 'disponible',
            'tramite_nombre' => 'General'
        ];

        $id = $db->create($data);
        
        // Registrar en auditoría
        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'CREATE_EXPEDIENTE',
            'tabla' => 'expedientes',
            'registro_id' => $id,
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        $this->redirect('/expedientes');
    }

    public function edit($id) {
        $this->checkAdmin();
        $title = "Editar Expediente";
        $active = "expedientes";
        
        $db = new \app\helpers\JsonDB('expedientes');
        $exp = $db->find($id);

        if (!$exp) $this->redirect('/expedientes');

        ob_start();
        ?>
        <div class="table-container" style="max-width: 800px; margin: 0 auto;">
            <form action="<?= $_ENV['BASE_URL'] ?>/expedientes/actualizar/<?= $id ?>" method="POST">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>Número de Expediente</label>
                        <input type="text" name="numero_expediente" class="form-control" value="<?= $exp['numero_expediente'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Título / Asunto</label>
                        <input type="text" name="titulo" class="form-control" value="<?= $exp['titulo'] ?>" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"><?= $exp['descripcion'] ?? '' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="disponible" <?= ($exp['estado'] == 'disponible') ? 'selected' : '' ?>>Disponible</option>
                            <option value="prestado" <?= ($exp['estado'] == 'prestado') ? 'selected' : '' ?>>Prestado</option>
                            <option value="archivado" <?= ($exp['estado'] == 'archivado') ? 'selected' : '' ?>>Archivado</option>
                            <option value="en_revision" <?= ($exp['estado'] == 'en_revision') ? 'selected' : '' ?>>En Revisión</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ubicación Física</label>
                        <input type="text" name="ubicacion_fisica" class="form-control" value="<?= $exp['ubicacion_fisica'] ?? '' ?>">
                    </div>
                </div>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/expedientes" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    public function update($id) {
        $this->checkAdmin();
        $db = new \app\helpers\JsonDB('expedientes');
        $auditDb = new \app\helpers\JsonDB('auditoria');
        
        $data = [
            'numero_expediente' => $_POST['numero_expediente'],
            'titulo' => $_POST['titulo'],
            'descripcion' => $_POST['descripcion'],
            'estado' => $_POST['estado'],
            'ubicacion_fisica' => $_POST['ubicacion_fisica']
        ];

        $db->update($id, $data);
        
        // Auditoría
        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'UPDATE_EXPEDIENTE',
            'tabla' => 'expedientes',
            'registro_id' => $id,
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        $this->redirect('/expedientes');
    }
}
