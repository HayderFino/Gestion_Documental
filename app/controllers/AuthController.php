<?php

namespace app\controllers;

use app\models\Usuario;
use app\services\AuditService;

class AuthController extends Controller {
    private $usuarioModel;
    private $auditService;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->auditService = new AuditService();
    }

    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        require_once ROOT_PATH . '/app/views/auth/login.php';
    }

    public function login() {
        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $db = new \app\helpers\JsonDB('usuarios');
        $users = $db->where('usuario', $usuario);
        $user = !empty($users) ? array_values($users)[0] : null;

        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre_completo'];
            $_SESSION['user_role'] = $user['rol_nombre'];
            
            $this->redirect('/dashboard');
        } else {
            $error = "Usuario o contraseña incorrectos.";
            require_once ROOT_PATH . '/app/views/auth/login.php';
        }
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->auditService->log($_SESSION['user_id'], 'LOGOUT', 'usuarios', $_SESSION['user_id']);
        }
        session_destroy();
        $this->redirect('/login');
    }
}
