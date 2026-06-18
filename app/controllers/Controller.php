<?php

namespace app\controllers;

/**
 * Controlador base del cual heredan todos los demás controladores.
 * Proporciona métodos comunes para renderizar vistas, retornar JSON y redirigir.
 */
abstract class Controller {
    
    /**
     * Renderiza una vista pasando un arreglo de datos.
     * 
     * @param string $view Nombre de la vista (ruta relativa sin extensión .php)
     * @param array $data Arreglo asociativo con variables a extraer en la vista
     */
    protected function render($view, $data = []) {
        // Extrae las variables del arreglo al ámbito local
        extract($data);
        
        /** @var string $viewPath Ruta absoluta al archivo de la vista */
        $viewPath = ROOT_PATH . "/app/views/$view.php";
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("View $view not found.");
        }
    }

    /**
     * Retorna una respuesta en formato JSON.
     * 
     * @param mixed $data Datos a serializar y retornar
     * @param int $status Código de estado HTTP (por defecto 200)
     */
    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Redirige a otra URL dentro del sistema.
     * 
     * @param string $url Ruta relativa a la cual redirigir (ej. '/dashboard')
     */
    protected function redirect($url) {
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . $url);
        exit;
    }
}
