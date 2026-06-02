<?php

namespace app\controllers;

class DashboardController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    public function index() {
        $title = "Panel de Control";
        $active = "dashboard";
        $role = $_SESSION['user_role'];
        $userName = $_SESSION['user_name'];
        $userId = $_SESSION['user_id'];
        
        $expDb = new \app\helpers\JsonDB('expedientes');
        $preDb = new \app\helpers\JsonDB('prestamos');
        
        $allPrestamos = $preDb->all();
        
        if ($role !== 'Administrador' && $role !== 'Jefe de Línea') {
            $asigDb = new \app\helpers\JsonDB('asignaciones');
            $misAsignaciones = $asigDb->where('usuario_id', $userId);
            
            $stats = [
                'total_expedientes' => count($misAsignaciones),
                'prestamos_activos' => count(array_filter($allPrestamos, function($p) use ($userId) {
                    return ($p['usuario_solicitante_id'] ?? null) == $userId && $p['estado'] === 'entregado';
                })),
                'solicitudes_pendientes' => count(array_filter($allPrestamos, function($p) use ($userId) {
                    return ($p['usuario_solicitante_id'] ?? null) == $userId && $p['estado'] === 'pendiente_prestamo';
                })),
                'devoluciones_pendientes' => count(array_filter($allPrestamos, function($p) use ($userId) {
                    return ($p['usuario_solicitante_id'] ?? null) == $userId && $p['estado'] === 'pendiente_devolucion';
                }))
            ];
        } else {
            $stats = [
                'total_expedientes' => count($expDb->all()),
                'prestamos_activos' => count($preDb->where('estado', 'entregado')),
                'solicitudes_pendientes' => count($preDb->where('estado', 'pendiente_prestamo')),
                'devoluciones_pendientes' => count($preDb->where('estado', 'pendiente_devolucion'))
            ];
        }

        $targetActivos = ($role === 'Administrador') ? '#dashboard-activos' : '#dashboard-mis-expedientes';
        $targetSolicitudes = ($role === 'Administrador') ? '#dashboard-solicitudes' : '#dashboard-mis-expedientes';
        $targetDevoluciones = ($role === 'Administrador') ? '#dashboard-devoluciones' : '#dashboard-mis-expedientes';

        ob_start();
        ?>
        <div class="stats-grid">
            <a href="<?= $targetActivos ?>" class="stat-card stat-card-link">
                <div class="label">Total Expedientes</div>
                <div class="value"><?= number_format($stats['total_expedientes']) ?></div>
                <i class="fas fa-file-archive" style="color: var(--primary-color);"></i>
            </a>
            <a href="<?= $targetActivos ?>" class="stat-card stat-card-link" style="border-left-color: var(--info-color);">
                <div class="label">Préstamos Activos</div>
                <div class="value"><?= $stats['prestamos_activos'] ?></div>
                <i class="fas fa-hand-holding" style="color: var(--info-color);"></i>
            </a>
            <a href="<?= $targetSolicitudes ?>" class="stat-card stat-card-link" style="border-left-color: var(--warning-color);">
                <div class="label">Solicitudes Pendientes</div>
                <div class="value"><?= $stats['solicitudes_pendientes'] ?></div>
                <i class="fas fa-clock" style="color: var(--warning-color);"></i>
            </a>
            <a href="<?= $targetDevoluciones ?>" class="stat-card stat-card-link" style="border-left-color: var(--success-color);">
                <div class="label">Devoluciones x Confirmar</div>
                <div class="value"><?= $stats['devoluciones_pendientes'] ?></div>
                <i class="fas fa-undo" style="color: var(--success-color);"></i>
            </a>
        </div>

        <?php if ($role === 'Administrador'): ?>
            <div class="dashboard-sections" style="display: flex; flex-direction: column; gap: 2rem; margin-top: 2rem;">
                <!-- Solicitudes Pendientes -->
                <div id="dashboard-solicitudes" tabindex="-1" class="table-container">
                    <h3 style="margin-bottom: 1rem;"><i class="fas fa-bell"></i> Solicitudes por Aprobar</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Expediente</th>
                                <th>Solicitante</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pendientes = $preDb->where('estado', 'pendiente_prestamo');
                            if (empty($pendientes)): ?>
                                <tr><td colspan="3" style="text-align: center;">No hay solicitudes.</td></tr>
                            <?php endif;
                            foreach ($pendientes as $p): ?>
                            <tr>
                                <td><strong><?= $p['numero_expediente'] ?></strong></td>
                                <td><?= $p['solicitante_nombre'] ?></td>
                                <td>
                                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/ver-solicitud/<?= $p['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--accent-color);" title="Verificar Solicitud">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Devoluciones por Verificar -->
                <div id="dashboard-devoluciones" tabindex="-1" class="table-container">
                    <h3 style="margin-bottom: 1rem;"><i class="fas fa-file-signature"></i> Verificar Devoluciones</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Expediente</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $devoluciones = $preDb->where('estado', 'pendiente_devolucion');
                            if (empty($devoluciones)): ?>
                                <tr><td colspan="3" style="text-align: center;">No hay entregas pendientes.</td></tr>
                            <?php endif;
                            foreach ($devoluciones as $p): ?>
                            <tr>
                                <td><strong><?= $p['numero_expediente'] ?></strong></td>
                                <td><?= $p['solicitante_nombre'] ?></td>
                                <td>
                                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/devolver/<?= $p['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--success-color);" title="Verificar Entrega">
                                        <i class="fas fa-check-double"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Quién tiene qué -->
                <div id="dashboard-activos" tabindex="-1" class="table-container">
                    <h3 style="margin-bottom: 1rem;"><i class="fas fa-map-marker-alt"></i> Ubicación de Expedientes</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Expediente</th>
                                <th>Poseedor</th>
                                <th>Estado Actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $activos = array_filter($allPrestamos, function($p) {
                                return $p['estado'] === 'entregado' || $p['estado'] === 'pendiente_devolucion';
                            });
                            if (empty($activos)): ?>
                                <tr><td colspan="3" style="text-align: center;">Todos están en archivo.</td></tr>
                            <?php endif;
                            foreach ($activos as $p): ?>
                            <tr>
                                <td><strong><?= $p['numero_expediente'] ?></strong></td>
                                <td><?= $p['solicitante_nombre'] ?></td>
                                <td>
                                    <?php if ($p['estado'] === 'entregado'): ?>
                                        <span class="badge badge-info">En poder del usuario</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">En proceso de entrega</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Vista para Usuario -->
            <div id="dashboard-mis-expedientes" tabindex="-1" class="table-container" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem;"><i class="fas fa-book-reader"></i> Mis Expedientes Actuales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Expediente</th>
                            <th>Fecha Préstamo</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $misPrestamos = array_filter($allPrestamos, function($p) use ($userId) {
                            return ($p['usuario_solicitante_id'] ?? null) == $userId && ($p['estado'] == 'entregado' || $p['estado'] == 'pendiente_devolucion');
                        });
                        if (empty($misPrestamos)): ?>
                            <tr><td colspan="4" style="text-align: center;">No tienes expedientes en tu poder.</td></tr>
                        <?php endif;
                        foreach ($misPrestamos as $p): ?>
                        <tr>
                            <td><strong><?= $p['numero_expediente'] ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_prestamo'] ?? '')) ?></td>
                            <td>
                                <span class="badge badge-<?= $p['estado'] == 'entregado' ? 'info' : 'warning' ?>">
                                    <?= $p['estado'] == 'entregado' ? 'En mi poder' : 'Entrega solicitada' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['estado'] == 'entregado'): ?>
                                    <a href="<?= $_ENV['BASE_URL'] ?>/prestamos/solicitar-devolucion/<?= $p['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; background: var(--warning-color);">
                                        <i class="fas fa-undo"></i> Entregar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.stat-card-link').forEach(function(card) {
                    card.addEventListener('click', function(event) {
                        var target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            event.preventDefault();
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            target.focus({ preventScroll: true });
                        }
                    });
                });
            });
        </script>
        <?php
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }
}
