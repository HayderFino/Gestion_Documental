<?php

namespace app\controllers;

class AuditoriaController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    public function index() {
        $title = "Registro de Auditoría";
        $active = "auditoria";
        
        $db = new \app\helpers\JsonDB('auditoria');
        $logs = array_reverse($db->all()); // Ver los últimos primero

        ob_start();
        ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla/Módulo</th>
                        <th>Dirección IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['fecha'])) ?></td>
                        <td><strong><?= $log['usuario'] ?></strong></td>
                        <td><span class="badge badge-info" style="background: #e1e8ed; color: var(--primary-dark);"><?= $log['accion'] ?></span></td>
                        <td><?= $log['tabla'] ?></td>
                        <td><code><?= $log['ip'] ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }
}
