<?php

/**
 * Parámetros de configuración para ISURGOB
 * Sistema de Administración Municipal
 * Configuración centralizada y modernizada
 */

return [
    // Información del municipio
    'municipality' => [
        'name' => getenv('MUNI_NAME') ?: 'Municipalidad de Trevelin',
        'address' => getenv('MUNI_ADDRESS') ?: 'Domicilio Municipal',
        'phone' => getenv('MUNI_PHONE') ?: '4223344/5',
        'email' => getenv('MUNI_EMAIL') ?: 'rentas@municipalidad.gob.ar',
        'website' => getenv('MUNI_WEBSITE') ?: 'https://www.municipalidad.gob.ar',
        'cuit' => getenv('MUNI_CUIT') ?: '',
        'logo' => getenv('MUNI_LOGO') ?: '/images/logo_sam.gif',
    ],

    // Configuración del sistema
    'system' => [
        'id' => (int) (getenv('SIS_ID') ?: 3),
        'version' => '2.0.0',
        'environment' => YII_ENV,
        'debug' => YII_DEBUG,
        'timezone' => 'America/Argentina/Buenos_Aires',
        'language' => 'es-AR',
        'charset' => 'UTF-8',
    ],

    // Configuración de administración
    'admin' => [
        'email' => getenv('ADMIN_EMAIL') ?: 'sistemas@aari.com.ar',
        'name' => getenv('ADMIN_NAME') ?: 'Administrador del Sistema',
        'phone' => getenv('ADMIN_PHONE') ?: '',
    ],

    // Configuración de seguridad
    'security' => [
        'passwordMinLength' => 8,
        'passwordRequireSpecialChars' => true,
        'sessionTimeout' => 3600, // 1 hora
        'maxLoginAttempts' => 5,
        'lockoutDuration' => 900, // 15 minutos
        'enableCsrfValidation' => true,
        'enableCookieValidation' => true,
    ],

    // Configuración de archivos y uploads
    'files' => [
        'maxUploadSize' => '10M',
        'allowedExtensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'],
        'uploadPath' => '@webroot/uploads',
        'tempPath' => '@runtime/temp',
    ],

    // Configuración de reportes
    'reports' => [
        'defaultFormat' => 'pdf',
        'allowedFormats' => ['pdf', 'excel', 'csv'],
        'maxRecords' => 10000,
        'cacheDuration' => 3600,
    ],

    // Configuración de paginación
    'pagination' => [
        'defaultPageSize' => 20,
        'pageSizeLimit' => [1, 100],
        'pageParam' => 'page',
        'pageSizeParam' => 'per-page',
    ],

    // Configuración de formularios (templates HTML)
    'form' => [
        'templates' => [
            'field' => "{label}\n{input}\n{hint}\n{error}",
            'fieldHorizontal' => "<div class='form-group'><div class='col-sm-3'>{label}</div><div class='col-sm-9'>{input}{hint}{error}</div></div>",
            'fieldInline' => "<div class='form-group'>{label} {input} {hint} {error}</div>",
        ],
        'options' => [
            'class' => 'form-horizontal',
            'role' => 'form',
        ],
    ],

    // Templates de tabla (compatibilidad con versión anterior)
    'table' => [
        'T_TAB_COL1' => "<td>{label}</td><td align='left'>{input}\n{hint}</td>",
        'T_TAB_COL1_LIN2' => "<td align='left' valign='top'>{label}<br>{input}\n{hint}</td>",
        'T_TAB_COL1_3' => "<td align='right'>{label}</td><td align='left'>{input}\n{hint}</td>",
        'T_TAB_COL2' => "<td align='left'>{label}{input}\n{hint}</td>",
        'T_TAB_COL2_LIN2' => "<td align='left' valign='top' colspan='2'>{label}<br>{input}\n{hint}</td>",
        'T_TAB_COL3' => "<td align='left' colspan='4'>{label}{input}\n{hint}</td>",
        'T_TAB_COL4' => "<td>{label}</td><td align='left' colspan='4'>{input}\n{hint}</td>",
        'T_TAB_COL5' => "<td align='left' colspan='2'>{label}{input}\n{hint}</td>",
        'T_TAB_COL10' => "<td>{label}</td><td align='left' colspan='10'>{input}\n{hint}</td>",
        'T_TAB_COL1_CHECK1' => "<td>{label}</td><td><input type='checkbox' id='calcdesc-check1'></td><td align='left'>{input}\n{hint}</td>",
        'T_TAB_COL1_CHECK2' => "<td>{label}</td><td><input type='checkbox' id='calcdesc-check2'></td><td align='left'>{input}\n{hint}</td>",
        'T_DIV' => "<div>{label}{input}\n{hint}</div>",
    ],

    // Configuración de módulos
    'modules' => [
        'sam' => [
            'enabled' => true,
            'name' => 'SAM - Sistema de Administración Municipal',
            'description' => 'Módulo principal de administración',
        ],
        'samseg' => [
            'enabled' => true,
            'name' => 'SAM Seguridad',
            'description' => 'Módulo de seguridad y usuarios',
        ],
        'samtrib' => [
            'enabled' => true,
            'name' => 'SAM Tributario',
            'description' => 'Módulo de gestión tributaria',
        ],
    ],

    // Configuración de API
    'api' => [
        'version' => 'v1',
        'rateLimit' => [
            'enabled' => true,
            'requests' => 1000,
            'window' => 3600, // 1 hora
        ],
        'cors' => [
            'enabled' => true,
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowedHeaders' => ['Content-Type', 'Authorization'],
        ],
    ],

    // Configuración de caché
    'cache' => [
        'duration' => 3600,
        'keyPrefix' => 'isurgob_',
        'serializer' => false,
    ],

    // Configuración de logs
    'logging' => [
        'level' => YII_ENV_DEV ? 'debug' : 'error',
        'maxFileSize' => '10MB',
        'maxFiles' => 5,
        'rotateByCopy' => true,
    ],

    // URLs y rutas importantes
    'urls' => [
        'base' => getenv('APP_URL') ?: 'http://localhost',
        'assets' => '/assets',
        'uploads' => '/uploads',
        'api' => '/api/v1',
    ],

    // Configuración de notificaciones
    'notifications' => [
        'email' => [
            'enabled' => true,
            'from' => getenv('MAIL_FROM') ?: 'noreply@municipalidad.gob.ar',
            'fromName' => getenv('MAIL_FROM_NAME') ?: 'Sistema ISURGOB',
        ],
        'sms' => [
            'enabled' => false,
            'provider' => '',
            'apiKey' => '',
        ],
    ],

    // Configuración de backup
    'backup' => [
        'enabled' => true,
        'schedule' => 'daily',
        'retention' => 30, // días
        'path' => '@runtime/backups',
        'compress' => true,
    ],

    // Configuración de mantenimiento
    'maintenance' => [
        'enabled' => false,
        'message' => 'El sistema se encuentra en mantenimiento. Intente más tarde.',
        'allowedIPs' => ['127.0.0.1', '::1'],
    ],
];