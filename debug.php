<?php

/**
 * Script de debug simple para EasyPanel
 * Este archivo ayuda a diagnosticar problemas sin cargar Yii completo
 */

// Configuración básica de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/runtime/debug.log');

// Headers para EasyPanel/Traefik
header('Content-Type: text/html; charset=UTF-8');
header('X-Powered-By: SAM Debug Script');

// Función para verificar archivos
function checkFile($path, $description) {
    $fullPath = __DIR__ . '/' . $path;
    $exists = file_exists($fullPath);
    $readable = $exists && is_readable($fullPath);
    $size = $exists ? filesize($fullPath) : 0;
    
    return [
        'path' => $path,
        'description' => $description,
        'exists' => $exists,
        'readable' => $readable,
        'size' => $size,
        'status' => $exists && $readable ? 'OK' : 'ERROR'
    ];
}

// Función para verificar directorios
function checkDirectory($path, $description) {
    $fullPath = __DIR__ . '/' . $path;
    $exists = is_dir($fullPath);
    $writable = $exists && is_writable($fullPath);
    
    return [
        'path' => $path,
        'description' => $description,
        'exists' => $exists,
        'writable' => $writable,
        'status' => $exists && $writable ? 'OK' : 'ERROR'
    ];
}

// Función para crear log de debug
function debugLog($message) {
    $logFile = __DIR__ . '/runtime/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    if (is_dir(__DIR__ . '/runtime')) {
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Iniciar debug
debugLog('=== DEBUG SCRIPT STARTED ===');
debugLog('Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Unknown'));
debugLog('HTTP Host: ' . ($_SERVER['HTTP_HOST'] ?? 'Unknown'));
debugLog('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Debug EasyPanel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .ok { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .status-ok { background-color: #d4edda; color: #155724; }
        .status-error { background-color: #f8d7da; color: #721c24; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 SAM - Diagnóstico EasyPanel</h1>
            <p>Sistema de diagnóstico para resolver errores 500</p>
        </div>

        <div class="section">
            <h2>📊 Estado General</h2>
            <p><strong>Fecha/Hora:</strong> <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
            <p><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
            <p><strong>Host:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'Unknown' ?></p>
            <p><strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></p>
            <p><strong>Script Path:</strong> <?= __DIR__ ?></p>
        </div>

        <div class="section">
            <h2>📁 Verificación de Archivos Críticos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Tamaño</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $criticalFiles = [
                        'yii/autoload.php' => 'Autoloader Principal',
                        'yii/yiisoft/yii2/Yii.php' => 'Framework Yii2',
                        'config/web.php' => 'Configuración Web',
                        'config/db.php' => 'Configuración BD',
                        '.env' => 'Variables de Entorno',
                        'controllers/SiteController.php' => 'Controlador Principal',
                        'views/layouts/main.php' => 'Layout Principal',
                        'web/index.php' => 'Punto de Entrada Web'
                    ];
                    
                    foreach ($criticalFiles as $file => $desc) {
                        $check = checkFile($file, $desc);
                        $statusClass = $check['status'] === 'OK' ? 'status-ok' : 'status-error';
                        $sizeFormatted = $check['size'] > 0 ? number_format($check['size']) . ' bytes' : 'N/A';
                        
                        echo "<tr class='{$statusClass}'>";
                        echo "<td>{$check['path']}</td>";
                        echo "<td>{$check['description']}</td>";
                        echo "<td>{$check['status']}</td>";
                        echo "<td>{$sizeFormatted}</td>";
                        echo "</tr>";
                        
                        debugLog("File check: {$file} - {$check['status']}");
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>📂 Verificación de Directorios</h2>
            <table>
                <thead>
                    <tr>
                        <th>Directorio</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Permisos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $criticalDirs = [
                        'runtime' => 'Directorio de Runtime',
                        'web/assets' => 'Assets Web',
                        'config' => 'Configuraciones',
                        'controllers' => 'Controladores',
                        'views' => 'Vistas',
                        'yii' => 'Framework Yii'
                    ];
                    
                    foreach ($criticalDirs as $dir => $desc) {
                        $check = checkDirectory($dir, $desc);
                        $statusClass = $check['status'] === 'OK' ? 'status-ok' : 'status-error';
                        $permissions = $check['writable'] ? 'Escribible' : 'Solo lectura';
                        
                        echo "<tr class='{$statusClass}'>";
                        echo "<td>{$check['path']}</td>";
                        echo "<td>{$check['description']}</td>";
                        echo "<td>{$check['status']}</td>";
                        echo "<td>{$permissions}</td>";
                        echo "</tr>";
                        
                        debugLog("Directory check: {$dir} - {$check['status']}");
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>🔧 Prueba de Carga de Componentes</h2>
            <?php
            echo "<h3>1. Prueba de Autoloader</h3>";
            try {
                if (file_exists(__DIR__ . '/yii/autoload.php')) {
                    require_once __DIR__ . '/yii/autoload.php';
                    echo "<p class='ok'>✅ Autoloader cargado correctamente</p>";
                    debugLog('Autoloader loaded successfully');
                } else {
                    echo "<p class='error'>❌ Autoloader no encontrado</p>";
                    debugLog('Autoloader not found');
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error cargando autoloader: " . htmlspecialchars($e->getMessage()) . "</p>";
                debugLog('Autoloader error: ' . $e->getMessage());
            }
            
            echo "<h3>2. Prueba de Framework Yii</h3>";
            try {
                if (file_exists(__DIR__ . '/yii/yiisoft/yii2/Yii.php')) {
                    require_once __DIR__ . '/yii/yiisoft/yii2/Yii.php';
                    echo "<p class='ok'>✅ Framework Yii2 cargado correctamente</p>";
                    echo "<p><strong>Versión Yii:</strong> " . Yii::getVersion() . "</p>";
                    debugLog('Yii framework loaded successfully - Version: ' . Yii::getVersion());
                } else {
                    echo "<p class='error'>❌ Framework Yii2 no encontrado</p>";
                    debugLog('Yii framework not found');
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error cargando Yii2: " . htmlspecialchars($e->getMessage()) . "</p>";
                debugLog('Yii framework error: ' . $e->getMessage());
            }
            
            echo "<h3>3. Prueba de Configuración</h3>";
            try {
                if (file_exists(__DIR__ . '/config/web.php')) {
                    $config = require __DIR__ . '/config/web.php';
                    echo "<p class='ok'>✅ Configuración web cargada</p>";
                    echo "<p><strong>ID App:</strong> " . ($config['id'] ?? 'No definido') . "</p>";
                    echo "<p><strong>Nombre:</strong> " . ($config['name'] ?? 'No definido') . "</p>";
                    debugLog('Web config loaded successfully');
                } else {
                    echo "<p class='error'>❌ Configuración web no encontrada</p>";
                    debugLog('Web config not found');
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error en configuración: " . htmlspecialchars($e->getMessage()) . "</p>";
                debugLog('Config error: ' . $e->getMessage());
            }
            ?>
        </div>

        <div class="section">
            <h2>🌐 Headers EasyPanel/Traefik</h2>
            <table>
                <thead>
                    <tr>
                        <th>Header</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $proxyHeaders = [
                        'HTTP_X_FORWARDED_PROTO' => 'Protocolo',
                        'HTTP_X_FORWARDED_FOR' => 'IP Original',
                        'HTTP_X_FORWARDED_HOST' => 'Host Original',
                        'HTTP_X_FORWARDED_PORT' => 'Puerto',
                        'HTTP_X_REAL_IP' => 'IP Real',
                        'HTTPS' => 'HTTPS Status',
                        'SERVER_PORT' => 'Puerto Servidor'
                    ];
                    
                    foreach ($proxyHeaders as $header => $desc) {
                        $value = $_SERVER[$header] ?? 'No presente';
                        echo "<tr>";
                        echo "<td><strong>{$desc}</strong><br><small>{$header}</small></td>";
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>🔗 Enlaces de Prueba</h2>
            <p>Use estos enlaces para probar diferentes componentes:</p>
            <a href="test.php" class="btn">🧪 Test Completo</a>
            <a href="health.php" class="btn">❤️ Health Check</a>
            <a href="index.php" class="btn">🏠 Aplicación Principal</a>
            <a href="debug.php" class="btn">🔧 Este Debug</a>
        </div>

        <div class="section">
            <h2>📝 Log de Debug</h2>
            <div class="code">
                <?php
                $logFile = __DIR__ . '/runtime/debug.log';
                if (file_exists($logFile)) {
                    $logContent = file_get_contents($logFile);
                    $lines = explode("\n", $logContent);
                    $recentLines = array_slice($lines, -20); // Últimas 20 líneas
                    echo htmlspecialchars(implode("\n", $recentLines));
                } else {
                    echo "Log file not found or not created yet.";
                }
                ?>
            </div>
        </div>
    </div>

    <?php debugLog('=== DEBUG SCRIPT COMPLETED ==='); ?>
</body>
</html>