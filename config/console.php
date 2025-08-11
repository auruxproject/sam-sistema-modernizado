<?php

/**
 * Configuración de consola para ISURGOB
 * Comandos y tareas automatizadas del sistema
 */

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'isurgob-console',
    'name' => 'ISURGOB Console Application',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@runtime/logs/console.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 5,
                ],
            ],
        ],
        'db' => $db,
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => YII_ENV_DEV,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => getenv('MAIL_HOST') ?: 'localhost',
                'username' => getenv('MAIL_USERNAME') ?: '',
                'password' => getenv('MAIL_PASSWORD') ?: '',
                'port' => getenv('MAIL_PORT') ?: '587',
                'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
            ],
        ],
        'mutex' => [
            'class' => 'yii\mutex\FileMutex',
            'mutexPath' => '@runtime/mutex',
        ],
        'queue' => [
            'class' => 'yii\queue\file\Queue',
            'path' => '@runtime/queue',
        ],
    ],
    'params' => $params,
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@app/migrations',
                '@app/modules/sam/migrations',
                '@app/modules/samseg/migrations',
                '@app/modules/samtrib/migrations',
            ],
        ],
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'app\fixtures',
        ],
        'cache' => [
            'class' => 'yii\console\controllers\CacheController',
        ],
        'backup' => [
            'class' => 'app\commands\BackupController',
        ],
        'maintenance' => [
            'class' => 'app\commands\MaintenanceController',
        ],
        'reports' => [
            'class' => 'app\commands\ReportsController',
        ],
        'cleanup' => [
            'class' => 'app\commands\CleanupController',
        ],
    ],
];

// Configuración específica para entorno de desarrollo
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;