<?php

/**
 * Configuración de base de datos para ISURGOB
 * Soporte para múltiples municipios y variables de entorno
 * Compatible con PostgreSQL y EasyPanel
 */

use yii\db\Connection;

// Configuración principal de base de datos
$dbConfig = [
    'class' => Connection::class,
    'dsn' => sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_PORT') ?: '5432',
        getenv('DB_DATABASE') ?: 'isurgob'
    ),
    'username' => getenv('DB_USERNAME') ?: 'webusr',
    'password' => getenv('DB_PASSWORD') ?: 'test',
    'charset' => 'utf8',
    'schemaMap' => [
        'pgsql' => 'yii\db\pgsql\Schema',
    ],
    'enableSchemaCache' => !YII_ENV_DEV,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
    'enableQueryCache' => !YII_ENV_DEV,
    'queryCacheDuration' => 3600,
    'queryCache' => 'cache',
    'enableLogging' => YII_ENV_DEV,
    'enableProfiling' => YII_ENV_DEV,
    'attributes' => [
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

// Configuración específica para entorno de desarrollo
if (YII_ENV_DEV) {
    $dbConfig['enableLogging'] = true;
    $dbConfig['enableProfiling'] = true;
    $dbConfig['enableSchemaCache'] = false;
    $dbConfig['enableQueryCache'] = false;
}

// Configuración específica para entorno de producción
if (YII_ENV_PROD) {
    $dbConfig['enableSchemaCache'] = true;
    $dbConfig['enableQueryCache'] = true;
    $dbConfig['schemaCacheDuration'] = 86400; // 24 horas
    $dbConfig['queryCacheDuration'] = 3600;   // 1 hora
}

return $dbConfig;