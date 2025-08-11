<?php

/**
 * Health Check Endpoint para EasyPanel
 * Verifica el estado de la aplicación y sus dependencias
 */

// Configurar headers para respuesta JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Función para verificar la conexión a la base de datos
function checkDatabase() {
    try {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $dbname = getenv('DB_DATABASE') ?: 'isurgob';
        $username = getenv('DB_USERNAME') ?: 'webusr';
        $password = getenv('DB_PASSWORD') ?: 'test';
        
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Ejecutar una consulta simple
        $stmt = $pdo->query('SELECT 1');
        $result = $stmt->fetch();
        
        return [
            'status' => 'ok',
            'message' => 'Database connection successful',
            'response_time' => microtime(true)
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage(),
            'response_time' => microtime(true)
        ];
    }
}

// Función para verificar el sistema de archivos
function checkFileSystem() {
    try {
        $runtimePath = __DIR__ . '/runtime';
        $testFile = $runtimePath . '/health_check_' . time() . '.tmp';
        
        // Verificar si el directorio runtime existe y es escribible
        if (!is_dir($runtimePath)) {
            mkdir($runtimePath, 0755, true);
        }
        
        if (!is_writable($runtimePath)) {
            throw new Exception('Runtime directory is not writable');
        }
        
        // Intentar escribir un archivo temporal
        file_put_contents($testFile, 'health check');
        
        // Verificar que se puede leer
        $content = file_get_contents($testFile);
        
        // Limpiar archivo temporal
        unlink($testFile);
        
        if ($content !== 'health check') {
            throw new Exception('File read/write test failed');
        }
        
        return [
            'status' => 'ok',
            'message' => 'File system is working correctly',
            'writable' => true
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'File system error: ' . $e->getMessage(),
            'writable' => false
        ];
    }
}

// Función para verificar la configuración de PHP
function checkPHPConfig() {
    $requiredExtensions = ['pdo', 'pdo_pgsql', 'mbstring', 'openssl', 'json'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    $memoryLimit = ini_get('memory_limit');
    $maxExecutionTime = ini_get('max_execution_time');
    
    return [
        'status' => empty($missingExtensions) ? 'ok' : 'warning',
        'php_version' => PHP_VERSION,
        'memory_limit' => $memoryLimit,
        'max_execution_time' => $maxExecutionTime,
        'missing_extensions' => $missingExtensions,
        'required_extensions' => $requiredExtensions
    ];
}

// Función para verificar el espacio en disco
function checkDiskSpace() {
    try {
        $freeBytes = disk_free_space(__DIR__);
        $totalBytes = disk_total_space(__DIR__);
        
        $freeGB = round($freeBytes / 1024 / 1024 / 1024, 2);
        $totalGB = round($totalBytes / 1024 / 1024 / 1024, 2);
        $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
        
        $status = $usedPercent > 90 ? 'warning' : 'ok';
        
        return [
            'status' => $status,
            'free_space_gb' => $freeGB,
            'total_space_gb' => $totalGB,
            'used_percent' => $usedPercent
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Could not check disk space: ' . $e->getMessage()
        ];
    }
}

// Función principal de health check
function performHealthCheck() {
    $startTime = microtime(true);
    
    $checks = [
        'database' => checkDatabase(),
        'filesystem' => checkFileSystem(),
        'php_config' => checkPHPConfig(),
        'disk_space' => checkDiskSpace()
    ];
    
    // Determinar el estado general
    $overallStatus = 'ok';
    foreach ($checks as $check) {
        if ($check['status'] === 'error') {
            $overallStatus = 'error';
            break;
        } elseif ($check['status'] === 'warning' && $overallStatus === 'ok') {
            $overallStatus = 'warning';
        }
    }
    
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2); // en milisegundos
    
    return [
        'status' => $overallStatus,
        'timestamp' => date('c'),
        'response_time_ms' => $responseTime,
        'application' => 'ISURGOB',
        'version' => '2.0',
        'environment' => getenv('APP_ENV') ?: 'production',
        'checks' => $checks,
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true)
            ]
        ]
    ];
}

// Ejecutar health check y devolver respuesta
try {
    $healthData = performHealthCheck();
    
    // Establecer código de respuesta HTTP apropiado
    switch ($healthData['status']) {
        case 'ok':
            http_response_code(200);
            break;
        case 'warning':
            http_response_code(200); // Warnings no son errores críticos
            break;
        case 'error':
            http_response_code(503); // Service Unavailable
            break;
        default:
            http_response_code(500);
    }
    
    echo json_encode($healthData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Health check failed: ' . $e->getMessage(),
        'timestamp' => date('c'),
        'application' => 'ISURGOB'
    ], JSON_PRETTY_PRINT);
}

exit;