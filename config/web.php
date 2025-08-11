<?php

/**
 * Configuración principal de la aplicación ISURGOB
 * Sistema de Administración Municipal modernizado
 * Compatible con EasyPanel y PHP 7.4+
 */

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'isurgob-app',
    'name' => 'ISURGOB - Sistema de Administración Municipal',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'modules' => [
        'sam' => [
            'class' => 'app\modules\sam\Module',
        ],
        'samseg' => [
            'class' => 'app\modules\samseg\Module',
        ],
        'samtrib' => [
            'class' => 'app\modules\samtrib\Module',
        ],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'isurgob-secure-key-2024',
            'csrfParam' => '_csrf-isurgob',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            // Configuración para EasyPanel/Traefik
            'trustedHosts' => [
                getenv('TRUSTED_HOSTS') ?: '*.easypanel.host',
                'localhost',
                '127.0.0.1',
            ],
            'secureHeaders' => [
                'X-Forwarded-For',
                'X-Forwarded-Host', 
                'X-Forwarded-Proto',
                'X-Forwarded-Port',
                'X-Real-IP',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'],
            'authTimeout' => 3600, // 1 hora
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
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
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/app.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 5,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['yii\db\*'],
                    'logFile' => '@runtime/logs/db.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 5,
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'suffix' => '',
            'normalizer' => [
                'class' => 'yii\web\UrlNormalizer',
                'action' => \yii\web\UrlNormalizer::ACTION_REDIRECT_TEMPORARY,
            ],
            'rules' => [
                // Health check endpoints para EasyPanel
                'health' => 'site/health',
                'status' => 'site/health', 
                'ping' => 'site/health',
                
                '' => 'site/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ],
        ],
        'session' => [
            'class' => 'yii\web\Session',
            'timeout' => 3600,
            'cookieParams' => [
                'httpOnly' => true,
                'secure' => !YII_ENV_DEV && (isset($_SERVER['HTTPS']) || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])),
                'sameSite' => 'Lax',
            ],
            'useCookies' => true,
            'regenerateIdOnLogin' => true,
        ],
        'security' => [
            'class' => 'yii\base\Security',
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'dd/MM/yyyy',
            'datetimeFormat' => 'dd/MM/yyyy HH:mm:ss',
            'timeFormat' => 'HH:mm:ss',
            'defaultTimeZone' => 'America/Argentina/Buenos_Aires',
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap' => [
                        'app' => 'app.php',
                        'app/error' => 'error.php',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
    'language' => 'es-AR',
    'charset' => 'UTF-8',
    'timeZone' => 'America/Argentina/Buenos_Aires',
];

// Configuración para entorno de desarrollo
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1', '::1', '192.168.*.*', '10.*.*.*'],
        'panels' => [
            'config' => ['class' => 'yii\debug\panels\ConfigPanel'],
            'request' => ['class' => 'yii\debug\panels\RequestPanel'],
            'log' => ['class' => 'yii\debug\panels\LogPanel'],
            'profiling' => ['class' => 'yii\debug\panels\ProfilingPanel'],
            'db' => ['class' => 'yii\debug\panels\DbPanel'],
            'assets' => ['class' => 'yii\debug\panels\AssetPanel'],
            'mail' => ['class' => 'yii\debug\panels\MailPanel'],
        ],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['127.0.0.1', '::1', '192.168.*.*', '10.*.*.*'],
        'generators' => [
            'crud' => [
                'class' => 'yii\gii\generators\crud\Generator',
                'templates' => [
                    'default' => '@app/templates/crud',
                ],
            ],
            'model' => [
                'class' => 'yii\gii\generators\model\Generator',
                'templates' => [
                    'default' => '@app/templates/model',
                ],
            ],
        ],
    ];
}

// Configuración para entorno de producción
if (YII_ENV_PROD) {
    $config['components']['cache'] = [
        'class' => 'yii\redis\Cache',
        'redis' => [
            'hostname' => getenv('REDIS_HOST') ?: 'localhost',
            'port' => getenv('REDIS_PORT') ?: 6379,
            'database' => 0,
        ],
    ];
}

return $config;