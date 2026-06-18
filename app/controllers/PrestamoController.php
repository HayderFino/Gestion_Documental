<?php

namespace app\controllers;

use app\models\Prestamo;
use app\models\Expediente;
use app\models\Usuario;
use app\config\Database;
use app\services\AuditService;

/**
 * Controlador para la gestión de préstamos y devoluciones de expedientes.
 * Permite solicitar, aprobar, entregar, y devolver expedientes de archivo.
 */
class PrestamoController extends Controller {
    /** @var Prestamo Modelo de datos del préstamo */
    private $prestamoModel;
    /** @var Expediente Modelo de datos del expediente */
    private $expedienteModel;
    /** @var AuditService Servicio para registrar eventos de auditoría */
    private $auditService;

    /**
     * Constructor del controlador.
     * Verifica que exista una sesión activa.
     */
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        $this->prestamoModel = new Prestamo();
        $this->expedienteModel = new Expediente();
        $this->auditService = new AuditService();
    }

    /**
     * Muestra la vista principal de gestión de préstamos.
     * Lista préstamos filtrando por rol (Usuario ve los suyos, Admin ve todos).
     */
    public function index() {
        /** @var string $title Título de la vista */
        $title = "Gestión de Préstamos";
        /** @var string $active Menú activo */
        $active = "prestamos";
        /** @var string $role Rol del usuario actual */
        $role = $_SESSION['user_role'];
        /** @var string $userName Nombre del usuario actual */
        $userName = $_SESSION['user_name'];
        
        /** @var \app\helpers\JsonDB $db Conexión a la base de datos de préstamos */
        $db = new \app\helpers\JsonDB('prestamos');
        /** @var array $allPrestamos Todos los registros de préstamos */
        $allPrestamos = $db->all();

        // Si es usuario, solo ve sus préstamos
        /** @var array $prestamos Arreglo final de préstamos a mostrar */
        if ($role === 'Usuario') {
            $prestamos = array_filter($allPrestamos, function($p) use ($userName) {
                return $p['solicitante_nombre'] === $userName;
            });
        } else {
            $prestamos = $allPrestamos;
        }

        ob_start();
        ?>
        <div class="top-actions">
            <div class="role-badge">
                <span class="badge badge-primary">Rol: <?= $role ?></span>
            </div>
            <?php if ($role === 'Usuario'): ?>
            <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/solicitar" class="btn btn-primary">
                <i class="fas fa-handshake"></i> <span class="btn-text">Solicitar Préstamo</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Expediente</th>
                        <th>Solicitante</th>
                        <th>Fecha Préstamo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prestamos)): ?>
                        <tr><td colspan="6" style="text-align: center;">No hay préstamos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($prestamos as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td><strong><?= $p['numero_expediente'] ?></strong></td>
                        <td><?= $p['solicitante_nombre'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['fecha_prestamo'] ?? $p['created_at'])) ?></td>
                        <td>
                            <?php 
                                $badgeClass = 'warning';
                                $estadoLabel = $p['estado'];
                                if ($p['estado'] == 'devuelto') $badgeClass = 'success';
                                if ($p['estado'] == 'vencido') $badgeClass = 'danger';
                                if ($p['estado'] == 'entregado') $badgeClass = 'info';
                                if ($p['estado'] == 'pendiente_prestamo') { $badgeClass = 'warning'; $estadoLabel = 'Pendiente Préstamo'; }
                                if ($p['estado'] == 'pendiente_devolucion') { $badgeClass = 'warning'; $estadoLabel = 'Pendiente Devolución'; }
                            ?>
                            <span class="badge badge-<?= $badgeClass ?>">
                                <?= ucfirst(str_replace('_', ' ', $estadoLabel)) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <?php if ($role === 'Administrador'): ?>
                                    <?php if ($p['estado'] == 'pendiente_prestamo'): ?>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/ver-solicitud/<?= $p['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--accent-color);" title="Verificar Solicitud">
                                            <i class="fas fa-eye"></i> Verificar
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($p['estado'] == 'pendiente_devolucion'): ?>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/devolver/<?= $p['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--primary-color);" title="Verificar Entrega">
                                            <i class="fas fa-file-signature"></i> Verificar Entrega
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($role === 'Usuario' && $p['estado'] == 'entregado'): ?>
                                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/entregar/<?= $p['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--warning-color);" title="Diligenciar Entrega">
                                        <i class="fas fa-edit"></i> Diligenciar Entrega
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Muestra el detalle de una solicitud pendiente de préstamo al administrador.
     * @param int|string $id ID del préstamo
     */
    public function verSolicitud($id) {
        if ($_SESSION['user_role'] !== 'Administrador') $this->redirect('/prestamos');
        
        /** @var string $title Título de la vista */
        $title = "Verificar Solicitud de Préstamo";
        /** @var string $active Menú activo */
        $active = "prestamos";
        
        /** @var \app\helpers\JsonDB $prestamoDb Conexión a préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var array|null $p Datos del préstamo */
        $p = $prestamoDb->find($id);

        if (!$p || $p['estado'] !== 'pendiente_prestamo') $this->redirect('/prestamos');

        ob_start();
        ?>
        <div class="table-container" style="max-width: 800px; margin: 0 auto;">
            <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">Detalles de la Solicitud</h3>
            
            <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div><strong>Expediente:</strong> <?= $p['numero_expediente'] ?></div>
                <div><strong>Solicitante:</strong> <?= $p['solicitante_nombre'] ?></div>
                <div><strong>Fecha Solicitud:</strong> <?= $p['fecha_solicitud'] ?></div>
                <div><strong>Vínculo:</strong> <?= $p['tipo_vinculacion'] ?></div>
                <div><strong>Línea:</strong> <?= $p['linea_expediente'] ?></div>
                <div><strong>Motivo:</strong> <?= $p['motivo_consulta'] ?></div>
                <div style="grid-column: span 2;"><strong>Observaciones:</strong><br><?= nl2br($p['observaciones'] ?? 'Ninguna') ?></div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="<?= $_ENV['BASE_URL'] ?>/prestamos" class="btn btn-secondary">Regresar</a>
                <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/aprobar/<?= $id ?>" class="btn btn-primary" style="background: var(--success-color);">
                    <i class="fas fa-check-circle"></i> Aprobar y Entregar Expediente
                </a>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Muestra el formulario para solicitar un préstamo de un expediente.
     */
    public function create() {
        if ($_SESSION['user_role'] !== 'Usuario') {
            $this->redirect('/prestamos');
        }
        /** @var string $title Título de la vista */
        $title = "Registrar Préstamo";
        /** @var string $active Menú activo */
        $active = "prestamos";
        
        /** @var \app\helpers\JsonDB $expedienteDb Conexión a expedientes */
        $expedienteDb = new \app\helpers\JsonDB('expedientes');
        /** @var array $allDisponibles Expedientes disponibles */
        $allDisponibles = $expedienteDb->where('estado', 'disponible');
        
        /** @var \app\helpers\JsonDB $asignacionesDb Conexión a asignaciones */
        $asignacionesDb = new \app\helpers\JsonDB('asignaciones');
        /** @var array $misAsignaciones Asignaciones del usuario */
        $misAsignaciones = $asignacionesDb->where('usuario_id', $_SESSION['user_id']);
        /** @var array $misIds IDs de expedientes asignados */
        $misIds = array_column($misAsignaciones, 'expediente_id');
        
        /** @var array $expedientes Expedientes disponibles y asignados al usuario */
        $expedientes = array_filter($allDisponibles, function($exp) use ($misIds) {
            return in_array($exp['id'], $misIds);
        });
        
        /** @var array $lineas Opciones para línea de expediente */
        $lineas = ['Recurso Hídrico', 'Minería y Ecosistemas', 'Residuos e Infraestructura', 'Forestal', 'Fauna', 'No sabe'];
        /** @var array $vinculaciones Opciones de vinculación */
        $vinculaciones = ['Funcionario', 'Contratista'];
        /** @var array $motivos Opciones de motivo de consulta */
        $motivos = [
            'Respuesta Correspondencia', 'Atención Queja', 'Concepto Liquidación', 
            'Auto Liquidación', 'Auto Visita', 'Concepto técnico Visita', 
            'Resolución Seguimiento', 'Resolución Proceso Sancionatorio', 
            'Auto Proceso Sancionatorio', 'Atención a Usuario', 
            'Tramites Urgentes: Tutela, DP otros', 'Otro/Cual'
        ];

        ob_start();
        if (isset($_SESSION['prestamo_error'])) {
            $errorMsg = $_SESSION['prestamo_error'];
            unset($_SESSION['prestamo_error']);
        }
        ?>
        <div class="table-container" style="max-width: 900px; margin: 0 auto;">
            <?php if (!empty($errorMsg)): ?>
                <div class="badge badge-danger" style="margin-bottom: 1rem; display: block; padding: 10px; border-radius: var(--radius-md); background:#f8d7da; color:#721c24;">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>
            <form action="<?= $_ENV['BASE_URL'] ?>/prestamos/guardar" method="POST">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    
                    <div class="form-group">
                        <label>1. Fecha Solicitud *</label>
                        <input type="date" name="fecha_solicitud" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>2. Nombre Completo Solicitante *</label>
                        <input type="text" class="form-control" value="<?= $_SESSION['user_name'] ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>3. Tipo de Vinculación *</label>
                        <select name="tipo_vinculacion" class="form-control" required>
                            <option value="">-- Seleccione vinculación --</option>
                            <?php foreach ($vinculaciones as $v): ?>
                                <option value="<?= $v ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>4. Nº Expediente *</label>
                        <select name="expediente_id" class="form-control" required>
                            <option value="">-- Seleccione el expediente --</option>
                            <?php foreach ($expedientes as $exp): ?>
                                <option value="<?= $exp['id'] ?>">
                                    <?= ($exp['numero_expediente'] ?? $exp['no_orden'] ?? 'S/N') ?> - <?= $exp['titulo'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>5. Línea del Expediente *</label>
                        <select name="linea_expediente" class="form-control" required>
                            <option value="">-- Seleccione la línea --</option>
                            <?php foreach ($lineas as $l): ?>
                                <option value="<?= $l ?>"><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>6. Motivo de Consulta *</label>
                        <select name="motivo_consulta" class="form-control" required id="motivo_select">
                            <option value="">-- Seleccione el motivo --</option>
                            <?php foreach ($motivos as $m): ?>
                                <option value="<?= $m ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>Observaciones / Detalle (Opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="3" placeholder="Si seleccionó 'Otro', especifique aquí..."></textarea>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Solicitud de Préstamo</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Procesa la solicitud POST para guardar un nuevo registro de préstamo (estado pendiente_prestamo).
     */
    public function store() {
        /** @var \app\helpers\JsonDB $prestamoDb BD préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var \app\helpers\JsonDB $expedienteDb BD expedientes */
        $expedienteDb = new \app\helpers\JsonDB('expedientes');
        /** @var \app\helpers\JsonDB $auditDb BD auditoría */
        $auditDb = new \app\helpers\JsonDB('auditoria');

        /** @var string|int $expedienteId ID del expediente solicitado */
        $expedienteId = $_POST['expediente_id'];
        /** @var array $expediente Datos del expediente */
        $expediente = $expedienteDb->find($expedienteId);

        // Validaciones
        $fecha_solicitud = $_POST['fecha_solicitud'] ?? '';
        $today = date('Y-m-d');
        if (strtotime($fecha_solicitud) < strtotime($today)) {
            $_SESSION['prestamo_error'] = 'La fecha de solicitud no puede ser anterior a la fecha actual.';
            $this->redirect('/prestamos/solicitar');
        }

        $data = [
            'expediente_id' => $expedienteId,
            'numero_expediente' => $expediente['numero_expediente'],
            'solicitante_nombre' => $_SESSION['user_name'],
            'usuario_solicitante_id' => $_SESSION['user_id'],
            'fecha_solicitud' => $fecha_solicitud,
            'tipo_vinculacion' => $_POST['tipo_vinculacion'],
            'linea_expediente' => $_POST['linea_expediente'],
            'motivo_consulta' => $_POST['motivo_consulta'],
            'observaciones' => $_POST['observaciones'],
            'estado' => 'pendiente_prestamo'
        ];

        $id = $prestamoDb->create($data);
        
        // Registrar en auditoría
        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'REQUEST_PRESTAMO',
            'tabla' => 'prestamos',
            'registro_id' => $id,
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        $this->redirect('/prestamos');
    }

    /**
     * Aprueba una solicitud de préstamo por un administrador.
     * @param int|string $id ID del préstamo
     */
    public function aprobarPrestamo($id) {
        if ($_SESSION['user_role'] !== 'Administrador') $this->redirect('/prestamos');

        /** @var \app\helpers\JsonDB $prestamoDb BD préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var \app\helpers\JsonDB $expedienteDb BD expedientes */
        $expedienteDb = new \app\helpers\JsonDB('expedientes');
        /** @var \app\helpers\JsonDB $auditDb BD auditoría */
        $auditDb = new \app\helpers\JsonDB('auditoria');

        /** @var array|null $p Datos del préstamo */
        $p = $prestamoDb->find($id);
        if ($p && $p['estado'] == 'pendiente_prestamo') {
            $prestamoDb->update($id, [
                'estado' => 'entregado',
                'fecha_prestamo' => date('Y-m-d H:i:s'),
                'admin_aprueba' => $_SESSION['user_name']
            ]);

            $expedienteDb->update($p['expediente_id'], [
                'estado' => 'prestado',
                'detalle_estado' => strtoupper($p['solicitante_nombre']) . ' (' . date('d/m/Y') . ')'
            ]);

            $auditDb->create([
                'usuario' => $_SESSION['user_name'],
                'accion' => 'APPROVE_PRESTAMO',
                'tabla' => 'prestamos',
                'registro_id' => $id,
                'fecha' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
        }

        $this->redirect('/prestamos');
    }

    /**
     * Muestra el formulario para diligenciar la entrega de un expediente por parte de un usuario.
     * @param int|string $id ID del préstamo
     */
    public function entregar($id) {
        if ($_SESSION['user_role'] !== 'Usuario') $this->redirect('/prestamos');

        /** @var string $title Título de la vista */
        $title = "Diligenciar Entrega de Expediente";
        /** @var string $active Menú activo */
        $active = "prestamos";
        
        /** @var \app\helpers\JsonDB $prestamoDb BD préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var array|null $p Datos del préstamo */
        $p = $prestamoDb->find($id);

        if (!$p || $p['estado'] !== 'entregado' || ($p['usuario_solicitante_id'] ?? null) != $_SESSION['user_id']) {
            $this->redirect('/prestamos');
        }

        /** @var array $tramites Opciones de trámite realizado */
        $tramites = [
            'Concepto Técnico Queja', 'Concepto Liquidación', 'Auto Liquidación',
            'Auto Visita', 'Concepto técnico Visita', 'Resolución Seguimiento',
            'Resolución Proceso Sancionatorio', 'Auto Proceso Sancionatorio',
            'Oficio a Usuario', 'Memorando', 'Informe de Visita', 'Otra/Cual'
        ];

        ob_start();
        if (isset($_SESSION['prestamo_error'])) {
            $errorMsg = $_SESSION['prestamo_error'];
            unset($_SESSION['prestamo_error']);
        }
        ?>
        <div class="table-container" style="max-width: 900px; margin: 0 auto;">
            <?php if (!empty($errorMsg)): ?>
                <div class="badge badge-danger" style="margin-bottom: 1rem; display: block; padding: 10px; border-radius: var(--radius-md); background:#f8d7da; color:#721c24;">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>
            <div style="margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
                <h3 style="color: var(--primary-dark);">Diligenciar Información de Entrega</h3>
                <p>Expediente: <strong><?= $p['numero_expediente'] ?></strong></p>
            </div>
            
            <form action="<?= $_ENV['BASE_URL'] ?>/prestamos/procesar-entrega/<?= $id ?>" method="POST">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    
                    <div class="form-group">
                        <label>1. Fecha (No editable)</label>
                        <input type="date" name="fecha_solicitada" class="form-control" value="<?= date('Y-m-d') ?>" readonly style="background:#f5f7fa; cursor:not-allowed;">
                    </div>

                    <div class="form-group">
                        <label>2. Trámite Realizado *</label>
                        <select name="tramite_realizado" class="form-control" required>
                            <option value="">-- Seleccione el trámite --</option>
                            <?php
                                // Preseleccionar el motivo de consulta si coincide o incluirlo
                                $preMotivo = $p['motivo_consulta'] ?? '';
                                if ($preMotivo && !in_array($preMotivo, $tramites)) {
                                    echo "<option value=\"" . htmlspecialchars($preMotivo) . "\" selected>" . htmlspecialchars($preMotivo) . "</option>";
                                }
                            ?>
                            <?php foreach ($tramites as $t): ?>
                                <option value="<?= $t ?>" <?= ($t === ($p['motivo_consulta'] ?? '')) ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>3. Nº Oficio, Memorando o Actos Administrativos adicionados *</label>
                        <input type="text" name="numero_acto" class="form-control" placeholder="Ej: Res. 2024-123 / Oficio 456" required>
                    </div>

                    <div class="form-group">
                        <label>4. Tomos Entregados *</label>
                        <input type="number" name="tomos_entregados" class="form-control" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label>5. Nº de folios finales *</label>
                        <input type="number" name="folios_recibidos" class="form-control" min="1" required placeholder="Total folios">
                    </div>

                    <div class="form-group">
                        <label>6. Folios Anexos *</label>
                        <input type="number" name="folios_anexos" class="form-control" min="0" value="0" required>
                    </div>

                    <div class="form-group">
                        <label>7. Estado Físico *</label>
                        <select name="estado_fisico" class="form-control" required>
                            <option value="bueno">Bueno</option>
                            <option value="regular">Regular</option>
                            <option value="malo">Malo</option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>8. Observaciones de Entrega</label>
                        <textarea name="observaciones_devolucion" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" style="background: var(--warning-color);">Enviar a Archivo para Verificación</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Procesa la solicitud POST enviada por un usuario para entregar el expediente (cambia a pendiente_devolucion).
     * @param int|string $id ID del préstamo
     */
    public function procesarEntrega($id) {
        if ($_SESSION['user_role'] !== 'Usuario') $this->redirect('/prestamos');

        /** @var \app\helpers\JsonDB $prestamoDb BD préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var array|null $p Datos del préstamo */
        $p = $prestamoDb->find($id);

        if (!$p || $p['estado'] !== 'entregado' || ($p['usuario_solicitante_id'] ?? null) != $_SESSION['user_id']) {
            $this->redirect('/prestamos');
        }

        // Validar campos numéricos y fecha
        $tomos_entregados = $_POST['tomos_entregados'] ?? '';
        $folios_recibidos = $_POST['folios_recibidos'] ?? '';
        $folios_anexos = $_POST['folios_anexos'] ?? '';

        if (!is_numeric($tomos_entregados) || intval($tomos_entregados) < 1) {
            $_SESSION['prestamo_error'] = 'El campo "Tomos Entregados" debe ser un número entero mayor o igual a 1.';
            $this->redirect('/prestamos/entregar/' . $id);
        }
        if (!is_numeric($folios_recibidos) || intval($folios_recibidos) < 1) {
            $_SESSION['prestamo_error'] = 'El campo "Nº de folios finales" debe ser un número entero mayor o igual a 1.';
            $this->redirect('/prestamos/entregar/' . $id);
        }
        if (!is_numeric($folios_anexos) || intval($folios_anexos) < 0) {
            $_SESSION['prestamo_error'] = 'El campo "Folios Anexos" debe ser un número entero mayor o igual a 0.';
            $this->redirect('/prestamos/entregar/' . $id);
        }

        $data = [
            'estado' => 'pendiente_devolucion',
            'datos_entrega' => [
                'tramite_realizado' => $_POST['tramite_realizado'],
                'numero_acto' => $_POST['numero_acto'],
                'tomos_entregados' => intval($tomos_entregados),
                'folios_recibidos' => intval($folios_recibidos),
                'folios_anexos' => intval($folios_anexos),
                'estado_fisico' => $_POST['estado_fisico'],
                'observaciones_devolucion' => $_POST['observaciones_devolucion']
            ]
        ];

        $prestamoDb->update($id, $data);
        $this->redirect('/prestamos');
    }

    /**
     * Muestra la vista al administrador para verificar la devolución de un expediente.
     * @param int|string $id ID del préstamo
     */
    public function devolver($id) {
        if ($_SESSION['user_role'] !== 'Administrador') $this->redirect('/prestamos');

        /** @var string $title Título de la vista */
        $title = "Verificar Entrega de Expediente";
        /** @var string $active Menú activo */
        $active = "prestamos";
        
        /** @var \app\helpers\JsonDB $prestamoDb BD préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var array|null $p Datos del préstamo */
        $p = $prestamoDb->find($id);

        if (!$p || $p['estado'] != 'pendiente_devolucion') $this->redirect('/prestamos');

        /** @var array $d Datos de entrega reportados por el usuario */
        $d = $p['datos_entrega'] ?? [];

        ob_start();
        ?>
        <div class="table-container" style="max-width: 800px; margin: 0 auto;">
            <div style="margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
                <h3 style="color: var(--primary-dark);">Verificación de Recepción Técnica</h3>
                <p>Expediente: <strong><?= $p['numero_expediente'] ?></strong> | Devuelto por: <strong><?= $p['solicitante_nombre'] ?></strong></p>
            </div>
            
            <?php if (empty($d)): ?>
                <div class="alert alert-warning" style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #ffeeba;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Nota:</strong> Este es un registro antiguo o no contiene datos de entrega diligenciados por el usuario. Por favor verifique físicamente.
                </div>
            <?php endif; ?>

            <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; background: #f9f9f9; padding: 1.5rem; border-radius: 8px;">
                <div><strong>Fecha Devolución:</strong> <?= $d['fecha_devolucion'] ?? 'Pendiente' ?></div>
                <div><strong>Trámite Realizado:</strong> <?= $d['tramite_realizado'] ?? 'No especificado' ?></div>
                <div><strong>Actos/Memorandos:</strong> <?= $d['numero_acto'] ?? 'N/A' ?></div>
                <div><strong>Tomos:</strong> <?= $d['tomos_entregados'] ?? 1 ?></div>
                <div><strong>Folios Totales:</strong> <?= $d['folios_recibidos'] ?? 0 ?></div>
                <div><strong>Folios Anexos:</strong> <?= $d['folios_anexos'] ?? 0 ?></div>
                <div><strong>Estado Físico:</strong> <span class="badge badge-info"><?= strtoupper($d['estado_fisico'] ?? 'BUENO') ?></span></div>
                <div style="grid-column: span 2;"><strong>Observaciones del Usuario:</strong><br><?= nl2br($d['observaciones_devolucion'] ?? 'Sin observaciones') ?></div>
            </div>

            <form id="form-verificacion" action="<?= $_ENV['BASE_URL'] ?>/prestamos/procesar-devolucion/<?= $id ?>" method="POST">
                <!-- Campos ocultos para mantener los datos verificados -->
                <!-- fecha_devolucion se asigna al procesar la devolución en archivo -->
                <input type="hidden" name="tramite_realizado" value="<?= $d['tramite_realizado'] ?? 'No especificado' ?>">
                <input type="hidden" name="numero_acto" value="<?= $d['numero_acto'] ?? 'N/A' ?>">
                <input type="hidden" name="tomos_entregados" value="<?= $d['tomos_entregados'] ?? 1 ?>">
                <input type="hidden" name="folios_recibidos" value="<?= $d['folios_recibidos'] ?? 0 ?>">
                <input type="hidden" name="folios_anexos" value="<?= $d['folios_anexos'] ?? 0 ?>">
                <input type="hidden" name="estado_fisico" value="<?= $d['estado_fisico'] ?? 'bueno' ?>">
                <input type="hidden" name="observaciones_devolucion" value="<?= $d['observaciones_devolucion'] ?? '' ?>">

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Observaciones de Verificación (Opcional si confirma, Obligatorio si rechaza):</label>
                    <textarea name="observaciones_admin" id="observaciones_admin" rows="3" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;" placeholder="Escriba aquí por qué no recibe el expediente o cualquier observación técnica..."></textarea>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; align-items: center;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos" class="btn btn-secondary">Solo Salir</a>
                    
                    <button type="button" onclick="rechazarDevolucion()" class="btn btn-primary" style="background: var(--danger-color);">
                        <i class="fas fa-times-circle"></i> Rechazar Recepción
                    </button>

                    <button type="submit" class="btn btn-primary" style="background: var(--success-color);">
                        <i class="fas fa-check-double"></i> Confirmar Recepción y Cerrar Préstamo
                    </button>
                </div>
            </form>

            <script>
            function rechazarDevolucion() {
                const obs = document.getElementById('observaciones_admin').value.trim();
                if (!obs) {
                    alert('Por favor escriba el motivo del rechazo en el campo de observaciones.');
                    document.getElementById('observaciones_admin').focus();
                    return;
                }
                
                if (confirm('¿Está seguro de que desea RECHAZAR esta entrega? El préstamo volverá a estado "Entregado".')) {
                    const form = document.getElementById('form-verificacion');
                    form.action = "<?= $_ENV['BASE_URL'] ?>/prestamos/rechazar-devolucion/<?= $id ?>";
                    form.submit();
                }
            }
            </script>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Procesa la confirmación de devolución del expediente por un administrador (cambia a devuelto y en expediente disponible).
     * @param int|string $id ID del préstamo
     */
    public function procesarDevolucion($id) {
        if ($_SESSION['user_role'] !== 'Administrador') $this->redirect('/prestamos');

        /** @var \app\helpers\JsonDB $prestamoDb BD préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var \app\helpers\JsonDB $expedienteDb BD expedientes */
        $expedienteDb = new \app\helpers\JsonDB('expedientes');
        /** @var \app\helpers\JsonDB $devolucionDb BD devoluciones (historial) */
        $devolucionDb = new \app\helpers\JsonDB('devoluciones');
        /** @var \app\helpers\JsonDB $auditDb BD auditoría */
        $auditDb = new \app\helpers\JsonDB('auditoria');

        /** @var array|null $p Datos del préstamo */
        $p = $prestamoDb->find($id);
        if (!$p) $this->redirect('/prestamos');

        // Forzar que el trámite de la devolución sea el mismo motivo de consulta original si existe
        $tramite_final = $p['motivo_consulta'] ?? ($_POST['tramite_realizado'] ?? 'No especificado');

        $devolucionData = [
            'prestamo_id' => $id,
            'numero_expediente' => $p['numero_expediente'],
            'fecha_devolucion' => date('Y-m-d'),
            'nombre_devuelve' => $p['solicitante_nombre'],
            'tipo_vinculacion' => $p['tipo_vinculacion'],
            'tramite_realizado' => $tramite_final,
            'numero_acto' => $_POST['numero_acto'],
            'tomos_entregados' => intval($_POST['tomos_entregados'] ?? 0),
            'folios_recibidos' => intval($_POST['folios_recibidos'] ?? 0),
            'folios_anexos' => intval($_POST['folios_anexos'] ?? 0),
            'estado_fisico' => $_POST['estado_fisico'],
            'usuario_recibe_archivo' => $_SESSION['user_name'],
            'observaciones' => $_POST['observaciones_devolucion']
        ];
        $devolucionDb->create($devolucionData);

        $prestamoDb->update($id, ['estado' => 'devuelto']);

        $expedienteDb->update($p['expediente_id'], [
            'estado' => 'disponible',
            'tomos' => $_POST['tomos_entregados'],
            'folios' => $_POST['folios_recibidos']
        ]);

        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'RETURN_EXPEDIENTE',
            'tabla' => 'devoluciones',
            'registro_id' => $id,
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);

        $this->redirect('/prestamos');
    }

    /**
     * Procesa el rechazo de la devolución del expediente por el administrador.
     * @param int|string $id ID del préstamo
     */
    public function rechazarDevolucion($id) {
        if ($_SESSION['user_role'] !== 'Administrador') $this->redirect('/prestamos');

        /** @var \app\helpers\JsonDB $prestamoDb BD préstamos */
        $prestamoDb = new \app\helpers\JsonDB('prestamos');
        /** @var \app\helpers\JsonDB $expDb BD expedientes */
        $expDb = new \app\helpers\JsonDB('expedientes');
        /** @var \app\helpers\JsonDB $auditoriaDb BD auditoría */
        $auditoriaDb = new \app\helpers\JsonDB('auditoria');

        /** @var array|null $p Datos del préstamo */
        $p = $prestamoDb->find($id);
        if (!$p) $this->redirect('/prestamos');

        $motivoRechazo = $_POST['observaciones_admin'] ?? 'Sin motivo especificado';

        // Revertir estado del préstamo
        $p['estado'] = 'entregado';
        $p['rechazo_last_reason'] = $motivoRechazo;
        $p['rechazo_at'] = date('Y-m-d H:i:s');
        
        // No borramos datos_entrega para que el usuario pueda ver qué envió, 
        // pero el estado "entregado" le permitirá volver a diligenciarlos.
        
        $prestamoDb->update($id, $p);

        // Auditoría
        $auditoriaDb->create([
            'fecha' => date('Y-m-d H:i:s'),
            'usuario' => $_SESSION['user_name'],
            'accion' => 'RECHAZO_DEVOLUCION',
            'detalles' => "Rechazada entrega de EXP: {$p['numero_expediente']}. Motivo: $motivoRechazo"
        ]);

        $this->redirect('/prestamos');
    }
}
