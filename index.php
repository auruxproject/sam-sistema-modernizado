<?php

/**
 * Punto de entrada principal para ISURGOB
 * Sistema de Administración Municipal modernizado
 * Compatible con EasyPanel, Traefik y PHP 7.4+
 */

// Configuración específica para EasyPanel/Traefik
// Manejar headers de proxy reverso
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

// Cargar variables de entorno si existe el archivo .env
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, '"\' ');
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Configuración del entorno
defined('YII_DEBUG') or define('YII_DEBUG', getenv('APP_DEBUG') === 'true' || getenv('APP_ENV') === 'development');
defined('YII_ENV') or define('YII_ENV', getenv('APP_ENV') ?: 'production');

// Configuración de errores para desarrollo
if (YII_ENV_DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuración de zona horaria
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires');

// Configuración de memoria y tiempo de ejecución
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

// Cargar autoloader de Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/yii/autoload.php')) {
    require_once __DIR__ . '/yii/autoload.php';
} else {
    die('Error: Composer autoloader not found. Please run "composer install" or check autoloader path.');
}

// Cargar framework Yii
if (file_exists(__DIR__ . '/vendor/yiisoft/yii2/Yii.php')) {
    require_once __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
} elseif (file_exists(__DIR__ . '/yii/yiisoft/yii2/Yii.php')) {
    require_once __DIR__ . '/yii/yiisoft/yii2/Yii.php';
} else {
    die('Error: Yii framework not found. Please run "composer install" or check Yii path.');
}

// Cargar configuración de la aplicación
$config = require __DIR__ . '/config/web.php';

// Verificar modo de mantenimiento
if (isset($config['params']['maintenance']['enabled']) && $config['params']['maintenance']['enabled']) {
    $allowedIPs = $config['params']['maintenance']['allowedIPs'] ?? [];
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!in_array($clientIP, $allowedIPs)) {
        http_response_code(503);
        $message = $config['params']['maintenance']['message'] ?? 'Sistema en mantenimiento';
        echo "<!DOCTYPE html><html><head><title>Mantenimiento</title></head><body><h1>{$message}</h1></body></html>";
        exit;
    }
}

// Crear y ejecutar la aplicación
try {
    $application = new yii\web\Application($config);
    
    // Configurar trusted proxies para EasyPanel
    if (isset($_ENV['TRUSTED_PROXIES'])) {
        $trustedProxies = explode(',', $_ENV['TRUSTED_PROXIES']);
        $application->request->setTrustedHosts($trustedProxies);
    }
    
    $application->run();
    
} catch (Exception $e) {
    // Log del error
    error_log('Application Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    if (YII_ENV_DEV) {
        throw $e;
    } else {
        http_response_code(500);
        
        // Respuesta más informativa para debugging en EasyPanel
        $errorId = uniqid('err_');
        error_log("Error ID {$errorId}: " . $e->getTraceAsString());
        
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Error del Servidor</title>";
        echo "<meta charset='UTF-8'>";
        echo "<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;}";
        echo ".error-container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
        echo "h1{color:#d32f2f;margin-bottom:20px;}";
        echo ".error-id{color:#666;font-size:12px;margin-top:20px;}";
        echo "</style></head><body>";
        echo "<div class='error-container'>";
        echo "<h1>Error interno del servidor</h1>";
        echo "<p>Lo sentimos, ha ocurrido un error interno. Por favor, intente nuevamente más tarde.</p>";
        echo "<p>Si el problema persiste, contacte al administrador del sistema.</p>";
        echo "<div class='error-id'>Error ID: {$errorId}</div>";
        echo "</div></body></html>";
    }
}

