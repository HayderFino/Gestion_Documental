<?php

namespace app\helpers;

class Router {
    private $routes = [];

    public function add($method, $path, $callback) {
        $path = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[^/]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'path' => "#^" . $path . "$#",
            'callback' => $callback
        ];
    }

    public function dispatch($url) {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = parse_url($url, PHP_URL_PATH) ?: '/';
        
        // Remove base path if needed (if not running on root)
        // For local development on subfolder, we might need to adjust
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['path'], $url, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                if (is_array($route['callback'])) {
                    $controllerName = $route['callback'][0];
                    $action = $route['callback'][1];
                    $controller = new $controllerName();
                    return call_user_func_array([$controller, $action], $params);
                }
                
                return call_user_func_array($route['callback'], $params);
            }
        }

        http_response_code(404);
        echo "404 - Not Found";
    }
}
