<?php

namespace app\controllers;

abstract class Controller {
    protected function render($view, $data = []) {
        extract($data);
        $viewPath = ROOT_PATH . "/app/views/$view.php";
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("View $view not found.");
        }
    }

    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        header("Location: " . ($_ENV['BASE_URL'] ?? '') . $url);
        exit;
    }
}
