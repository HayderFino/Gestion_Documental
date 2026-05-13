<?php

namespace app\controllers;

class UsuarioController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    public function index() {
        $title = "Gestión de Usuarios";
        $active = "usuarios";
        
        $db = new \app\helpers\JsonDB('usuarios');
        $usuarios = $db->all();

        ob_start();
        ?>
        <div class="top-actions" style="margin-bottom: 1.5rem; display: flex; justify-content: flex-end;">
            <button class="btn btn-primary"><i class="fas fa-user-plus"></i> Nuevo Usuario</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><strong><?= $u['usuario'] ?></strong></td>
                        <td><?= $u['nombre_completo'] ?></td>
                        <td><?= $u['email'] ?></td>
                        <td><?= $u['rol_nombre'] ?></td>
                        <td>
                            <span class="badge badge-<?= ($u['estado'] == 'activo') ? 'success' : 'danger' ?>">
                                <?= ucfirst($u['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-primary" style="padding: 4px 8px;"><i class="fas fa-edit"></i></button>
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
}
