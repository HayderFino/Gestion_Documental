<?php
/**
 * SISTEMA DE GESTIÓN DOCUMENTAL – PRÉSTAMO Y DEVOLUCIÓN DE EXPEDIENTES
 * Entry Point
 */

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
session_start();

// Define constants
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Auto-copy favicon from database if missing in public/assets/favicon.png
$faviconDest = ROOT_PATH . '/public/assets/favicon.png';
if (!file_exists($faviconDest)) {
    $faviconSource = ROOT_PATH . '/database/favicon_cas_1780588614112.png';
    if (file_exists($faviconSource)) {
        if (!is_dir(dirname($faviconDest))) {
            @mkdir(dirname($faviconDest), 0755, true);
        }
        @copy($faviconSource, $faviconDest);
    }
}
// Clean up temporary script if it exists
$tempScript = ROOT_PATH . '/public/copy_favicon.php';
if (file_exists($tempScript)) {
    @unlink($tempScript);
}

// Autoloader (Simple PSR-4-like)
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $file = ROOT_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load environment variables (Basic parser)
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Compute BASE_URL dynamically to support different local and server paths.
if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['SCRIPT_NAME'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
    if ($scriptPath === '/' || $scriptPath === '\\') {
        $scriptPath = '';
    }
    $_ENV['BASE_URL'] = rtrim($scheme . '://' . $host . $scriptPath, '/');
}

// Load Routes
$router = require_once ROOT_PATH . '/routes/web.php';

// Refined URL Detection
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Detect project root by removing public/index.php from script name
$projectRoot = str_replace('/public/index.php', '', $scriptName);

// Remove project root from request URI
$url = str_replace($projectRoot, '', $requestUri);

// If there's still a /public at the start of the URL (direct access), remove it
if (strpos($url, '/public') === 0) {
    $url = substr($url, 7);
}

$url = strtok($url, '?'); // Remove query string

if ($url === '' || $url === '/' || $url === false) $url = '/login'; // Default to login if root

// Dispatch
$router->dispatch($url);
