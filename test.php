<?php

/**
 * Script de prueba para diagnosticar problemas del sistema SAM
 * Compatible con EasyPanel
 */

// Configuración de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Diagnóstico del Sistema SAM</h1>";
echo "<hr>";

// Verificar versión de PHP
echo "<h2>1. Información de PHP</h2>";
echo "<p><strong>Versión PHP:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>SAPI:</strong> " . php_sapi_name() . "</p>";
echo "<p><strong>Memoria límite:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>Tiempo ejecución:</strong> " . ini_get('max_execution_time') . "s</p>";

// Verificar extensiones requeridas
echo "<h2>2. Extensiones PHP</h2>";
$requiredExtensions = ['pdo', 'pdo_pgsql', 'mbstring', 'openssl', 'json', 'curl'];
foreach ($requiredExtensions as $ext) {
    $status = extension_loaded($ext) ? '✅ Instalada' : '❌ Faltante';
    echo "<p><strong>{$ext}:</strong> {$status}</p>";
}

// Verificar archivos críticos
echo "<h2>3. Archivos del Sistema</h2>";
$criticalFiles = [
    'yii/autoload.php' => 'Autoloader de Composer',
    'yii/yiisoft/yii2/Yii.php' => 'Framework Yii2',
    'config/web.php' => 'Configuración Web',
    'config/db.php' => 'Configuración Base de Datos',
    'runtime' => 'Directorio Runtime',
    'web/assets' => 'Directorio Assets'
];

foreach ($criticalFiles as $file => $description) {
    $exists = file_exists(__DIR__ . '/' . $file) || is_dir(__DIR__ . '/' . $file);
    $status = $exists ? '✅ Existe' : '❌ Faltante';
    echo "<p><strong>{$description}:</strong> {$status} ({$file})</p>";
}

// Verificar permisos de escritura
echo "<h2>4. Permisos de Escritura</h2>";
$writableDirs = ['runtime', 'web/assets'];
foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $writable = is_dir($path) && is_writable($path);
    $status = $writable ? '✅ Escribible' : '❌ Sin permisos';
    echo "<p><strong>{$dir}:</strong> {$status}</p>";
}

// Probar carga del autoloader
echo "<h2>5. Prueba de Autoloader</h2>";
try {
    if (file_exists(__DIR__ . '/yii/autoload.php')) {
        require_once __DIR__ . '/yii/autoload.php';
        echo "<p>✅ Autoloader cargado correctamente</p>";
        
        // Probar carga de Yii
        if (file_exists(__DIR__ . '/yii/yiisoft/yii2/Yii.php')) {
            require_once __DIR__ . '/yii/yiisoft/yii2/Yii.php';
            echo "<p>✅ Framework Yii2 cargado correctamente</p>";
            echo "<p><strong>Versión Yii2:</strong> " . Yii::getVersion() . "</p>";
        } else {
            echo "<p>❌ No se pudo cargar el framework Yii2</p>";
        }
    } else {
        echo "<p>❌ Autoloader no encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error al cargar autoloader: " . $e->getMessage() . "</p>";
}

// Probar configuración
echo "<h2>6. Prueba de Configuración</h2>";
try {
    if (file_exists(__DIR__ . '/config/web.php')) {
        $config = require __DIR__ . '/config/web.php';
        echo "<p>✅ Configuración web cargada correctamente</p>";
        echo "<p><strong>ID Aplicación:</strong> " . ($config['id'] ?? 'No definido') . "</p>";
        echo "<p><strong>Nombre:</strong> " . ($config['name'] ?? 'No definido') . "</p>";
    } else {
        echo "<p>❌ Archivo de configuración web no encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error al cargar configuración: " . $e->getMessage() . "</p>";
}

// Información del servidor
echo "<h2>7. Información del Servidor</h2>";
echo "<p><strong>Servidor:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "</p>";
echo "<p><strong>Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Desconocido') . "</p>";
echo "<p><strong>IP Cliente:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocido') . "</p>";
echo "<p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido') . "</p>";

// Headers de proxy (EasyPanel/Traefik)
echo "<h2>8. Headers de Proxy (EasyPanel)</h2>";
$proxyHeaders = [
    'HTTP_X_FORWARDED_PROTO' => 'Protocolo',
    'HTTP_X_FORWARDED_FOR' => 'IP Original',
    'HTTP_X_FORWARDED_HOST' => 'Host Original',
    'HTTP_X_FORWARDED_PORT' => 'Puerto Original',
    'HTTP_X_REAL_IP' => 'IP Real'
];

foreach ($proxyHeaders as $header => $description) {
    $value = $_SERVER[$header] ?? 'No presente';
    echo "<p><strong>{$description}:</strong> {$value}</p>";
}

// Variables de entorno
echo "<h2>9. Variables de Entorno</h2>";
$envVars = ['APP_ENV', 'APP_DEBUG', 'DB_HOST', 'DB_PORT', 'DB_NAME'];
foreach ($envVars as $var) {
    $value = getenv($var) ?: 'No definida';
    echo "<p><strong>{$var}:</strong> {$value}</p>";
}

echo "<hr>";
echo "<p><strong>Diagnóstico completado:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><em>Si hay errores, revise los elementos marcados con ❌</em></p>";

?>