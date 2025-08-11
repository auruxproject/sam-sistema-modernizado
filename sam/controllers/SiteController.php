<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\helpers\Url;
use yii\data\SqlDataProvider;
use yii\export2excel\Export2ExcelBehavior;

use app\utils\db\utb;
use app\models\LoginForm;
use app\models\CbioclaveForm;
use app\models\ContactForm;
use app\models\ConvertForm;
use app\models\taux\tablaAux;
use app\models\SignupForm;
use app\models\PasswordResetRequestForm;
use app\models\ResetPasswordForm;
use yii\base\InvalidParamException;

/**
 * Controlador principal del sitio ISURGOB
 * Maneja autenticación, autorización y funcionalidades principales
 * 
 * @author Sistema ISURGOB
 * @version 2.0
 */
class SiteController extends Controller
{
    /**
     * Configuración de comportamientos del controlador
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup', 'about', 'config'],
                'rules' => [
                    [
                        'actions' => ['login', 'signup', 'error', 'logout', 'request-password-reset', 'reset-password'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['about', 'index', 'logout', 'config'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post', 'get'],
                    'delete' => ['post'],
                ],
            ],
            'export2excel' => [
                'class' => Export2ExcelBehavior::class,
                'prefixStr' => function() {
                    return Yii::$app->user->isGuest ? 'guest' : Yii::$app->user->identity->username;
                },
                'suffixStr' => date('Ymd-His'),
            ],
        ];
    }

    /**
     * Verificación de permisos antes de ejecutar acciones
     * @param \yii\base\Action $action
     * @return bool
     * @throws NotFoundHttpException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $operacion = str_replace('/', '-', Yii::$app->controller->route);

        // Acciones que siempre están permitidas
        $permitirSiempre = [
            'site-captcha', 'site-signup', 'site-index', 'site-error', 'site-contact', 
            'site-login', 'site-logout', 'site-about', 'site-cbioclave', 'site-pdflist', 
            'site-exportar', 'site-download', 'site-auxeditredirect', 'site-limpiarvariablereporte',
            'site-request-password-reset', 'site-reset-password'
        ];

        if (in_array($operacion, $permitirSiempre)) {
            return true;
        }

        // Verificar si el usuario está autenticado
        if (Yii::$app->user->isGuest) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['error' => 'Usuario no autenticado'];
            }
            return $this->render('nopermitido');
        }

        // Verificar si el usuario necesita cambiar la clave
        if (Yii::$app->session->get('user_sinclave', 0) == 1) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['error' => 'Debe cambiar su contraseña'];
            }
            return $this->render('cbioclave');
        }

        // Verificar permisos específicos de la acción
        if (!utb::getExisteAccion($operacion)) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['error' => 'Acción no permitida'];
            }
            return $this->render('nopermitido');
        }

        // Verificaciones específicas para auxedit
        if ($operacion == 'site-auxedit') {
            $t = Yii::$app->request->get('t', 0);
            if (!$t) {
                throw new BadRequestHttpException('Parámetro t requerido');
            }

            $procesotaux = utb::getCampo('sam.tabla_aux', 'cod=' . intval($t), 'accesocons');
            if (!utb::getExisteProceso($procesotaux)) {
                return $this->render('nopermitido');
            }
        }

        return true;
    }

    /**
     * Configuración de acciones del controlador
     * @return array
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'minLength' => 4,
                'maxLength' => 6,
            ],
            'download' => [
                'class' => 'yii\export2excel\DownloadAction',
            ],
        ];
    }

    /**
     * Página principal del sistema
     * @return string
     */
    public function actionIndex()
    {
        // Registrar acceso al sistema
        if (!Yii::$app->user->isGuest) {
            Yii::info('Usuario ' . Yii::$app->user->identity->username . ' accedió al sistema', 'app');
        }
        
        return $this->render('index');
    }

    /**
     * Acción de login
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            if (Yii::$app->session->get('user_sinclave', 0) == 1) {
                Yii::$app->session->set('user_sinclave', 0);
                return $this->redirect(['cbioclave']);
            }
            return $this->goHome();
        }

        $model = new LoginForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if (Yii::$app->session->get('user_sinclave', 0) == 1) {
                Yii::$app->session->set('user_sinclave', 0);
                return $this->redirect(['cbioclave']);
            }
            return $this->goHome();
        }

        return $this->render('login', [
            'model' => $model,
            'municipios' => $model->CargarMunicipios()
        ]);
    }

    /**
     * Acción de logout
     * @return Response
     */
    public function actionLogout()
    {
        if (!Yii::$app->user->isGuest) {
            // Registrar salida del usuario
            $username = Yii::$app->user->identity->username;
            (new LoginForm())->getGrabarSalida();
            Yii::$app->user->logout();
            Yii::info('Usuario ' . $username . ' cerró sesión', 'app');
        }

        return $this->goHome();
    }

    /**
     * Cambio de contraseña obligatorio
     * @return string|Response
     */
    public function actionCbioclave()
    {
        if (Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new CbioclaveForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->cbioclave()) {
            Yii::$app->session->setFlash('success', 'Contraseña actualizada correctamente');
            return $this->goBack();
        }

        return $this->render('cbioclave', [
            'model' => $model,
        ]);
    }

    /**
     * Página de contacto
     * @return string|Response
     */
    public function actionContact()
    {
        $model = new ContactForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->contact(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Gracias por contactarnos. Responderemos a la mayor brevedad posible.');
            } else {
                Yii::$app->session->setFlash('error', 'Se ha producido un error al enviar el correo electrónico.');
            }
            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Página de configuración del sistema
     * @return string
     */
    public function actionConfig()
    {
        return $this->render('config');
    }

    /**
     * Página acerca de
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Registro de nuevos usuarios
     * @return string|Response
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    Yii::$app->session->setFlash('success', 'Usuario registrado correctamente');
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Solicitud de restablecimiento de contraseña
     * @return string|Response
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Revise su correo electrónico para obtener más instrucciones.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Lo sentimos, no podemos restablecer la contraseña para el usuario proporcionado.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Restablecimiento de contraseña
     * @param string $token
     * @return string|Response
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'Nueva contraseña ha sido actualizada');
            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /***************************************  AUXILIARES  ****************************************************/
    
    /**
     * Página principal de tablas auxiliares
     * @return string
     */
    public function actionTaux()
    {
        return $this->render('//taux/taux');
    }

    /**
     * Edición de tablas auxiliares
     * @param int $t ID de la tabla auxiliar
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionAuxedit($t)
    {
        $t = intval($t);
        if (!$t) {
            throw new BadRequestHttpException('Parámetro t inválido');
        }

        $model = tablaAux::findOne($t);
        if (!$model) {
            throw new NotFoundHttpException('Tabla auxiliar no encontrada');
        }

        $consulta = 1; // Modo consulta por defecto
        $error = '';
        $mensaje = '';
        
        // Variables para partidas
        $part_nom = $part_nom2 = $part_nom3 = '';
        $nropart = $nropart2 = $nropart3 = '';
        
        // Procesar formulario si se envió
        if (Yii::$app->request->isPost) {
            $consulta = Yii::$app->request->post('txAccion', 1);
            $error = $this->procesarFormularioAux($model, $t, $consulta);
            
            if ($error === '') {
                $mensaje = ($consulta == 2) ? 'delete' : 'grabado';
                return $this->redirect(['auxeditredirect', 't' => $t, 'mensaje' => $mensaje, 'consulta' => $consulta]);
            }
        }

        // Configurar datos de la tabla
        $this->configurarTablaAux($model, $t);
        $tabla = $this->cargarDatosTabla($model, $t);
        
        // Manejar PJAX para partidas
        $this->manejarPjaxPartidas($part_id, $part_nom, $nropart, $part_id2, $part_nom2, $nropart2, $part_id3, $part_nom3, $nropart3);
        
        // Inicializar variables si no hay error
        if ($error === '') {
            $this->inicializarVariablesFormulario($cod, $nombre, $tercercampo, $tcalle, $bco_ent, $bco_suc, $domi, $tel, $resp, $sec_id, $part_id, $part_id2, $part_id3);
        }

        $url = $model->link ?: 'auxedit';
        
        return $this->render('//taux/' . $url, [
            'model' => $model,
            'tabla' => $tabla,
            'error' => $error,
            'mensaje' => $mensaje,
            'cod' => $cod ?? '',
            'nombre' => $nombre ?? '',
            'tercercampo' => $tercercampo ?? '',
            'tcalle' => $tcalle ?? 0,
            'bco_ent' => $bco_ent ?? '',
            'bco_suc' => $bco_suc ?? '',
            'domi' => $domi ?? '',
            'tel' => $tel ?? '',
            'resp' => $resp ?? '',
            'sec_id' => $sec_id ?? 0,
            'part_id' => $part_id ?? 0,
            'nropart' => $nropart,
            'part_nom' => $part_nom,
            'part_id2' => $part_id2 ?? 0,
            'nropart2' => $nropart2,
            'part_nom2' => $part_nom2,
            'part_id3' => $part_id3 ?? 0,
            'nropart3' => $nropart3,
            'part_nom3' => $part_nom3,
            'consulta' => $consulta
        ]);
    }

    /**
     * Redirección después de editar tabla auxiliar
     * @param int $t
     * @return Response
     */
    public function actionAuxeditredirect($t)
    {
        $mensaje = Yii::$app->request->get('mensaje', '');
        $consulta = Yii::$app->request->get('consulta', 1);
        
        return $this->redirect(['auxedit', 't' => $t, 'mensaje' => $mensaje, 'consulta' => $consulta]);
    }

    /***************************************  REPORTES PDF  ****************************************************/

    /**
     * Generar reporte PDF
     * @param string $format Formato del PDF
     * @return mixed
     */
    public function actionPdflist($format = 'A4-P')
    {
        if (!isset(Yii::$app->session['proceso_asig']) || !utb::getExisteProceso(Yii::$app->session['proceso_asig'])) {
            return $this->render('nopermitido');
        }

        $pdf = Yii::$app->pdf;
        if (strtoupper($format) !== 'A4-P') {
            $pdf->format = strtoupper($format);
        }

        $session = Yii::$app->session;
        $columnas = $session->get('columns', []);
        $sql = $session->get('sql', 'SELECT 1');
        $titulo = $session->get('titulo', '');
        $condicion = $session->get('condicion', '');

        $dataProvider = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => 1000,
            'pagination' => ['pageSize' => 1000],
        ]);

        $pdf->marginTop = '30px';
        $pdf->content = $this->renderPartial('//reportes/reportelist', [
            'columnas' => $columnas,
            'provider' => $dataProvider,
            'titulo' => $titulo,
            'condicion' => $condicion
        ]);

        return $pdf->render();
    }

    /***************************************  EXPORTAR  ****************************************************/

    /**
     * Exportar datos a diferentes formatos
     * @return string|void
     */
    public function actionExportar()
    {
        if (!isset(Yii::$app->session['proceso_asig']) || !utb::getExisteProceso(Yii::$app->session['proceso_asig'])) {
            return $this->render('nopermitido');
        }

        // Obtener datos
        if (isset(Yii::$app->session['query'])) {
            $array = Yii::$app->session['query']->createCommand()->queryAll();
        } else {
            $array = Yii::$app->db->createCommand(Yii::$app->session['sql'])->queryAll();
        }

        if (empty($array)) {
            Yii::$app->session->setFlash('warning', 'No hay datos para exportar');
            return $this->goBack();
        }

        if (Yii::$app->request->isPost && Yii::$app->request->post('rbFormato')) {
            $this->procesarExportacion($array);
        }

        return $this->render('exportar', ['count' => count($array)]);
    }

    /**
     * Generar reporte estático
     * @param array $columnas
     * @param string $sql
     * @param string $titulo
     * @param string $condicion
     * @param string $formato
     * @return mixed
     */
    public static function imprimirReporte($columnas, $sql, $titulo, $condicion, $formato = 'A4-P')
    {
        if (!isset(Yii::$app->session['proceso_asig']) || !utb::getExisteProceso(Yii::$app->session['proceso_asig'])) {
            return Yii::$app->controller->render('nopermitido');
        }

        $pdf = Yii::$app->pdf;
        if (strtoupper($formato) !== 'A4-P') {
            $pdf->format = strtoupper($formato);
        }

        $dataProvider = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => 1000,
            'pagination' => ['pageSize' => 1000],
        ]);

        $pdf->content = Yii::$app->controller->renderPartial('//reportes/reportelist', [
            'columnas' => $columnas,
            'provider' => $dataProvider,
            'titulo' => $titulo,
            'condicion' => $condicion
        ]);

        return $pdf->render();
    }

    /***************************************  MÉTODOS PRIVADOS  ****************************************************/

    /**
     * Procesar formulario de tabla auxiliar
     * @param tablaAux $model
     * @param int $t
     * @param int $consulta
     * @return string
     */
    private function procesarFormularioAux($model, $t, $consulta)
    {
        $request = Yii::$app->request;
        $error = '';

        if ($model->link === '') {
            // Tablas comunes
            $cod = $request->post('txCod', 0);
            $nombre = $request->post('txNombre', '');
            $tercercampo = $request->post('txTercerCampo', 0);

            if ($consulta != 2) {
                $error = $model->grabarTablaAux($consulta, $cod, $nombre, $tercercampo);
            } else {
                $error = $model->borrarTablaAux($cod);
            }
        } else {
            // Tablas con páginas específicas
            $error = $this->procesarTablasEspeciales($model, $t, $consulta, $request);
        }

        return $error;
    }

    /**
     * Procesar tablas auxiliares especiales
     * @param tablaAux $model
     * @param int $t
     * @param int $consulta
     * @param \yii\web\Request $request
     * @return string
     */
    private function procesarTablasEspeciales($model, $t, $consulta, $request)
    {
        $error = '';
        $cod = $request->post('txCod', 0);
        $nombre = $request->post('txNombre', '');

        switch ($t) {
            case 130: // domi_calle
                $tcalle = $request->post('tipo', 0);
                if ($consulta != 2) {
                    $error = $model->grabarTablaAuxDomiCalle($consulta, $cod, $nombre, $tcalle);
                }
                break;
                
            case 80: // banco_suc
                $bco_ent = $request->post('bco_ent', 0);
                $bco_suc = $request->post('bco_suc', 0);
                $domi = $request->post('domi', 0);
                $tel = $request->post('tel', 0);
                
                if ($consulta != 2) {
                    $error = $model->grabarTablaAuxBancoSuc($consulta, $bco_ent, $bco_suc, $nombre, $domi, $tel);
                } else {
                    $error = $model->borrarTablaAuxBancoSuc($bco_ent, $bco_suc);
                }
                break;
                
            case 133: // muni_oficina
                $resp = $request->post('resp', 0);
                $sec_id = $request->post('sec_id', 0);
                $part_id = $request->post('part_id', 0);
                
                if ($consulta != 2) {
                    $error = $model->grabarTablaAuxOficina($consulta, $cod, $nombre, $resp, $sec_id, $part_id);
                }
                break;
                
            case 139: // muni_sec
                $part_id = $request->post('part_id', 0);
                $part_id2 = $request->post('part_id2', 0);
                $part_id3 = $request->post('part_id3', 0);
                
                if ($consulta != 2) {
                    $error = $model->grabarTablaAuxSecretaria($consulta, $cod, $nombre, $part_id, $part_id2, $part_id3);
                }
                break;
        }

        // Borrado común para tablas especiales (excepto banco_suc)
        if ($consulta == 2 && !in_array($t, [80, 128])) {
            $error = $model->borrarTablaAux($cod);
        }

        return $error;
    }

    /**
     * Configurar modelo de tabla auxiliar
     * @param tablaAux $model
     * @param int $t
     */
    private function configurarTablaAux($model, $t)
    {
        if ($model->link === '') {
            $model->nombrelong = tablaAux::GetCampoLong($model->nombre, 'nombre');
            $model->codlong = ($model->tcod == 'N' ? 4 : tablaAux::GetCampoLong($model->nombre, 'cod'));
            if ($t == 211) {
                $model->codlong = 6;
            }
            $model->CargarTercerCampo();
        }
    }

    /**
     * Cargar datos de la tabla
     * @param tablaAux $model
     * @param int $t
     * @return array
     */
    private function cargarDatosTabla($model, $t)
    {
        if ($model->link === '') {
            return tablaAux::CargarTabla($model->nombre, $model->tercercamponom);
        }

        switch ($t) {
            case 37:
                return tablaAux::CargarTabla('cem_cuadro');
            case 133:
                return (new tablaAux())->CargarTablaOficina();
            case 139:
                return (new tablaAux())->CargarTablaSecretaria();
            default:
                return tablaAux::CargarTabla($model->nombre);
        }
    }

    /**
     * Manejar solicitudes PJAX para partidas
     * @param int $part_id
     * @param string $part_nom
     * @param string $nropart
     * @param int $part_id2
     * @param string $part_nom2
     * @param string $nropart2
     * @param int $part_id3
     * @param string $part_nom3
     * @param string $nropart3
     */
    private function manejarPjaxPartidas(&$part_id, &$part_nom, &$nropart, &$part_id2, &$part_nom2, &$nropart2, &$part_id3, &$part_nom3, &$nropart3)
    {
        $request = Yii::$app->request;
        $pjax = $request->get('_pjax', '');
        $anio = date('Y');
        
        if ($pjax === '#pjaxCambiaPartida') {
            $nropart = $request->get('partida', 0);
            list($part_id, $part_nom) = $this->buscarPartida($nropart, $anio);
        } elseif ($pjax === '#pjaxCambiaPartida2') {
            $nropart2 = $request->get('partida2', 0);
            list($part_id2, $part_nom2) = $this->buscarPartida($nropart2, $anio);
        } elseif ($pjax === '#pjaxCambiaPartida3') {
            $nropart3 = $request->get('partida3', 0);
            list($part_id3, $part_nom3) = $this->buscarPartida($nropart3, $anio);
        }
    }

    /**
     * Buscar información de partida
     * @param string $nropart
     * @param int $anio
     * @return array
     */
    private function buscarPartida($nropart, $anio)
    {
        $cond = 'tiene_hijo=false AND anio = ' . $anio;
        
        if (strpos($nropart, '.') > -1) {
            $cond .= " and (formato = '" . $nropart . "' OR formatoaux = '" . $nropart . "')";
        } else {
            $cond .= " and (nropart = " . intval($nropart) . " or part_id=" . intval($nropart) . ")";
        }
        
        $part_id = intval(utb::getCampo("fin.v_part", $cond, "part_id"));
        $part_nom = utb::getCampo("fin.v_part", "part_id=" . $part_id, "nombre");
        
        return [$part_id, $part_nom];
    }

    /**
     * Inicializar variables del formulario
     */
    private function inicializarVariablesFormulario(&$cod, &$nombre, &$tercercampo, &$tcalle, &$bco_ent, &$bco_suc, &$domi, &$tel, &$resp, &$sec_id, &$part_id, &$part_id2, &$part_id3)
    {
        $cod = $nombre = $tercercampo = $bco_ent = $bco_suc = $domi = $tel = $resp = '';
        $tcalle = $sec_id = $part_id = $part_id2 = $part_id3 = 0;
    }

    /**
     * Procesar exportación de datos
     * @param array $array
     */
    private function procesarExportacion($array)
    {
        $request = Yii::$app->request;
        $formato = $request->post('rbFormato');
        $titulo = $request->post('txTitulo', Yii::$app->session->get('titulo', 'Exportación'));
        $desc = $request->post('txDetalle', Yii::$app->session->get('condicion', ''));
        
        switch ($formato) {
            case 'E': // Excel
                $this->exportarExcel($array, $titulo, $desc);
                break;
            case 'L': // LibreOffice
                $this->exportarLibreOffice($array, $titulo);
                break;
            case 'T': // Texto
                $this->exportarTexto($array, $titulo, $request);
                break;
        }
    }

    /**
     * Exportar a Excel
     * @param array $array
     * @param string $titulo
     * @param string $desc
     */
    private function exportarExcel($array, $titulo, $desc)
    {
        $exportar = [];
        foreach ($array as $i => $item) {
            $fila = [];
            foreach ($item as $clave => $valor) {
                foreach (Yii::$app->session['columns'] as $column) {
                    if ($column['attribute'] === $clave) {
                        $fila[$column['label']] = $valor;
                        break;
                    }
                }
            }
            $exportar[$i] = $fila;
        }

        $excel_data = Export2ExcelBehavior::excelDataFormat($exportar);
        $excel_content = [[
            'sheet_name' => 'Listado',
            'sheet_title' => $excel_data['excel_title'],
            'ceils' => $excel_data['excel_ceils'],
            'headerColor' => Export2ExcelBehavior::getCssClass("header"),
        ]];

        $excel_props = [
            'creator' => Yii::$app->params['muni_name'] ?? 'ISURGOB',
            'title' => $titulo,
            'subject' => '',
            'desc' => $desc,
            'keywords' => '',
            'category' => ''
        ];

        $this->export2excel($excel_content, $titulo, $excel_props);
    }

    /**
     * Exportar a LibreOffice
     * @param array $array
     * @param string $titulo
     */
    private function exportarLibreOffice($array, $titulo)
    {
        $tabla = '<table>';
        $tablaT = '<tr>';
        
        // Encabezados
        foreach (Yii::$app->session['columns'] as $column) {
            $tablaT .= '<td>' . $column['label'] . '</td>';
        }
        $tablaT .= '</tr>';
        
        // Datos
        foreach ($array as $item) {
            $tabla .= '<tr>';
            foreach ($item as $clave => $valor) {
                foreach (Yii::$app->session['columns'] as $column) {
                    if ($column['attribute'] === $clave) {
                        $tabla .= '<td>' . $valor . '</td>';
                        break;
                    }
                }
            }
            $tabla .= '</tr>';
        }
        $tabla = $tabla . '</table>';

        header("Content-type: application/vnd.oasis.opendocument.spreadsheet");
        header("Content-Disposition: attachment; filename=\"$titulo.ods\";");
        echo $tablaT . $tabla;
        exit;
    }

    /**
     * Exportar a texto
     * @param array $array
     * @param string $titulo
     * @param \yii\web\Request $request
     */
    private function exportarTexto($array, $titulo, $request)
    {
        // Configurar delimitadores
        $delimitadores = [
            'T' => chr(9), // Tab
            'L' => '|',    // Línea Vertical
            'C' => ',',    // Coma
            'P' => ';',    // Punto y Coma
            'O' => $request->post('txOtroDelim', ',')
        ];
        
        $separadores = [
            'LF' => chr(10),
            'CR' => chr(13)
        ];
        
        $dc = $delimitadores[$request->post('rbDelimitador', 'C')];
        $sf = $separadores[$request->post('rbSepFila', 'LF')];
        $incluirTitulo = $request->post('ckIncTitulo', 0);
        
        $contenido = '';
        
        // Agregar títulos si se solicita
        if ($incluirTitulo) {
            $titulos = [];
            foreach (Yii::$app->session['columns'] as $column) {
                $titulos[] = $column['label'];
            }
            $contenido .= implode($dc, $titulos) . $sf;
        }
        
        // Agregar datos
        foreach ($array as $item) {
            $fila = [];
            foreach ($item as $clave => $valor) {
                foreach (Yii::$app->session['columns'] as $column) {
                    if ($column['attribute'] === $clave) {
                        $fila[] = $valor;
                        break;
                    }
                }
            }
            $contenido .= implode($dc, $fila) . $sf;
        }
        
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=\"$titulo.txt\";");
        echo $contenido;
        exit;
    }
}
