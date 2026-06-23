<?php

namespace app\controllers;

/**
 * Controlador para la gestión de usuarios del sistema.
 * Permite listar, crear, editar y actualizar usuarios,
 * con validaciones y registro en auditoría.
 */
class UsuarioController extends Controller {
    
    /**
     * Constructor del controlador.
     * Verifica la sesión y el rol del usuario (solo Administrador).
     */
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        if ($_SESSION['user_role'] !== 'Administrador') {
            $this->redirect('/dashboard');
        }
    }

    /**
     * Muestra la vista principal (listado de usuarios).
     */
    public function index() {
        /** @var string $title Título de la página web */
        $title = "Gestión de Usuarios";
        /** @var string $active Identificador del menú activo para el layout */
        $active = "usuarios";
        
        /** @var \app\helpers\JsonDB $db Instancia de la base de datos de usuarios (JSON) */
        $db = new \app\helpers\JsonDB('usuarios');
        /** @var array $usuarios Lista completa de usuarios obtenidos de la base de datos */
        $usuarios = $db->all();

        ob_start();
        ?>
        <div class="top-actions" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= $_ENV['BASE_URL'] ?>/usuarios/crear" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> <span class="btn-text">Nuevo Usuario</span>
            </a>
            <div class="search-container" style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 0.2rem 0.5rem; display: flex; align-items: center;">
                <i class="fas fa-search" style="color: #666; margin-right: 0.5rem;"></i>
                <input type="text" id="searchUsuarios" placeholder="Buscar usuario..." style="border: none; outline: none; padding: 0.4rem; width: 250px; font-family: inherit;">
            </div>
        </div>

        <div class="table-container">
            <table id="usuariosTable">
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
                    <?php foreach ($usuarios as $userData): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($userData['usuario']) ?></strong></td>
                        <td><?= htmlspecialchars($userData['numero_documento'] ?? '') ?></td>
                        <td><?= htmlspecialchars($userData['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($userData['email']) ?></td>
                        <td><?= htmlspecialchars($userData['rol_nombre']) ?></td>
                        <td>
                            <span class="badge badge-<?= ($userData['estado'] == 'activo') ? 'success' : 'danger' ?>">
                                <?= ucfirst($userData['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= $_ENV['BASE_URL'] ?>/usuarios/editar/<?= $userData['id'] ?>" class="btn btn-primary" style="padding: 4px 8px;" title="Editar Usuario">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // $searchInput: Referencia al campo de texto del buscador
                const searchInput = document.getElementById("searchUsuarios");
                // $tableRows: Colección de las filas (<tr>) de la tabla de usuarios
                const tableRows = document.querySelectorAll("#usuariosTable tbody tr");

                searchInput.addEventListener("keyup", function() {
                    // $filter: Texto ingresado por el usuario, convertido a minúsculas para búsqueda insensible a mayúsculas
                    const filter = searchInput.value.toLowerCase();
                    tableRows.forEach(row => {
                        // $textContent: Texto plano contenido en la fila completa
                        const textContent = row.textContent.toLowerCase();
                        if (textContent.includes(filter)) {
                            row.style.display = "";
                        } else {
                            row.style.display = "none";
                        }
                    });
                });
            });
        </script>
        <?php
        /** @var string $content Contenido HTML generado en el buffer (ob_start -> ob_get_clean) */
        $content = ob_get_clean();
        
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function create() {
        /** @var string $title Título de la vista de creación */
        $title = "Crear Nuevo Usuario";
        /** @var string $active Menú activo en la barra de navegación */
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
        /** @var string $content HTML renderizado para la vista create */
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Procesa los datos del formulario POST y crea el usuario en la base de datos.
     */
    public function store() {
        /** @var string $usuario Nombre de usuario / login capturado del formulario */
        $usuario = trim($_POST['usuario'] ?? '');
        /** @var string $numeroDocumento Documento de identidad (estandarizado a camelCase) */
        $numeroDocumento = trim($_POST['numero_documento'] ?? '');
        /** @var string $nombreCompleto Nombre completo del usuario */
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        /** @var string $email Correo electrónico del usuario */
        $email = trim($_POST['email'] ?? '');
        /** @var string $password Contraseña del usuario (en futuras mejoras aquí iría un hash) */
        $password = $_POST['password'] ?? '';
        /** @var string $rolNombre Rol del usuario en el sistema (ej. Administrador, Usuario) */
        $rolNombre = $_POST['rol_nombre'] ?? 'Usuario';
        /** @var string $estado Estado de la cuenta (activo/inactivo) */
        $estado = $_POST['estado'] ?? 'activo';

        // Validación de campos obligatorios
        if (empty($usuario) || empty($numeroDocumento) || empty($nombreCompleto) || empty($email) || empty($password)) {
            $_SESSION['user_error'] = "Todos los campos son obligatorios.";
            $this->redirect('/usuarios/crear');
        }

        /** @var \app\helpers\JsonDB $db Conexión a la tabla usuarios */
        $db = new \app\helpers\JsonDB('usuarios');
        /** @var array $allUsers Listado actual de todos los usuarios para validación */
        $allUsers = $db->all();

        // Validar unicidad (que no exista otro con el mismo usuario, email o documento)
        foreach ($allUsers as $userData) {
            if (strtolower($userData['usuario']) === strtolower($usuario)) {
                $_SESSION['user_error'] = "El nombre de usuario '{$usuario}' ya se encuentra registrado.";
                $this->redirect('/usuarios/crear');
            }
            if (strtolower($userData['email']) === strtolower($email)) {
                $_SESSION['user_error'] = "El correo electrónico '{$email}' ya se encuentra registrado.";
                $this->redirect('/usuarios/crear');
            }
            if (!empty($userData['numero_documento']) && $userData['numero_documento'] === $numeroDocumento) {
                $_SESSION['user_error'] = "El número de documento '{$numeroDocumento}' ya se encuentra registrado.";
                $this->redirect('/usuarios/crear');
            }
        }

        /** @var array $data Arreglo asociativo con los datos que se guardarán en la DB (claves en snake_case para compatibilidad) */
        $data = [
            'usuario' => $usuario,
            'password' => $password,
            'numero_documento' => $numeroDocumento,
            'nombre_completo' => $nombreCompleto,
            'email' => $email,
            'rol_nombre' => $rolNombre,
            'estado' => $estado
        ];

        /** @var string|int $id ID generado para el nuevo registro */
        $id = $db->create($data);

        // Registro de Auditoría
        /** @var \app\helpers\JsonDB $auditDb Conexión a la tabla de auditoría */
        $auditDb = new \app\helpers\JsonDB('auditoria');
        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'CREATE_USER',
            'tabla' => 'usuarios',
            'registro_id' => $id,
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'detalles' => "Usuario creado: {$usuario} ({$nombreCompleto}) con rol {$rolNombre}"
        ]);

        $this->redirect('/usuarios');
    }

    /**
     * Muestra el formulario para editar un usuario existente.
     * @param int|string $id Identificador único del usuario a editar
     */
    public function edit($id) {
        /** @var string $title Título para la vista de edición */
        $title = "Editar Usuario";
        /** @var string $active Menú activo */
        $active = "usuarios";
        
        /** @var \app\helpers\JsonDB $db Conexión base de datos usuarios */
        $db = new \app\helpers\JsonDB('usuarios');
        /** @var array|null $userData Datos del usuario obtenidos por su ID */
        $userData = $db->find($id);

        if (!$userData) {
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
                        <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($userData['usuario']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Número de Documento</label>
                        <input type="text" name="numero_documento" class="form-control" value="<?= htmlspecialchars($userData['numero_documento'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($userData['nombre_completo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña (Dejar en blanco para mantener la actual)</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Rol del Sistema</label>
                        <select name="rol_nombre" class="form-control" required>
                            <option value="Usuario" <?= $userData['rol_nombre'] === 'Usuario' ? 'selected' : '' ?>>Usuario</option>
                            <option value="Jefe de Línea" <?= $userData['rol_nombre'] === 'Jefe de Línea' ? 'selected' : '' ?>>Jefe de Línea</option>
                            <option value="Administrador" <?= $userData['rol_nombre'] === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control" required>
                            <option value="activo" <?= $userData['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $userData['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
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
        /** @var string $content HTML renderizado del formulario */
        $content = ob_get_clean();
        $this->render('layouts/main', compact('title', 'active', 'content'));
    }

    /**
     * Procesa la actualización de los datos de un usuario por POST.
     * @param int|string $id Identificador del usuario a actualizar
     */
    public function update($id) {
        /** @var string $usuario Nombre de usuario modificado */
        $usuario = trim($_POST['usuario'] ?? '');
        /** @var string $numeroDocumento Documento modificado */
        $numeroDocumento = trim($_POST['numero_documento'] ?? '');
        /** @var string $nombreCompleto Nombre modificado */
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        /** @var string $email Correo modificado */
        $email = trim($_POST['email'] ?? '');
        /** @var string $password Nueva contraseña ingresada (vacío si no cambia) */
        $password = $_POST['password'] ?? '';
        /** @var string $rolNombre Rol seleccionado */
        $rolNombre = $_POST['rol_nombre'] ?? 'Usuario';
        /** @var string $estado Estado seleccionado */
        $estado = $_POST['estado'] ?? 'activo';

        if (empty($usuario) || empty($numeroDocumento) || empty($nombreCompleto) || empty($email)) {
            $_SESSION['user_error'] = "Los campos Usuario, Número de Documento, Nombre Completo y Email son obligatorios.";
            $this->redirect("/usuarios/editar/{$id}");
        }

        // Validación: Bloquear inactivación si tiene expedientes asignados o préstamos activos
        if ($estado === 'inactivo') {
            $asignacionesDb = new \app\helpers\JsonDB('asignaciones');
            $misAsignaciones = $asignacionesDb->where('usuario_id', $id);
            if (!empty($misAsignaciones)) {
                $_SESSION['user_error'] = "No se puede inactivar el usuario porque tiene expedientes asignados en el sistema.";
                $this->redirect("/usuarios/editar/{$id}");
            }

            $prestamosDb = new \app\helpers\JsonDB('prestamos');
            $todosPrestamos = $prestamosDb->all();
            $prestamosActivos = array_filter($todosPrestamos, function($p) use ($id) {
                return $p['usuario_solicitante_id'] == $id && $p['estado'] !== 'devuelto';
            });
            if (!empty($prestamosActivos)) {
                $_SESSION['user_error'] = "No se puede inactivar el usuario porque tiene préstamos pendientes de devolución.";
                $this->redirect("/usuarios/editar/{$id}");
            }
        }

        /** @var \app\helpers\JsonDB $db Conexión BD */
        $db = new \app\helpers\JsonDB('usuarios');
        /** @var array|null $userData Datos anteriores del usuario */
        $userData = $db->find($id);

        if (!$userData) {
            $this->redirect('/usuarios');
        }

        /** @var array $allUsers Colección completa de usuarios para validar unicidad excluyéndose a sí mismo */
        $allUsers = $db->all();
        foreach ($allUsers as $otherUser) {
            // Se excluye a sí mismo de las verificaciones de unicidad
            if ($otherUser['id'] == $id) continue;
            
            if (strtolower($otherUser['usuario']) === strtolower($usuario)) {
                $_SESSION['user_error'] = "El nombre de usuario '{$usuario}' ya se encuentra registrado.";
                $this->redirect("/usuarios/editar/{$id}");
            }
            if (strtolower($otherUser['email']) === strtolower($email)) {
                $_SESSION['user_error'] = "El correo electrónico '{$email}' ya se encuentra registrado.";
                $this->redirect("/usuarios/editar/{$id}");
            }
            if (!empty($otherUser['numero_documento']) && $otherUser['numero_documento'] === $numeroDocumento) {
                $_SESSION['user_error'] = "El número de documento '{$numeroDocumento}' ya se encuentra registrado.";
                $this->redirect("/usuarios/editar/{$id}");
            }
        }

        /** @var string $finalPassword Determina si se guarda la nueva contraseña o se mantiene la actual */
        $finalPassword = !empty($password) ? $password : $userData['password'];

        /** @var array $data Arreglo asociativo con los datos finales a actualizar */
        $data = [
            'usuario' => $usuario,
            'password' => $finalPassword,
            'numero_documento' => $numeroDocumento,
            'nombre_completo' => $nombreCompleto,
            'email' => $email,
            'rol_nombre' => $rolNombre,
            'estado' => $estado
        ];

        $db->update($id, $data);

        // Registro de Auditoría
        /** @var \app\helpers\JsonDB $auditDb Conexión para guardar traza de auditoría */
        $auditDb = new \app\helpers\JsonDB('auditoria');
        $auditDb->create([
            'usuario' => $_SESSION['user_name'],
            'accion' => 'UPDATE_USER',
            'tabla' => 'usuarios',
            'registro_id' => intval($id),
            'fecha' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'detalles' => "Usuario modificado: {$usuario}. Rol: {$rolNombre}, Estado: {$estado}"
        ]);

        $this->redirect('/usuarios');
    }
}
