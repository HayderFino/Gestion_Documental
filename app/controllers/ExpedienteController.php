<?php

namespace app\controllers;

use app\models\Expediente;
use app\services\AuditService;

/**
 * Controlador para la gestión completa de expedientes.
 * Permite crear, editar, asignar, y visualizar expedientes según el rol del usuario.
 */
class ExpedienteController extends Controller
{
    /** @var Expediente Modelo de datos del expediente */
    private $expedienteModel;
    
    /** @var AuditService Servicio para registrar auditorías de acciones */
    private $auditService;

    /**
     * Constructor del controlador.
     * Verifica la sesión activa del usuario y carga servicios necesarios.
     */
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        $this->expedienteModel = new Expediente();
        $this->auditService = new AuditService();
    }

    /**
     * Verifica si el usuario actual es Administrador.
     * Si no lo es, redirige al listado de expedientes.
     */
    private function checkAdmin()
    {
        if ($_SESSION['user_role'] !== 'Administrador') {
            $this->redirect('/expedientes');
        }
    }

    /**
     * Muestra la vista principal de gestión de expedientes.
     * Lista todos los expedientes, filtrando si el usuario es estándar.
     */
    public function index()
    {
        /** @var string $title Título de la vista */
        $title = "Gestión de Expedientes";
        /** @var string $active Menú activo */
        $active = "expedientes";

        /** @var \app\helpers\JsonDB $db Conexión DB expedientes */
        $db = new \app\helpers\JsonDB('expedientes');
        /** @var array $allExpedientes Todos los expedientes */
        $allExpedientes = $db->all();

        /** @var string $role Rol del usuario */
        $role = $_SESSION['user_role'];
        /** @var int|string $userId ID del usuario actual */
        $userId = $_SESSION['user_id'];
        
        /** @var \app\helpers\JsonDB $asignacionesDb DB asignaciones */
        $asignacionesDb = new \app\helpers\JsonDB('asignaciones');
        /** @var array $allAsignaciones Todas las asignaciones */
        $allAsignaciones = $asignacionesDb->all();

        // Mapear usuarios para mostrar nombres
        /** @var \app\helpers\JsonDB $usuariosDb DB usuarios */
        $usuariosDb = new \app\helpers\JsonDB('usuarios');
        /** @var array $allUsuarios Todos los usuarios */
        $allUsuarios = $usuariosDb->all();
        $usuariosMap = [];
        foreach ($allUsuarios as $u) {
            $usuariosMap[$u['id']] = $u['nombre_completo'];
        }

        // Agrupar asignados por expediente_id
        $expedienteAsignados = [];
        foreach ($allAsignaciones as $asig) {
            $eId = $asig['expediente_id'];
            $uId = $asig['usuario_id'];
            if (isset($usuariosMap[$uId])) {
                $expedienteAsignados[$eId][] = $usuariosMap[$uId];
            }
        }

        // Restricción: Si no es Admin o Jefe de Línea, sólo ver expedientes asignados
        if ($role !== 'Administrador' && $role !== 'Jefe de Línea') {
            $misAsignaciones = array_filter($allAsignaciones, function($asig) use ($userId) {
                return $asig['usuario_id'] == $userId;
            });
            $misIds = array_column($misAsignaciones, 'expediente_id');
            $expedientes = array_filter($allExpedientes, function($exp) use ($misIds) {
                return in_array($exp['id'], $misIds);
            });
        } else {
            $expedientes = $allExpedientes;
        }

        ob_start();
        ?>
        <div class="top-actions">
            <div class="search-box">
                <input type="text" placeholder="Buscar expediente..." class="form-control">
            </div>
            <?php if ($role === 'Administrador'): ?>
                <a href="<?= $_ENV['BASE_URL'] ?>/expedientes/crear" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <span class="btn-text">Nuevo Expediente</span>
                </a>
            <?php endif; ?>
        </div>


        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Expediente</th>
                        <th>Asunto / Serie</th>
                        <th>Ubicación</th>
                        <th>Código</th>
                        <th>Estado</th>
                        <th>N° Orden</th>
                        <?php if ($role === 'Administrador' || $role === 'Jefe de Línea'): ?>
                            <th>Asignado a</th>
                        <?php endif; ?>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expedientes as $exp): ?>
                        <tr>
                            <td><strong><?= $exp['numero_expediente'] ?? 'N/A' ?></strong></td>
                            <td><?= $exp['titulo'] ?></td>
                            <td><code><?= $exp['ubicacion_fisica'] ?? 'N/A' ?></code></td>
                            <td><?= $exp['codigo'] ?? 'N/A' ?></td>
                            <td>
                                <span
                                    class="badge badge-<?= (stripos($exp['estado'] ?? '', 'Prestado') === false) ? 'success' : 'warning' ?>">
                                    <?= $exp['estado'] ?? 'Disponible' ?>
                                </span>
                            </td>
                            <td><?= $exp['no_orden'] ?? 0 ?></td>
                            <?php if ($role === 'Administrador' || $role === 'Jefe de Línea'): ?>
                                <td>
                                    <?php 
                                    $nombresAsignados = $expedienteAsignados[$exp['id']] ?? [];
                                    if (!empty($nombresAsignados)): 
                                        echo implode(', ', $nombresAsignados);
                                    else: ?>
                                        <span class="badge badge-secondary" style="background: #e2e8f0; color: #4a5568; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <?php if ($role === 'Administrador'): ?>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/expedientes/editar/<?= $exp['id'] ?>" class="btn btn-primary"
                                            style="padding: 4px 8px;" title="Editar Expediente">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($role === 'Administrador' || $role === 'Jefe de Línea'): ?>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/expedientes/asignar/<?= $exp['id'] ?>" class="btn btn-primary"
                                            style="padding: 4px 8px; background: var(--success-color);" title="Asignar Usuarios">
                                            <i class="fas fa-user-tag"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($role !== 'Administrador' && $role !== 'Jefe de Línea'): ?>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/expedientes/ver/<?= $exp['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px;" title="Ver expediente">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($expedientes)): ?>
                        <tr>
                            <td colspan="<?= ($role === 'Administrador' || $role === 'Jefe de Línea') ? 8 : 7 ?>" style="text-align: center; padding: 2rem;">No hay expedientes registrados o asignados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        $content = ob_get_clean();

        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Muestra el detalle de un expediente específico.
     * @param int|string $id Identificador del expediente
     */
    public function ver($id)
    {
        /** @var string $title Título de la vista */
        $title = "Ver Expediente";
        /** @var string $active Menú activo */
        $active = "expedientes";

        /** @var \app\helpers\JsonDB $db DB expedientes */
        $db = new \app\helpers\JsonDB('expedientes');
        /** @var array|null $exp Datos del expediente */
        $exp = $db->find($id);

        if (!$exp) {
            $this->redirect('/expedientes');
        }

        $asignacionesDb = new \app\helpers\JsonDB('asignaciones');
        $asignaciones = $asignacionesDb->where('expediente_id', $id);

        if ($_SESSION['user_role'] !== 'Administrador' && $_SESSION['user_role'] !== 'Jefe de Línea') {
            $allowed = false;
            foreach ($asignaciones as $asig) {
                if ($asig['usuario_id'] == $_SESSION['user_id']) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                $this->redirect('/expedientes');
            }
        }

        $usuariosDb = new \app\helpers\JsonDB('usuarios');
        $allUsuarios = $usuariosDb->all();
        $usuariosMap = [];
        foreach ($allUsuarios as $u) {
            $usuariosMap[$u['id']] = $u['nombre_completo'];
        }

        $usuariosAsignados = [];
        foreach ($asignaciones as $asig) {
            if (isset($usuariosMap[$asig['usuario_id']])) {
                $usuariosAsignados[] = $usuariosMap[$asig['usuario_id']];
            }
        }

        ob_start();
        ?>
        <div class="table-container" style="max-width: 900px; margin: 0 auto; padding: 2rem;">
            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Detalle de Expediente</h3>
            <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div><strong>Expediente:</strong> <?= htmlspecialchars($exp['numero_expediente'] ?? 'N/A') ?></div>
                <div><strong>Asunto / Serie:</strong> <?= htmlspecialchars($exp['titulo'] ?? '') ?></div>
                <div><strong>Ubicación Física:</strong> <?= htmlspecialchars($exp['ubicacion_fisica'] ?? 'N/A') ?></div>
                <div><strong>Código:</strong> <?= htmlspecialchars($exp['codigo'] ?? 'N/A') ?></div>
                <div><strong>Estado:</strong> <?= htmlspecialchars($exp['estado'] ?? 'Disponible') ?></div>
                <div><strong>N° Orden:</strong> <?= htmlspecialchars($exp['no_orden'] ?? 'N/A') ?></div>
                <div><strong>Descripción:</strong> <?= nl2br(htmlspecialchars($exp['descripcion'] ?? 'Sin descripción')) ?></div>
                <div><strong>Asignados:</strong> <?= !empty($usuariosAsignados) ? htmlspecialchars(implode(', ', $usuariosAsignados)) : '<span style="color:#6b7280;">Sin asignar</span>' ?></div>
            </div>
            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="<?= $_ENV['BASE_URL'] ?>/expedientes" class="btn btn-secondary">Regresar</a>
            </div>
        </div>
        <?php
        $content = ob_get_clean();

        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Muestra el formulario para registrar un nuevo expediente.
     */
    public function create()
    {
        $this->checkAdmin();
        /** @var string $title Título de la vista */
        $title = "Crear Nuevo Expediente";
        /** @var string $active Menú activo */
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
                        <input type="number" name="tomos" class="form-control" value="1" min="1">
                    </div>
                    <div class="form-group">
                        <label>Foliación Total</label>
                        <input type="number" name="folios" class="form-control" value="0" min="0">
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

    /**
     * Procesa la solicitud POST para guardar el nuevo expediente en la DB.
     */
    public function store()
    {
        $this->checkAdmin();
        /** @var \app\helpers\JsonDB $db Conexión BD expedientes */
        $db = new \app\helpers\JsonDB('expedientes');
        /** @var \app\helpers\JsonDB $auditDb Conexión BD auditoría */
        $auditDb = new \app\helpers\JsonDB('auditoria');

        $data = [
            'numero_expediente' => $_POST['numero_expediente'],
            'titulo' => $_POST['titulo'],
            'descripcion' => $_POST['descripcion'],
            'tomos' => intval($_POST['tomos'] ?? 1),
            'folios' => intval($_POST['folios'] ?? 0),
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

    /**
     * Muestra el formulario de edición (Inventario Técnico) de un expediente.
     * @param int|string $id Identificador del expediente a editar
     */
    public function edit($id)
    {
        $this->checkAdmin();
        /** @var string $title Título de la vista */
        $title = "Editar Expediente";
        /** @var string $active Menú activo */
        $active = "expedientes";

        /** @var \app\helpers\JsonDB $db BD expedientes */
        $db = new \app\helpers\JsonDB('expedientes');
        /** @var array|null $exp Datos del expediente */
        $exp = $db->find($id);

        if (!$exp)
            $this->redirect('/expedientes');

        ob_start();
        ?>
        <div class="table-container" style="max-width: 1100px; margin: 0 auto;">
            <h3
                style="margin-bottom: 2rem; color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 0.5rem;">
                <i class="fas fa-file-invoice"></i> Inventario Técnico de Expediente
            </h3>

            <form action="<?= $_ENV['BASE_URL'] ?>/expedientes/actualizar/<?= $id ?>" method="POST">
                <!-- Campos Generales (Sin categoría) -->
                <div class="form-grid"
                    style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.2rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>No. de Orden</label>
                        <input type="text" name="no_orden" class="form-control" value="<?= $exp['no_orden'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" name="codigo" class="form-control" value="<?= $exp['codigo'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Nombre de las Series, subseries o asuntos</label>
                        <input type="text" name="titulo" class="form-control" value="<?= $exp['titulo'] ?>" required>
                    </div>
                </div>

                <!-- Fechas Extremas -->
                <h4
                    style="margin: 1.5rem 0 0.5rem; color: var(--secondary-color); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                    Fechas Extremas</h4>
                <div class="form-grid"
                    style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; border-left: 3px solid #3498db; padding-left: 1rem; margin-bottom: 1.5rem; background: #fcfcfc; padding-top: 1rem; padding-bottom: 1rem;">
                    <div class="form-group">
                        <label>Inicial</label>
                        <input type="date" name="fecha_inicial" class="form-control" value="<?= $exp['fecha_inicial'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Final</label>
                        <input type="date" name="fecha_final" class="form-control" value="<?= $exp['fecha_final'] ?? '' ?>">
                    </div>
                </div>

                <!-- Unidad de Conservación -->
                <h4
                    style="margin: 1.5rem 0 0.5rem; color: var(--secondary-color); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                    Unidad de Conservación</h4>
                <div class="form-grid"
                    style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; border-left: 3px solid #2ecc71; padding-left: 1rem; margin-bottom: 1.5rem; background: #fcfcfc; padding-top: 1rem; padding-bottom: 1rem;">
                    <div class="form-group">
                        <label>Caja</label>
                        <input type="text" name="caja" class="form-control" value="<?= $exp['caja'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Carpeta</label>
                        <input type="text" name="carpeta" class="form-control" value="<?= $exp['carpeta'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Libro</label>
                        <input type="text" name="libro" class="form-control" value="<?= $exp['libro'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Otro / Anexo</label>
                        <input type="text" name="otro_anexo" class="form-control" value="<?= $exp['otro_anexo'] ?? '' ?>">
                    </div>
                </div>

                <!-- Campos de Cuantificación (Sin categoría) -->
                <div class="form-grid"
                    style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>No. de Folios</label>
                        <input type="number" name="folios" class="form-control" value="<?= $exp['folios'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <label>Tomo</label>
                        <input type="number" name="tomos" class="form-control" value="<?= $exp['tomos'] ?? 1 ?>">
                    </div>
                    <div class="form-group">
                        <label>Soporte</label>
                        <input type="text" name="soporte" class="form-control" value="<?= $exp['soporte'] ?? 'Papel' ?>">
                    </div>
                    <div class="form-group">
                        <label>Frecuencia de Consulta</label>
                        <select name="frecuencia_consulta" class="form-control">
                            <option value="Alta" <?= ($exp['frecuencia_consulta'] ?? '') == 'Alta' ? 'selected' : '' ?>>Alta
                            </option>
                            <option value="Media" <?= ($exp['frecuencia_consulta'] ?? '') == 'Media' ? 'selected' : '' ?>>Media
                            </option>
                            <option value="Baja" <?= ($exp['frecuencia_consulta'] ?? '') == 'Baja' ? 'selected' : '' ?>>Baja
                            </option>
                        </select>
                    </div>
                </div>

                <!-- CITA -->
                <h4
                    style="margin: 1.5rem 0 0.5rem; color: var(--secondary-color); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                    CITA</h4>
                <div class="form-grid"
                    style="display: grid; grid-template-columns: 1fr; gap: 1rem; border-left: 3px solid #9b59b6; padding-left: 1rem; margin-bottom: 1.5rem; background: #fcfcfc; padding-top: 1rem; padding-bottom: 1rem;">
                    <div class="form-group">
                        <label>Expediente-CITA</label>
                        <input type="text" name="expediente_cita" class="form-control"
                            value="<?= $exp['expediente_cita'] ?? '' ?>">
                    </div>
                </div>

                <!-- Notas -->
                <h4
                    style="margin: 1.5rem 0 0.5rem; color: var(--secondary-color); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                    Notas</h4>
                <div class="form-grid"
                    style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; border-left: 3px solid #95a5a6; padding-left: 1rem; margin-bottom: 1.5rem; background: #fcfcfc; padding-top: 1rem; padding-bottom: 1rem;">
                    <div class="form-group">
                        <label>Expediente</label>
                        <input type="text" name="numero_expediente" class="form-control"
                            value="<?= $exp['numero_expediente'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Interesado</label>
                        <input type="text" name="interesado" class="form-control" value="<?= $exp['interesado'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Municipio</label>
                        <input type="text" name="municipio" class="form-control" value="<?= $exp['municipio'] ?? '' ?>">
                    </div>
                </div>

                <!-- CONTROL ARCHIVO (Al final por solicitud del usuario) -->
                <h4
                    style="margin: 1.5rem 0 0.5rem; color: var(--secondary-color); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                    Control Archivo</h4>
                <div class="form-grid"
                    style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; border-left: 3px solid #e67e22; padding-left: 1rem; margin-bottom: 1.5rem; background: #fcfcfc; padding-top: 1rem; padding-bottom: 1rem;">
                    <div class="form-group">
                        <label>Prestamo</label>
                        <input type="text" name="estado" class="form-control" value="<?= $exp['estado'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Ubicación</label>
                        <input type="text" name="ubicacion_fisica" class="form-control"
                            value="<?= $exp['ubicacion_fisica'] ?? '' ?>">
                    </div>
                </div>

                <div
                    style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #eee;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/expedientes" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Procesa la actualización del expediente por POST.
     * @param int|string $id ID del expediente
     */
    public function update($id)
    {
        $this->checkAdmin();
        /** @var \app\helpers\JsonDB $db BD expedientes */
        $db = new \app\helpers\JsonDB('expedientes');
        /** @var \app\helpers\JsonDB $auditDb BD auditoría */
        $auditDb = new \app\helpers\JsonDB('auditoria');

        $data = [
            'no_orden' => $_POST['no_orden'] ?? '',
            'codigo' => $_POST['codigo'] ?? '',
            'titulo' => $_POST['titulo'],
            'fecha_inicial' => $_POST['fecha_inicial'] ?? '',
            'fecha_final' => $_POST['fecha_final'] ?? '',
            'caja' => $_POST['caja'] ?? '',
            'carpeta' => $_POST['carpeta'] ?? '',
            'libro' => $_POST['libro'] ?? '',
            'otro_anexo' => $_POST['otro_anexo'] ?? '',
            'folios' => $_POST['folios'] ?? 0,
            'tomos' => $_POST['tomos'] ?? 1,
            'soporte' => $_POST['soporte'] ?? 'Papel',
            'frecuencia_consulta' => $_POST['frecuencia_consulta'] ?? 'Media',
            'ubicacion_fisica' => $_POST['ubicacion_fisica'] ?? '',
            'expediente_cita' => $_POST['expediente_cita'] ?? '',
            'numero_expediente' => $_POST['numero_expediente'],
            'interesado' => $_POST['interesado'] ?? '',
            'municipio' => $_POST['municipio'] ?? '',
            'estado' => $_POST['estado'] ?? 'disponible'
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

    /**
     * Muestra la vista para asignar acceso al expediente a uno o más usuarios.
     * @param int|string $id ID del expediente
     */
    public function asignar($id)
    {
        /** @var string $role Rol del usuario actual */
        $role = $_SESSION['user_role'];
        if ($role !== 'Administrador' && $role !== 'Jefe de Línea') {
            $this->redirect('/expedientes');
        }

        /** @var string $title Título de la vista */
        $title = "Asignar Expediente";
        /** @var string $active Menú activo */
        $active = "expedientes";

        $db = new \app\helpers\JsonDB('expedientes');
        $exp = $db->find($id);

        if (!$exp) {
            $this->redirect('/expedientes');
        }

        // Cargar todos los usuarios con rol 'Usuario'
        $usuariosDb = new \app\helpers\JsonDB('usuarios');
        $usuarios = array_filter($usuariosDb->all(), function($u) {
            return $u['rol_nombre'] === 'Usuario' && $u['estado'] === 'activo';
        });

        // Cargar asignaciones actuales de este expediente
        $asignacionesDb = new \app\helpers\JsonDB('asignaciones');
        $asignaciones = $asignacionesDb->where('expediente_id', $id);
        $usuariosAsignadosIds = array_column($asignaciones, 'usuario_id');

        ob_start();
        ?>
        <div class="table-container" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
            <div style="margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
                <h3 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                    <i class="fas fa-user-tag"></i> Asignar Usuarios al Expediente
                </h3>
                <p style="color: var(--text-muted);">
                    Expediente: <strong><?= htmlspecialchars($exp['numero_expediente']) ?></strong><br>
                    Asunto: <?= htmlspecialchars($exp['titulo']) ?>
                </p>
            </div>

            <form action="<?= $_ENV['BASE_URL'] ?>/expedientes/asignar/<?= $id ?>" method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 1rem; color: var(--primary-dark);">
                        Seleccione los usuarios que tendrán acceso a este expediente:
                    </label>
                    
                    <?php if (empty($usuarios)): ?>
                        <div class="alert alert-warning" style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: var(--radius-md);">
                            No hay usuarios activos registrados en el sistema con el rol "Usuario".
                        </div>
                    <?php else: ?>
                        <input type="text" id="user-search" placeholder="Buscar por nombre, documento, correo o usuario..." class="form-control" style="width:100%; margin-bottom:0.75rem; padding:0.5rem;">
                        <div id="users-list" style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            <?php foreach ($usuarios as $u): ?>
                                <?php $documento = trim($u['numero_documento'] ?? ''); ?>
                                <label data-search="<?= htmlspecialchars($u['nombre_completo'] . ' ' . $u['email'] . ' ' . $u['usuario'] . ' ' . $documento) ?>" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px; border-radius: var(--radius-sm); transition: var(--transition);" class="checkbox-label">
                                    <input type="checkbox" name="usuarios[]" value="<?= $u['id'] ?>" 
                                        <?= in_array($u['id'], $usuariosAsignadosIds) ? 'checked' : '' ?>
                                        style="width: 18px; height: 18px; cursor: pointer;">
                                    <div>
                                        <strong style="display: block;"><?= htmlspecialchars($u['nombre_completo']) ?><?php if ($documento !== ''): ?> <span style="font-weight:600; color:#6b7280; font-size:0.85rem;">(<?= htmlspecialchars($documento) ?>)</span><?php endif; ?></strong>
                                        <span style="font-size: 0.85rem; color: var(--text-muted); display: block;"><?= htmlspecialchars($u['email']) ?> (<?= htmlspecialchars($u['usuario']) ?>)</span>
                                        <?php if ($documento !== ''): ?>
                                            <span style="font-size: 0.82rem; color: #4a5568;">Documento: <?= htmlspecialchars($documento) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div id="no-results" style="display:none; padding:1rem; color:var(--text-muted); text-align:center;">No se encontraron usuarios.</div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var input = document.getElementById('user-search');
                                var list = document.getElementById('users-list');
                                if (!input || !list) return;
                                var labels = Array.prototype.slice.call(list.querySelectorAll('.checkbox-label'));

                                function filter() {
                                    var q = input.value.toLowerCase().trim();
                                    var anyVisible = false;
                                    labels.forEach(function(lbl){
                                        var text = lbl.dataset.search ? lbl.dataset.search.toLowerCase() : lbl.textContent.toLowerCase();
                                        var show = q === '' || text.indexOf(q) !== -1;
                                        lbl.style.display = show ? 'flex' : 'none';
                                        if (show) anyVisible = true;
                                    });
                                    document.getElementById('no-results').style.display = anyVisible ? 'none' : 'block';
                                }

                                input.addEventListener('input', filter);
                            });
                        </script>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 1.5rem;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/expedientes" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" style="background: var(--success-color);">
                        <i class="fas fa-save"></i> Guardar Asignación
                    </button>
                </div>
            </form>
        </div>
        
        <style>
            .checkbox-label:hover {
                background-color: rgba(26, 79, 139, 0.05);
            }
        </style>
        <?php
        $content = ob_get_clean();

        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Procesa la solicitud POST para guardar las asignaciones de usuarios a un expediente.
     * @param int|string $id ID del expediente
     */
    public function guardarAsignacion($id)
    {
        /** @var string $role Rol actual */
        $role = $_SESSION['user_role'];
        if ($role !== 'Administrador' && $role !== 'Jefe de Línea') {
            $this->redirect('/expedientes');
        }

        $db = new \app\helpers\JsonDB('expedientes');
        $exp = $db->find($id);

        if (!$exp) {
            $this->redirect('/expedientes');
        }

        $asignacionesDb = new \app\helpers\JsonDB('asignaciones');
        $auditDb = new \app\helpers\JsonDB('auditoria');

        // Obtener usuarios seleccionados
        $selectedUserIds = $_POST['usuarios'] ?? [];

        // Obtener asignaciones actuales para este expediente
        $todasAsignaciones = $asignacionesDb->all();

        // Cargar nombres de usuarios para auditoría
        $usuariosDb = new \app\helpers\JsonDB('usuarios');
        $allUsuarios = $usuariosDb->all();
        $usuariosMap = [];
        foreach ($allUsuarios as $u) {
            $usuariosMap[$u['id']] = $u['nombre_completo'];
        }

        // Filtrar las asignaciones de otros expedientes, quitando las actuales de este
        $nuevasAsignaciones = array_filter($todasAsignaciones, function($asig) use ($id) {
            return $asig['expediente_id'] != $id;
        });

        // Agregar las nuevas asignaciones
        $nuevosNombres = [];
        foreach ($selectedUserIds as $uId) {
            $nuevasAsignaciones[] = [
                'id' => count($nuevasAsignaciones) > 0 ? max(array_column($nuevasAsignaciones, 'id')) + 1 : 1,
                'expediente_id' => intval($id),
                'usuario_id' => intval($uId),
                'asignado_por' => $_SESSION['user_name'],
                'fecha_asignacion' => date('Y-m-d H:i:s')
            ];
            if (isset($usuariosMap[$uId])) {
                $nuevosNombres[] = $usuariosMap[$uId];
            }
        }

        // Guardar en asignaciones.json
        $asignacionesDb->save(array_values($nuevasAsignaciones));

        // Registrar auditoría
        $detallesAsignacion = empty($nuevosNombres) ? "Ninguno (Desasignados todos)" : implode(', ', $nuevosNombres);
        $auditDb->create([
            'usuario' => $_SESSION['name'] ?? $_SESSION['user_name'],
            'accion' => 'ASSIGN_EXPEDIENTE',
            'tabla' => 'asignaciones',
            'registro_id' => intval($id),
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'detalles' => "Expediente {$exp['numero_expediente']} asignado a: {$detallesAsignacion}"
        ]);

        $this->redirect('/expedientes');
    }
}
