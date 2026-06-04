<?php

namespace app\controllers;

class UsuarioController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        if ($_SESSION['user_role'] !== 'Administrador') {
            $this->redirect('/dashboard');
        }
    }

    public function index() {
        $title = "Gestión de Usuarios";
        $active = "usuarios";
        
        $db = new \app\helpers\JsonDB('usuarios');
        $usuarios = $db->all();

        ob_start();
        ?>
        <div class="top-actions">
            <a href="<?= $_ENV['BASE_URL'] ?>/usuarios/crear" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> <span class="btn-text">Nuevo Usuario</span>
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Documento</th>
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
                        <td><strong><?= htmlspecialchars($u['usuario']) ?></strong></td>
                        <td><?= htmlspecialchars($u['numero_documento'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['rol_nombre']) ?></td>
                        <td>
                            <span class="badge badge-<?= ($u['estado'] == 'activo') ? 'success' : 'danger' ?>">
                                <?= ucfirst($u['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= $_ENV['BASE_URL'] ?>/usuarios/editar/<?= $u['id'] ?>" class="btn btn-primary" style="padding: 4px 8px;" title="Editar Usuario">
                                <i class="fas fa-edit"></i>
                            </a>
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

    public function create() {
        $title = "Crear Nuevo Usuario";
        $active = "usuarios";
        
        ob_start();
        ?>
        <div class="table-container" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
            <h3 style="margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; color: var(--primary-dark);">
                <i class="fas fa-user-plus"></i> Registrar Nuevo Usuario
            </h3>

            <?php if (isset($_SESSION['user_error'])): ?>
                <div class="badge badge-danger" style="margin-bottom: 1.5rem; display: block; padding: 10px; border-radius: var(--radius-md); text-align: center; background: #f8d7da; color: #721c24;">
                    <?= $_SESSION['user_error']; unset($_SESSION['user_error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?= $_ENV['BASE_URL'] ?>/usuarios/guardar" method="POST">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr; gap: 1.2rem;">
                    <div class="form-group">
                        <label>Nombre de Usuario (Login)</label>
                        <input type="text" name="usuario" class="form-control" placeholder="ej: jgomez" required>
                    </div>
                    <div class="form-group">
                        <label>Número de Documento</label>
                        <input type="text" name="numero_documento" class="form-control" placeholder="ej: 12345678" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre_completo" class="form-control" placeholder="ej: Jorge Gómez" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" placeholder="ej: jgomez@cas.gov.co" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Rol del Sistema</label>
                        <select name="rol_nombre" class="form-control" required>
                            <option value="Usuario">Usuario</option>
                            <option value="Jefe de Línea">Jefe de Línea</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 1.5rem;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/usuarios" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Usuario</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    public function store() {
        $usuario = trim($_POST['usuario'] ?? '');
        $numero_documento = trim($_POST['numero_documento'] ?? '');
        $nombre_completo = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol_nombre = $_POST['rol_nombre'] ?? 'Usuario';
        $estado = $_POST['estado'] ?? 'activo';

        if (empty($usuario) || empty($numero_documento) || empty($nombre_completo) || empty($email) || empty($password)) {
            $_SESSION['user_error'] = "Todos los campos son obligatorios.";
            $this->redirect('/usuarios/crear');
        }

        $db = new \app\helpers\JsonDB('usuarios');
        $all = $db->all();

        // Validar unicidad
        foreach ($all as $u) {
            if (strtolower($u['usuario']) === strtolower($usuario)) {
                $_SESSION['user_error'] = "El nombre de usuario '{$usuario}' ya se encuentra registrado.";
                $this->redirect('/usuarios/crear');
            }
            if (strtolower($u['email']) === strtolower($email)) {
                $_SESSION['user_error'] = "El correo electrónico '{$email}' ya se encuentra registrado.";
                $this->redirect('/usuarios/crear');
            }
            if (!empty($u['numero_documento']) && $u['numero_documento'] === $numero_documento) {
                $_SESSION['user_error'] = "El número de documento '{$numero_documento}' ya se encuentra registrado.";
                $this->redirect('/usuarios/crear');
            }
        }

        $data = [
            'usuario' => $usuario,
            'password' => $password,
            'numero_documento' => $numero_documento,
            'nombre_completo' => $nombre_completo,
            'email' => $email,
            'rol_nombre' => $rol_nombre,
            'estado' => $estado
        ];

        $id = $db->create($data);

        // Auditoría
        $auditDb = new \app\helpers\JsonDB('auditoria');
        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'CREATE_USER',
            'tabla' => 'usuarios',
            'registro_id' => $id,
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'detalles' => "Usuario creado: {$usuario} ({$nombre_completo}) con rol {$rol_nombre}"
        ]);

        $this->redirect('/usuarios');
    }

    /**
     * @param int $id
     */
    public function edit($id) {
        $title = "Editar Usuario";
        $active = "usuarios";
        
        $db = new \app\helpers\JsonDB('usuarios');
        $u = $db->find($id);

        if (!$u) {
            $this->redirect('/usuarios');
        }

        ob_start();
        ?>
        <div class="table-container" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
            <h3 style="margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; color: var(--primary-dark);">
                <i class="fas fa-user-edit"></i> Modificar Usuario
            </h3>

            <?php if (isset($_SESSION['user_error'])): ?>
                <div class="badge badge-danger" style="margin-bottom: 1.5rem; display: block; padding: 10px; border-radius: var(--radius-md); text-align: center; background: #f8d7da; color: #721c24;">
                    <?= $_SESSION['user_error']; unset($_SESSION['user_error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?= $_ENV['BASE_URL'] ?>/usuarios/actualizar/<?= $id ?>" method="POST">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr; gap: 1.2rem;">
                    <div class="form-group">
                        <label>Nombre de Usuario (Login)</label>
                        <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($u['usuario']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Número de Documento</label>
                        <input type="text" name="numero_documento" class="form-control" value="<?= htmlspecialchars($u['numero_documento'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($u['nombre_completo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña (Dejar en blanco para mantener la actual)</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Rol del Sistema</label>
                        <select name="rol_nombre" class="form-control" required>
                            <option value="Usuario" <?= $u['rol_nombre'] === 'Usuario' ? 'selected' : '' ?>>Usuario</option>
                            <option value="Jefe de Línea" <?= $u['rol_nombre'] === 'Jefe de Línea' ? 'selected' : '' ?>>Jefe de Línea</option>
                            <option value="Administrador" <?= $u['rol_nombre'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control" required>
                            <option value="activo" <?= $u['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $u['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 1.5rem;">
                    <a href="<?= $_ENV['BASE_URL'] ?>/usuarios" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * @param int $id
     */
    public function update($id) {
        $usuario = trim($_POST['usuario'] ?? '');
        $numero_documento = trim($_POST['numero_documento'] ?? '');
        $nombre_completo = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol_nombre = $_POST['rol_nombre'] ?? 'Usuario';
        $estado = $_POST['estado'] ?? 'activo';

        if (empty($usuario) || empty($numero_documento) || empty($nombre_completo) || empty($email)) {
            $_SESSION['user_error'] = "Los campos Usuario, Número de Documento, Nombre Completo y Email son obligatorios.";
            $this->redirect("/usuarios/editar/{$id}");
        }

        $db = new \app\helpers\JsonDB('usuarios');
        $u = $db->find($id);

        if (!$u) {
            $this->redirect('/usuarios');
        }

        // Validar unicidad (excluyendo a sí mismo)
        $all = $db->all();
        foreach ($all as $other) {
            if ($other['id'] == $id) continue;
            if (strtolower($other['usuario']) === strtolower($usuario)) {
                $_SESSION['user_error'] = "El nombre de usuario '{$usuario}' ya se encuentra registrado.";
                $this->redirect("/usuarios/editar/{$id}");
            }
            if (strtolower($other['email']) === strtolower($email)) {
                $_SESSION['user_error'] = "El correo electrónico '{$email}' ya se encuentra registrado.";
                $this->redirect("/usuarios/editar/{$id}");
            }
            if (!empty($other['numero_documento']) && $other['numero_documento'] === $numero_documento) {
                $_SESSION['user_error'] = "El número de documento '{$numero_documento}' ya se encuentra registrado.";
                $this->redirect("/usuarios/editar/{$id}");
            }
        }

        // Si no se proporcionó contraseña, mantener la actual
        $final_password = !empty($password) ? $password : $u['password'];

        $data = [
            'usuario' => $usuario,
            'password' => $final_password,
            'numero_documento' => $numero_documento,
            'nombre_completo' => $nombre_completo,
            'email' => $email,
            'rol_nombre' => $rol_nombre,
            'estado' => $estado
        ];

        $db->update($id, $data);

        // Auditoría
        $auditDb = new \app\helpers\JsonDB('auditoria');
        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'UPDATE_USER',
            'tabla' => 'usuarios',
            'registro_id' => intval($id),
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'detalles' => "Usuario modificado: {$usuario}. Rol: {$rol_nombre}, Estado: {$estado}"
        ]);

        $this->redirect('/usuarios');
    }
}
