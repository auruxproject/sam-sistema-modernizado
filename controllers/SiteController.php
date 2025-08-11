<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\User;

/**
 * Controlador principal del sitio
 * Compatible con EasyPanel y Traefik
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\\web\\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\\captcha\\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Página principal
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }
        
        return $this->render('index');
    }

    /**
     * Login action
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    /**
     * Health check endpoint para EasyPanel
     * Verifica el estado del sistema y componentes críticos
     */
    public function actionHealth()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $checks = [];
        $overallStatus = 'healthy';
        
        try {
            // Verificar base de datos
            $dbCheck = $this->checkDatabase();
            $checks['database'] = $dbCheck;
            if (!$dbCheck['status']) {
                $overallStatus = 'unhealthy';
            }
            
            // Verificar sistema de archivos
            $fsCheck = $this->checkFileSystem();
            $checks['filesystem'] = $fsCheck;
            if (!$fsCheck['status']) {
                $overallStatus = 'unhealthy';
            }
            
            // Verificar configuración PHP
            $phpCheck = $this->checkPhpConfig();
            $checks['php'] = $phpCheck;
            if (!$phpCheck['status']) {
                $overallStatus = 'unhealthy';
            }
            
            // Verificar espacio en disco
            $diskCheck = $this->checkDiskSpace();
            $checks['disk'] = $diskCheck;
            if (!$diskCheck['status']) {
                $overallStatus = 'unhealthy';
            }
            
        } catch (\Exception $e) {
            $overallStatus = 'unhealthy';
            $checks['error'] = [
                'status' => false,
                'message' => 'Health check failed: ' . $e->getMessage()
            ];
        }
        
        $response = [
            'status' => $overallStatus,
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'environment' => YII_ENV,
            'checks' => $checks
        ];
        
        // Establecer código de respuesta HTTP apropiado
        if ($overallStatus !== 'healthy') {
            Yii::$app->response->statusCode = 503; // Service Unavailable
        }
        
        return $response;
    }
    
    /**
     * Verificar conexión a base de datos
     */
    private function checkDatabase(): array
    {
        try {
            $db = Yii::$app->db;
            $db->open();
            
            // Ejecutar una consulta simple
            $command = $db->createCommand('SELECT 1');
            $result = $command->queryScalar();
            
            return [
                'status' => $result === 1,
                'message' => 'Database connection successful',
                'driver' => $db->driverName
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar sistema de archivos
     */
    private function checkFileSystem(): array
    {
        $runtimePath = Yii::getAlias('@runtime');
        $webPath = Yii::getAlias('@webroot');
        
        $checks = [];
        $allGood = true;
        
        // Verificar directorio runtime
        if (!is_dir($runtimePath)) {
            $checks['runtime_exists'] = false;
            $allGood = false;
        } elseif (!is_writable($runtimePath)) {
            $checks['runtime_writable'] = false;
            $allGood = false;
        } else {
            $checks['runtime'] = true;
        }
        
        // Verificar directorio web
        if (!is_dir($webPath)) {
            $checks['web_exists'] = false;
            $allGood = false;
        } else {
            $checks['web'] = true;
        }
        
        return [
            'status' => $allGood,
            'message' => $allGood ? 'Filesystem checks passed' : 'Filesystem issues detected',
            'details' => $checks
        ];
    }
    
    /**
     * Verificar configuración PHP
     */
    private function checkPhpConfig(): array
    {
        $issues = [];
        
        // Verificar extensiones requeridas
        $requiredExtensions = ['pdo', 'pdo_pgsql', 'mbstring', 'openssl', 'json'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "Missing PHP extension: {$ext}";
            }
        }
        
        // Verificar límites de memoria
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1' && $this->parseBytes($memoryLimit) < 128 * 1024 * 1024) {
            $issues[] = "Low memory limit: {$memoryLimit}";
        }
        
        // Verificar tiempo de ejecución
        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime > 0 && $maxExecutionTime < 30) {
            $issues[] = "Low execution time: {$maxExecutionTime}s";
        }
        
        return [
            'status' => empty($issues),
            'message' => empty($issues) ? 'PHP configuration OK' : 'PHP configuration issues',
            'issues' => $issues,
            'php_version' => PHP_VERSION
        ];
    }
    
    /**
     * Verificar espacio en disco
     */
    private function checkDiskSpace(): array
    {
        $path = Yii::getAlias('@app');
        $freeBytes = disk_free_space($path);
        $totalBytes = disk_total_space($path);
        
        if ($freeBytes === false || $totalBytes === false) {
            return [
                'status' => false,
                'message' => 'Unable to check disk space'
            ];
        }
        
        $freePercent = ($freeBytes / $totalBytes) * 100;
        $lowSpace = $freePercent < 10; // Menos del 10% libre
        
        return [
            'status' => !$lowSpace,
            'message' => $lowSpace ? 'Low disk space' : 'Disk space OK',
            'free_space' => $this->formatBytes($freeBytes),
            'total_space' => $this->formatBytes($totalBytes),
            'free_percent' => round($freePercent, 2)
        ];
    }
    
    /**
     * Convertir string de bytes a número
     */
    private function parseBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    /**
     * Formatear bytes para lectura humana
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}