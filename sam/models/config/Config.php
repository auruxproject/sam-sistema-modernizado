<?php

namespace app\models\config;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * This is the model class for table "sam.config".
 *
 * @property integer $ctarecargo
 * @property integer $ctarecargotc
 * @property integer $interes_min
 * @property integer $ctaredondeo
 * @property float $porcredondeo
 * @property integer $ctacte_anio_desde
 * @property string $texto_ucm
 * @property float $ucm1
 * @property float $ucm2
 * @property string $titulo_libredeuda
 * @property string $titulo2_libredeuda
 * @property string $mensaje_libredeuda
 * @property integer $proxvenc_libredeuda
 * @property integer $copias_recl
 * @property integer $calle_recl
 * @property string $path_recl
 * @property boolean $usar_codcalle_loc
 * @property boolean $usar_codcalle_noloc
 * @property boolean $inm_valida_nc
 * @property boolean $inm_valida_frente
 * @property boolean $inm_gen_osm
 * @property integer $trib_op_matric
 * @property integer $judi_item_gasto
 * @property integer $judi_item_hono
 * @property integer $ctadiferencia
 * @property integer $itemcobro
 * @property integer $itemcomision
 * @property integer $itemcomisionbco
 * @property integer $cajaverifdebito
 * @property boolean $repo_usu_nom
 * @property integer $djfaltantes
 * @property boolean $op_hab_plazas
 * @property boolean $per_plan_decaido
 * @property integer $comer_hab_vence
 * @property integer $juz_origentransito1
 * @property integer $juz_origentransito2
 * @property string $ib_modo
 * @property boolean $per_pedir_cuit
 * @property boolean $per_pedir_doc
 * @property integer $com_validar_ib
 * @property integer $ret_sin_aprob
 * @property integer $inm_phmadre
 * @property integer $cta_id_act
 * @property string $agrete_path
 * @property string $bol_path
 * @property string $bol_mail
 * @property string $bol_mail_clave
 * @property string $bol_mail_host
 * @property integer $bol_mail_port
 */
class Config extends ActiveRecord
{
    // Validation constants
    const MIN_INTERES = 0;
    const MAX_INTERES = 100;
    const MIN_CTACTE_ANIO = 5;
    const MAX_CTACTE_ANIO = 20;
    const MIN_COMER_HAB_VENCE = 0;
    const MAX_COMER_HAB_VENCE = 12;
    const MIN_COPIAS_RECL = 1;
    const MAX_COPIAS_RECL = 10;
    
    // Redondeo options
    const REDONDEO_NINGUNO = 0;
    const REDONDEO_DECIMO = 1;
    const REDONDEO_CUARTO = 2;
    const REDONDEO_MEDIO = 3;
    const REDONDEO_ENTERO = 4;
    
    // IB Mode options
    const IB_MODO_AUTOMATICO = 'A';
    const IB_MODO_MANUAL = 'M';
    
    // Caja verification options
    const CAJA_VERIF_NINGUNA = 0;
    const CAJA_VERIF_ADVERTIR = 1;
    const CAJA_VERIF_BLOQUEAR = 2;
    
    public $usar_codcalle_noloc;
    
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();
        
        // Set default values
        $this->setDefaultValues();
    }
    
    /**
     * Sets default values for the model
     */
    private function setDefaultValues(): void
    {
        $defaults = [
            'ctarecargo' => 0,
            'ctarectc' => 0,
            'ctaredondeo' => 0,
            'ctadiferencia' => 0,
            'judi_item_gasto' => 0,
            'judi_item_hono' => 0,
            'itemcobro' => 0,
            'itemcomision' => 0,
            'itemcomisionbco' => 0,
            'comer_hab_vence' => 0,
            'interes_min' => 0,
            'ctacte_anio_desde' => date('Y') - 5,
            'ucm1' => 0.0,
            'ucm2' => 0.0,
            'copias_recl' => 1,
            'usar_codcalle_noloc' => false,
            'juz_origentransito1' => 0,
            'juz_origentransito2' => 0,
            'cajaverifdebito' => 0,
            'djfaltantes' => 0,
            'inm_phmadre' => 0,
            'cta_id_act' => 0,
            'bol_mail_port' => 587,
            'ib_modo' => self::IB_MODO_AUTOMATICO,
            'path_recl' => './uploads/recl/',
            'bol_path' => './uploads/bol/',
        ];
        
        foreach ($defaults as $attribute => $value) {
            if ($this->$attribute === null) {
                $this->$attribute = $value;
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'sam.config';
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Required fields
            [[
                'ctarecargo', 'interes_min', 'ctaredondeo', 'ctacte_anio_desde',
                'proxvenc_libredeuda', 'ctadiferencia', 'comer_hab_vence', 'ucm1', 'ucm2'
            ], 'required'],
            
            // Boolean fields
            [[
                'usar_codcalle_loc', 'inm_valida_nc', 'inm_valida_frente', 'inm_gen_osm',
                'repo_usu_nom', 'op_hab_plazas', 'per_plan_decaido', 'calle_recl',
                'per_pedir_cuit', 'per_pedir_doc'
            ], 'boolean'],
            
            // Numeric fields with minimum values
            [['porcredondeo', 'ucm1', 'ucm2'], 'number', 'min' => 0],
            
            // Integer fields with validation
            [[
                'ctarecargo', 'cta_id_act', 'ctaredondeo', 'ctarectc', 'ctadiferencia',
                'juz_origentransito1', 'juz_origentransito2', 'bol_mail_port'
            ], 'integer', 'min' => 0],
            
            // Interest validation
            ['interes_min', 'integer', 'min' => self::MIN_INTERES, 'max' => self::MAX_INTERES],
            
            // Account year validation
            ['ctacte_anio_desde', 'integer', 'min' => self::MIN_CTACTE_ANIO, 'max' => self::MAX_CTACTE_ANIO],
            
            // Commercial validation
            ['comer_hab_vence', 'integer', 'min' => self::MIN_COMER_HAB_VENCE, 'max' => self::MAX_COMER_HAB_VENCE],
            
            // Copies validation
            ['copias_recl', 'integer', 'min' => self::MIN_COPIAS_RECL, 'max' => self::MAX_COPIAS_RECL],
            
            // Range validations
            [['djfaltantes', 'cajaverifdebito'], 'integer', 'min' => 0, 'max' => 2],
            [['ret_sin_aprob', 'inm_phmadre'], 'integer', 'min' => 0, 'max' => 1],
            
            // Item validations
            [[
                'itemcobro', 'itemcomision', 'itemcomisionbco', 'trib_op_matric',
                'judi_item_gasto', 'judi_item_hono'
            ], 'integer', 'min' => 0],
            
            // String validations
            ['ib_modo', 'string', 'max' => 1],
            ['ib_modo', 'in', 'range' => [self::IB_MODO_AUTOMATICO, self::IB_MODO_MANUAL]],
            
            ['texto_ucm', 'string', 'max' => 10],
            [['titulo_libredeuda', 'titulo2_libredeuda', 'agrete_path'], 'string', 'max' => 100],
            [['path_recl', 'bol_path', 'bol_mail', 'bol_mail_clave', 'bol_mail_host'], 'string', 'max' => 50],
            ['mensaje_libredeuda', 'string', 'max' => 500],
            
            // Email validation
            ['bol_mail', 'email'],
            
            // Path validations
            ['path_recl', 'validatePath'],
            ['bol_path', 'validatePath'],
            ['agrete_path', 'validatePath'],
            
            // Redondeo validation
            ['porcredondeo', 'in', 'range' => [
                self::REDONDEO_NINGUNO, self::REDONDEO_DECIMO, self::REDONDEO_CUARTO,
                self::REDONDEO_MEDIO, self::REDONDEO_ENTERO
            ]],
            
            // Trim strings
            [[
                'texto_ucm', 'titulo_libredeuda', 'titulo2_libredeuda', 'mensaje_libredeuda',
                'path_recl', 'bol_path', 'bol_mail', 'bol_mail_clave', 'bol_mail_host',
                'agrete_path', 'ib_modo'
            ], 'trim'],
            
            // Safe attributes for mass assignment
            [[
                'ctarecargo', 'ctarectc', 'interes_min', 'ctaredondeo', 'porcredondeo',
                'ctacte_anio_desde', 'texto_ucm', 'ucm1', 'ucm2', 'titulo_libredeuda',
                'titulo2_libredeuda', 'mensaje_libredeuda', 'proxvenc_libredeuda',
                'copias_recl', 'calle_recl', 'path_recl', 'usar_codcalle_loc',
                'inm_valida_nc', 'inm_valida_frente', 'inm_gen_osm', 'trib_op_matric',
                'judi_item_gasto', 'judi_item_hono', 'ctadiferencia', 'itemcobro',
                'itemcomision', 'itemcomisionbco', 'cajaverifdebito', 'repo_usu_nom',
                'djfaltantes', 'op_hab_plazas', 'per_plan_decaido', 'comer_hab_vence',
                'juz_origentransito1', 'juz_origentransito2', 'ib_modo', 'per_pedir_cuit',
                'per_pedir_doc', 'com_validar_ib', 'ret_sin_aprob', 'inm_phmadre',
                'cta_id_act', 'agrete_path', 'bol_path', 'bol_mail', 'bol_mail_clave',
                'bol_mail_host', 'bol_mail_port'
            ], 'safe'],
        ];
    }
    
    /**
     * Validates path fields
     *
     * @param string $attribute
     * @param array $params
     */
    public function validatePath(string $attribute, array $params): void
    {
        if (!empty($this->$attribute)) {
            $path = $this->$attribute;
            
            // Check for directory traversal attempts
            if (strpos($path, '..') !== false) {
                $this->addError($attribute, 'La ruta no puede contener ".."');
                return;
            }
            
            // Ensure path ends with slash for directories
            if (!empty($path) && substr($path, -1) !== '/' && substr($path, -1) !== '\\') {
                $this->$attribute = $path . '/';
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'ctarecargo' => 'Cuenta Recargo',
            'ctarectc' => 'Cuenta Recargo TC',
            'cta_id_act' => 'Cuenta ID Actividad',
            'interes_min' => 'Interés Mínimo (%)',
            'ctaredondeo' => 'Cuenta Redondeo',
            'porcredondeo' => 'Porcentaje Redondeo',
            'ctacte_anio_desde' => 'Cuenta Corriente Año Desde',
            'texto_ucm' => 'Texto UCM',
            'ucm1' => 'UCM 1',
            'ucm2' => 'UCM 2',
            'titulo_libredeuda' => 'Título Libre Deuda',
            'titulo2_libredeuda' => 'Título 2 Libre Deuda',
            'mensaje_libredeuda' => 'Mensaje Libre Deuda',
            'proxvenc_libredeuda' => 'Próximo Vencimiento Libre Deuda',
            'copias_recl' => 'Cantidad de Copias para Reclamos',
            'calle_recl' => 'Calle Reclamos',
            'path_recl' => 'Ruta Reclamos',
            'usar_codcalle_loc' => 'Usar Código Calle Local',
            'usar_codcalle_noloc' => 'Usar Código Calle No Local',
            'inm_valida_nc' => 'Inmueble Valida NC',
            'inm_valida_frente' => 'Inmueble Valida Frente',
            'inm_gen_osm' => 'Inmueble Generar OSM',
            'trib_op_matric' => 'Tributo Operación Matrícula',
            'judi_item_gasto' => 'Judicial Item Gasto',
            'judi_item_hono' => 'Judicial Item Honorarios',
            'ctadiferencia' => 'Cuenta Diferencia',
            'itemcobro' => 'Item Cobro',
            'itemcomision' => 'Item Comisión',
            'itemcomisionbco' => 'Item Comisión Banco',
            'cajaverifdebito' => 'Caja Verificación Débito',
            'repo_usu_nom' => 'Reporte Usuario Nombre',
            'djfaltantes' => 'DJ Faltantes',
            'op_hab_plazas' => 'Operación Habilitar Plazas',
            'per_plan_decaido' => 'Persona Plan Decaído',
            'comer_hab_vence' => 'Comercio Habilitación Vence (meses)',
            'juz_origentransito1' => 'Juzgado Origen Tránsito 1',
            'juz_origentransito2' => 'Juzgado Origen Tránsito 2',
            'ib_modo' => 'Modo Ingresos Brutos',
            'per_pedir_cuit' => 'Persona Pedir CUIT',
            'per_pedir_doc' => 'Persona Pedir Documento',
            'com_validar_ib' => 'Comercio Validar IB',
            'ret_sin_aprob' => 'Retención Sin Aprobación',
            'inm_phmadre' => 'Inmueble PH Madre',
            'agrete_path' => 'Ruta Agrete',
            'bol_path' => 'Ruta Boletas',
            'bol_mail' => 'Email Boletas',
            'bol_mail_clave' => 'Clave Email Boletas',
            'bol_mail_host' => 'Host Email Boletas',
            'bol_mail_port' => 'Puerto Email Boletas',
        ];
    }
    
    /**
     * Gets the redondeo value based on porcredondeo
     *
     * @return float
     */
    public function getRedondeoValue(): float
    {
        switch ($this->porcredondeo) {
            case self::REDONDEO_DECIMO:
                return 0.1;
            case self::REDONDEO_CUARTO:
                return 0.25;
            case self::REDONDEO_MEDIO:
                return 0.5;
            case self::REDONDEO_ENTERO:
                return 1.0;
            default:
                return 0.0;
        }
    }
    
    /**
     * Gets redondeo options for dropdowns
     *
     * @return array
     */
    public static function getRedondeoOptions(): array
    {
        return [
            self::REDONDEO_NINGUNO => 'Sin redondeo',
            self::REDONDEO_DECIMO => 'A décimos (0.1)',
            self::REDONDEO_CUARTO => 'A cuartos (0.25)',
            self::REDONDEO_MEDIO => 'A medios (0.5)',
            self::REDONDEO_ENTERO => 'A enteros (1.0)',
        ];
    }
    
    /**
     * Gets IB mode options for dropdowns
     *
     * @return array
     */
    public static function getIbModeOptions(): array
    {
        return [
            self::IB_MODO_AUTOMATICO => 'Automático',
            self::IB_MODO_MANUAL => 'Manual',
        ];
    }
    
    /**
     * Gets caja verification options for dropdowns
     *
     * @return array
     */
    public static function getCajaVerifOptions(): array
    {
        return [
            self::CAJA_VERIF_NINGUNA => 'No verificar',
            self::CAJA_VERIF_ADVERTIR => 'Advertir',
            self::CAJA_VERIF_BLOQUEAR => 'Bloquear',
        ];
    }
    
    /**
     * Validates the configuration before saving
     *
     * @return bool
     */
    public function validateConfig(): bool
    {
        $errors = [];
        
        // Validate interest range
        if ($this->interes_min < self::MIN_INTERES || $this->interes_min > self::MAX_INTERES) {
            $errors[] = "Monto Mínimo fuera de Rango, debe estar dentro del rango (" . self::MIN_INTERES . "," . self::MAX_INTERES . ")";
        }
        
        // Validate account year range
        if ($this->ctacte_anio_desde < self::MIN_CTACTE_ANIO || $this->ctacte_anio_desde > self::MAX_CTACTE_ANIO) {
            $errors[] = "'Año desde' de cuenta corriente fuera de rango, debe estar dentro del rango (" . self::MIN_CTACTE_ANIO . "," . self::MAX_CTACTE_ANIO . ")";
        }
        
        // Validate commercial validation range
        if ($this->comer_hab_vence < self::MIN_COMER_HAB_VENCE || $this->comer_hab_vence > self::MAX_COMER_HAB_VENCE) {
            $errors[] = "'Cantidad de meses de duración de habilitación fuera de rango, debe estar dentro del rango (" . self::MIN_COMER_HAB_VENCE . "," . self::MAX_COMER_HAB_VENCE . ")";
        }
        
        // Validate paths exist and are writable
        $paths = ['path_recl', 'bol_path', 'agrete_path'];
        foreach ($paths as $pathAttr) {
            if (!empty($this->$pathAttr)) {
                $path = Yii::getAlias('@webroot') . '/' . ltrim($this->$pathAttr, './');
                if (!is_dir($path)) {
                    try {
                        if (!mkdir($path, 0755, true)) {
                            $errors[] = "No se pudo crear el directorio: {$this->$pathAttr}";
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Error al crear directorio {$this->$pathAttr}: " . $e->getMessage();
                    }
                } elseif (!is_writable($path)) {
                    $errors[] = "El directorio {$this->$pathAttr} no tiene permisos de escritura";
                }
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError('ctarecargo', $error);
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Validate configuration
        if (!$this->validateConfig()) {
            return false;
        }
        
        // Sanitize string fields
        $stringFields = [
            'texto_ucm', 'titulo_libredeuda', 'titulo2_libredeuda',
            'mensaje_libredeuda', 'path_recl', 'bol_path', 'bol_mail',
            'bol_mail_host', 'agrete_path'
        ];
        
        foreach ($stringFields as $field) {
            if (isset($this->$field)) {
                $this->$field = Html::encode($this->$field);
            }
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        
        // Log configuration changes
        if (!$insert && !empty($changedAttributes)) {
            $logData = [
                'user_id' => Yii::$app->user->id ?? 0,
                'changed_attributes' => array_keys($changedAttributes),
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => Yii::$app->request->getUserIP()
            ];
            
            Yii::info('Configuration updated: ' . json_encode($logData), 'config-change');
        }
        
        // Clear cache if needed
        if (Yii::$app->cache) {
            Yii::$app->cache->delete('system-config');
        }
    }
    
    /**
     * Gets the singleton instance of configuration
     *
     * @return Config|null
     */
    public static function getInstance(): ?Config
    {
        static $instance = null;
        
        if ($instance === null) {
            $instance = static::find()->one();
            
            // Create default config if none exists
            if ($instance === null) {
                $instance = new static();
                $instance->setDefaultValues();
                
                try {
                    if (!$instance->save()) {
                        Yii::error('Failed to create default configuration: ' . json_encode($instance->errors), __METHOD__);
                        return null;
                    }
                } catch (\Exception $e) {
                    Yii::error('Exception creating default configuration: ' . $e->getMessage(), __METHOD__);
                    return null;
                }
            }
        }
        
        return $instance;
    }
    
    /**
     * Legacy method for backward compatibility
     *
     * @return bool
     * @deprecated Use save() instead
     */
    public function grabar(): bool
    {
        try {
            return $this->save();
        } catch (\Exception $e) {
            Yii::error('Error saving configuration: ' . $e->getMessage(), __METHOD__);
            $this->addError('ctarecargo', 'Error al guardar la configuración: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Legacy method for backward compatibility
     *
     * @return string
     * @deprecated Use validateConfig() instead
     */
    public function validar(): string
    {
        if ($this->validateConfig()) {
            return '';
        }
        
        $errors = [];
        foreach ($this->errors as $attribute => $attributeErrors) {
            $errors = array_merge($errors, $attributeErrors);
        }
        
        return implode('. ', $errors);
    }
}
