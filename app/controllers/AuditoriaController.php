<?php

namespace app\controllers;

/**
 * Controlador para consultar el registro de auditoría del sistema.
 * Permite a los administradores ver quién y cuándo realizó qué acciones.
 */
class AuditoriaController extends Controller {
    
    /**
     * Constructor del controlador.
     * Verifica que el usuario haya iniciado sesión.
     */
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * Muestra la vista principal del registro de auditoría, listando los eventos.
     */
    public function index() {
        /** @var string $title Título de la vista */
        $title = "Registro de Auditoría";
        /** @var string $active Menú activo para el layout */
        $active = "auditoria";
        
        /** @var \app\helpers\JsonDB $db Conexión a la base de datos de auditoría */
        $db = new \app\helpers\JsonDB('auditoria');
        
        /** @var array $logs Arreglo que contiene todos los registros invertidos para mostrar los más recientes primero */
        $logs = array_reverse($db->all());

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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $logItem): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($logItem['fecha'])) ?></td>
                        <td><strong><?= htmlspecialchars($logItem['usuario']) ?></strong></td>
                        <td><span class="badge badge-info" style="background: #e1e8ed; color: var(--primary-dark);"><?= htmlspecialchars($logItem['accion']) ?></span></td>
                        <td><?= htmlspecialchars($logItem['tabla']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        /** @var string $content Contenido HTML generado en el buffer para renderizar la tabla */
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }
}
