<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\data\SqlDataProvider;
use yii\data\ActiveDataProvider;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\Html;

/**
 * This is the model class for table "sam.version".
 *
 * @property integer $sis_id
 * @property string $version
 * @property string $origen
 * @property string $novedades
 * @property string $fchmod
 * @property integer $usrmod
 */
class ControlVersion extends ActiveRecord
{
    // System ID constants
    const SISTEMA_SAM = 1;
    const SISTEMA_PORTAL = 2;
    const SISTEMA_MOBILE = 3;
    
    // Origin constants
    const ORIGEN_DESARROLLO = 'D';
    const ORIGEN_PRODUCCION = 'P';
    const ORIGEN_TESTING = 'T';
    
    // Version pattern
    const VERSION_PATTERN = '/^\d+\.\d+\.\d+$/';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'sam.version';
    }
    
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => false, // No creation timestamp
                'updatedAtAttribute' => 'fchmod',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Required fields
            [['sis_id', 'version', 'origen'], 'required'],
            
            // Integer validations
            [['sis_id', 'usrmod'], 'integer', 'min' => 1],
            
            // String validations
            ['version', 'string', 'max' => 20],
            ['version', 'match', 'pattern' => self::VERSION_PATTERN, 'message' => 'La versión debe tener el formato X.Y.Z'],
            ['origen', 'string', 'max' => 1],
            ['origen', 'in', 'range' => [self::ORIGEN_DESARROLLO, self::ORIGEN_PRODUCCION, self::ORIGEN_TESTING]],
            ['novedades', 'string'],
            
            // Unique validation
            [['sis_id', 'version'], 'unique', 'targetAttribute' => ['sis_id', 'version']],
            
            // Safe attributes
            [['fchmod'], 'safe'],
            
            // Trim strings
            [['version', 'origen', 'novedades'], 'trim'],
            
            // Custom validations
            ['version', 'validateVersionFormat'],
            ['novedades', 'validateNovedades'],
        ];
    }
    
    /**
     * Validates version format
     *
     * @param string $attribute
     * @param array $params
     */
    public function validateVersionFormat(string $attribute, array $params): void
    {
        if (!empty($this->$attribute)) {
            $version = $this->$attribute;
            
            // Check semantic versioning format
            if (!preg_match(self::VERSION_PATTERN, $version)) {
                $this->addError($attribute, 'La versión debe seguir el formato semántico (ej: 1.0.0)');
                return;
            }
            
            // Check if version is greater than existing versions
            if (!$this->isNewRecord) {
                return; // Skip validation for updates
            }
            
            $latestVersion = static::find()
                ->where(['sis_id' => $this->sis_id])
                ->orderBy(['version' => SORT_DESC])
                ->one();
                
            if ($latestVersion && version_compare($version, $latestVersion->version, '<=')) {
                $this->addError($attribute, 'La nueva versión debe ser mayor que la versión actual (' . $latestVersion->version . ')');
            }
        }
    }
    
    /**
     * Validates novedades content
     *
     * @param string $attribute
     * @param array $params
     */
    public function validateNovedades(string $attribute, array $params): void
    {
        if (!empty($this->$attribute)) {
            $content = $this->$attribute;
            
            // Check minimum length
            if (strlen(trim($content)) < 10) {
                $this->addError($attribute, 'Las novedades deben tener al menos 10 caracteres');
            }
            
            // Check for suspicious content
            $suspiciousPatterns = [
                '/<script[^>]*>.*?<\/script>/is',
                '/<iframe[^>]*>.*?<\/iframe>/is',
                '/javascript:/i',
                '/on\w+\s*=/i'
            ];
            
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->addError($attribute, 'Las novedades contienen contenido no permitido');
                    break;
                }
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'sis_id' => 'Sistema',
            'version' => 'Versión',
            'origen' => 'Origen',
            'novedades' => 'Novedades',
            'fchmod' => 'Fecha de Modificación',
            'usrmod' => 'Usuario que Modificó',
        ];
    }
    
    /**
     * Gets system options for dropdowns
     *
     * @return array
     */
    public static function getSistemaOptions(): array
    {
        return [
            self::SISTEMA_SAM => 'SAM - Sistema de Administración Municipal',
            self::SISTEMA_PORTAL => 'Portal Web',
            self::SISTEMA_MOBILE => 'Aplicación Móvil',
        ];
    }
    
    /**
     * Gets origin options for dropdowns
     *
     * @return array
     */
    public static function getOrigenOptions(): array
    {
        return [
            self::ORIGEN_DESARROLLO => 'Desarrollo',
            self::ORIGEN_PRODUCCION => 'Producción',
            self::ORIGEN_TESTING => 'Testing',
        ];
    }
    
    /**
     * Gets the system name
     *
     * @return string
     */
    public function getSistemaName(): string
    {
        $sistemas = self::getSistemaOptions();
        return $sistemas[$this->sis_id] ?? 'Sistema Desconocido';
    }
    
    /**
     * Gets the origin name
     *
     * @return string
     */
    public function getOrigenName(): string
    {
        $origenes = self::getOrigenOptions();
        return $origenes[$this->origen] ?? 'Origen Desconocido';
    }
    
    /**
     * Lists versions for a specific system
     *
     * @param int $sisId
     * @param int $pageSize
     * @return ActiveDataProvider
     */
    public function listarVersiones(int $sisId, int $pageSize = 18): ActiveDataProvider
    {
        $query = static::find()
            ->where(['sis_id' => $sisId])
            ->orderBy(['version' => SORT_DESC]);
            
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
    }
    
    /**
     * Gets versions with formatted date using SQL for backward compatibility
     *
     * @param int $sisId
     * @param int $pageSize
     * @return SqlDataProvider
     */
    public function listarVersionesConFecha(int $sisId, int $pageSize = 18): SqlDataProvider
    {
        $sql = "SELECT version, 
                       to_char(fchmod::date,'DD/MM/YYYY') as fecha, 
                       to_char(fchmod,'HH24:MI:SS') as hora,
                       origen,
                       CASE 
                           WHEN LENGTH(novedades) > 100 THEN SUBSTRING(novedades FROM 1 FOR 100) || '...'
                           ELSE novedades
                       END as novedades_resumen
                FROM sam.version 
                WHERE sis_id = :sisId 
                ORDER BY version DESC";
        
        $countSql = "SELECT COUNT(*) FROM sam.version WHERE sis_id = :sisId";
        $count = Yii::$app->db->createCommand($countSql, [':sisId' => $sisId])->queryScalar();
        
        return new SqlDataProvider([
            'sql' => $sql,
            'params' => [':sisId' => $sisId],
            'totalCount' => (int) $count,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
    }
    
    /**
     * Searches for version news/changelog
     *
     * @param int $sisId
     * @param string $version
     * @return string|null
     */
    public function buscarNovedad(int $sisId, string $version): ?string
    {
        try {
            $model = static::find()
                ->where(['sis_id' => $sisId, 'version' => $version])
                ->one();
                
            return $model ? $model->novedades : null;
        } catch (\Exception $e) {
            Yii::error('Error searching version news: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }
    
    /**
     * Gets the latest version for a system
     *
     * @param int $sisId
     * @return ControlVersion|null
     */
    public static function getLatestVersion(int $sisId): ?self
    {
        return static::find()
            ->where(['sis_id' => $sisId])
            ->orderBy(['version' => SORT_DESC])
            ->one();
    }
    
    /**
     * Gets all versions for a system
     *
     * @param int $sisId
     * @return array
     */
    public static function getVersionHistory(int $sisId): array
    {
        return static::find()
            ->where(['sis_id' => $sisId])
            ->orderBy(['version' => SORT_DESC])
            ->all();
    }
    
    /**
     * Checks if a version exists
     *
     * @param int $sisId
     * @param string $version
     * @return bool
     */
    public static function versionExists(int $sisId, string $version): bool
    {
        return static::find()
            ->where(['sis_id' => $sisId, 'version' => $version])
            ->exists();
    }
    
    /**
     * Creates a new version entry
     *
     * @param int $sisId
     * @param string $version
     * @param string $origen
     * @param string $novedades
     * @param int|null $usrmod
     * @return bool
     */
    public static function createVersion(int $sisId, string $version, string $origen, string $novedades, ?int $usrmod = null): bool
    {
        $model = new static();
        $model->sis_id = $sisId;
        $model->version = $version;
        $model->origen = $origen;
        $model->novedades = $novedades;
        $model->usrmod = $usrmod ?? Yii::$app->user->id ?? 0;
        
        return $model->save();
    }
    
    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Sanitize novedades content
        if (!empty($this->novedades)) {
            $this->novedades = Html::encode($this->novedades);
        }
        
        // Set user modification
        if (empty($this->usrmod)) {
            $this->usrmod = Yii::$app->user->id ?? 0;
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        
        // Log version creation/update
        $action = $insert ? 'created' : 'updated';
        $logData = [
            'action' => $action,
            'sis_id' => $this->sis_id,
            'version' => $this->version,
            'user_id' => $this->usrmod,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => Yii::$app->request->getUserIP()
        ];
        
        Yii::info('Version ' . $action . ': ' . json_encode($logData), 'version-control');
        
        // Clear cache if needed
        if (Yii::$app->cache) {
            Yii::$app->cache->delete('latest-version-' . $this->sis_id);
            Yii::$app->cache->delete('version-history-' . $this->sis_id);
        }
    }
    
    /**
     * Gets formatted modification date
     *
     * @return string
     */
    public function getFormattedDate(): string
    {
        if ($this->fchmod) {
            return Yii::$app->formatter->asDatetime($this->fchmod, 'dd/MM/yyyy HH:mm:ss');
        }
        
        return '';
    }
    
    /**
     * Gets short novedades for listing
     *
     * @param int $length
     * @return string
     */
    public function getShortNovedades(int $length = 100): string
    {
        if (empty($this->novedades)) {
            return '';
        }
        
        $decoded = Html::decode($this->novedades);
        
        if (strlen($decoded) <= $length) {
            return $decoded;
        }
        
        return substr($decoded, 0, $length) . '...';
    }
}
