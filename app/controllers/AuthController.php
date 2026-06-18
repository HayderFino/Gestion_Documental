<?php

namespace app\controllers;

use app\models\Usuario;
use app\services\AuditService;

/**
 * Controlador para la autenticación de usuarios.
 * Maneja el inicio y cierre de sesión.
 */
class AuthController extends Controller {
    /** @var Usuario Instancia del modelo de Usuario */
    private $usuarioModel;
    
    /** @var AuditService Servicio para registrar auditorías del sistema */
    private $auditService;

    /**
     * Constructor del controlador de autenticación.
     * Inicializa los modelos y servicios requeridos.
     */
    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->auditService = new AuditService();
    }

    /**
     * Muestra la vista del formulario de inicio de sesión.
     * Si el usuario ya está autenticado, lo redirige al dashboard.
     */
    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        require_once ROOT_PATH . '/app/views/auth/login.php';
    }

    /**
     * Procesa la solicitud POST de inicio de sesión.
     * Valida credenciales contra la base de datos JSON.
     */
    public function login() {
        /** @var string $usuario Nombre de usuario ingresado en el formulario */
        $usuario = $_POST['usuario'] ?? '';
        /** @var string $password Contraseña ingresada en el formulario */
        $password = $_POST['password'] ?? '';
        
        /** @var \app\helpers\JsonDB $db Conexión a la base de datos JSON de usuarios */
        $db = new \app\helpers\JsonDB('usuarios');
        /** @var array $users Arreglo con los usuarios que coinciden con el nombre de usuario (debería ser 1 o 0) */
        $users = $db->where('usuario', $usuario);
        
        /** @var array|null $user Datos del usuario encontrado o null si no existe */
        $user = !empty($users) ? array_values($users)[0] : null;

        // Verifica si existe el usuario y si la contraseña coincide (TODO: Implementar hashes en el futuro)
        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre_completo'];
            $_SESSION['user_role'] = $user['rol_nombre'];
            
            $this->redirect('/dashboard');
        } else {
            /** @var string $error Mensaje de error para mostrar en la vista */
            $error = "Usuario o contraseña incorrectos.";
            require_once ROOT_PATH . '/app/views/auth/login.php';
        }
    }

    /**
     * Cierra la sesión activa del usuario y redirige al login.
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->auditService->log($_SESSION['user_id'], 'LOGOUT', 'usuarios', $_SESSION['user_id']);
        }
        session_destroy();
        $this->redirect('/login');
    }
}
