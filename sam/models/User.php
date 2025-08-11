<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use yii\db\Expression;
use yii\helpers\Security;

/**
 * This is the model class for table "sam.sis_usuario".
 *
 * @property integer $usr_id
 * @property string $nombre
 * @property string $clave
 * @property string $apenom
 * @property string $domi
 * @property integer $tdoc
 * @property string $ndoc
 * @property integer $oficina
 * @property string $cargo
 * @property integer $legajo
 * @property integer $matricula
 * @property integer $grupo
 * @property string $est
 * @property string $tel
 * @property string $mail
 * @property integer $distrib
 * @property integer $inspec_inm
 * @property integer $inspec_comer
 * @property integer $inspec_op
 * @property integer $inspec_juz
 * @property integer $inspec_recl
 * @property integer $abogado
 * @property integer $cajero
 * @property string $fchalta
 * @property string $fchbaja
 * @property string $fchmod
 * @property integer $usrmod
 * @property string $auth_key
 * @property string $password_reset_token
 * @property integer $created_at
 * @property integer $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    // Status constants
    const STATUS_ACTIVE = 'A';
    const STATUS_INACTIVE = 'I';
    const STATUS_DELETED = 'B';
    
    // Group constants
    const GRUPO_ADMIN = 1;
    const GRUPO_USUARIO = 2;
    const GRUPO_INSPECTOR = 3;
    const GRUPO_CAJERO = 4;
    
    // Role constants
    const ROLE_DISTRIBUIDOR = 1;
    const ROLE_CENSISTA = 1;
    const ROLE_INSPECTOR_COMERCIO = 1;
    const ROLE_INSPECTOR_OBRAS = 1;
    const ROLE_INSPECTOR_JUZGADO = 1;
    const ROLE_INSPECTOR_RECLAMOS = 1;
    const ROLE_ABOGADO = 1;
    const ROLE_CAJERO = 1;
    
    // Password reset token expire time (24 hours)
    const PASSWORD_RESET_TOKEN_EXPIRE = 86400;
    
    public $apenom;
    public static $procesos = [];
    public static $acciones = [];
    
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'sam.sis_usuario';
    }
    
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
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
            // Status rules
            ['est', 'default', 'value' => self::STATUS_ACTIVE],
            ['est', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            
            // Required fields
            [['nombre', 'clave', 'apenom', 'est'], 'required'],
            [['fchalta', 'fchmod'], 'required'],
            
            // Integer fields
            [[
                'tdoc', 'oficina', 'legajo', 'matricula', 'grupo', 'distrib',
                'inspec_inm', 'inspec_comer', 'inspec_op', 'inspec_juz',
                'inspec_recl', 'abogado', 'cajero', 'usrmod'
            ], 'integer'],
            
            // String length validations
            ['nombre', 'string', 'max' => 10],
            ['clave', 'string', 'max' => 255], // Increased for hashed passwords
            [['apenom', 'domi'], 'string', 'max' => 40],
            ['cargo', 'string', 'max' => 30],
            ['est', 'string', 'max' => 1],
            ['tel', 'string', 'max' => 20],
            ['mail', 'email', 'max' => 50],
            ['ndoc', 'string', 'max' => 20],
            
            // Auth key and password reset token
            ['auth_key', 'string', 'max' => 32],
            ['password_reset_token', 'string', 'max' => 255],
            ['password_reset_token', 'unique'],
            
            // Safe attributes
            [['fchbaja', 'created_at', 'updated_at'], 'safe'],
            
            // Boolean fields (0 or 1)
            [[
                'distrib', 'inspec_inm', 'inspec_comer', 'inspec_op',
                'inspec_juz', 'inspec_recl', 'abogado', 'cajero'
            ], 'boolean'],
            
            // Group validation
            ['grupo', 'in', 'range' => [self::GRUPO_ADMIN, self::GRUPO_USUARIO, self::GRUPO_INSPECTOR, self::GRUPO_CAJERO]],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'usr_id' => 'Código',
            'nombre' => 'Nombre de Usuario',
            'clave' => 'Contraseña',
            'apenom' => 'Apellido y Nombre',
            'domi' => 'Domicilio',
            'tdoc' => 'Tipo de Documento',
            'ndoc' => 'Número de Documento',
            'oficina' => 'Oficina',
            'cargo' => 'Cargo',
            'legajo' => 'Legajo',
            'matricula' => 'Matrícula',
            'grupo' => 'Grupo',
            'est' => 'Estado',
            'tel' => 'Teléfono',
            'mail' => 'Correo Electrónico',
            'distrib' => 'Es Distribuidor',
            'inspec_inm' => 'Es Censista',
            'inspec_comer' => 'Es Inspector de Comercio',
            'inspec_op' => 'Es Inspector de Obras Particulares',
            'inspec_juz' => 'Es Inspector de Juzgado de Faltas',
            'inspec_recl' => 'Es Inspector de Reclamos',
            'abogado' => 'Es Abogado',
            'cajero' => 'Es Cajero',
            'fchalta' => 'Fecha de Alta',
            'fchbaja' => 'Fecha de Baja',
            'fchmod' => 'Fecha de Modificación',
            'usrmod' => 'Usuario que Modificó',
            'auth_key' => 'Clave de Autenticación',
            'password_reset_token' => 'Token de Recuperación',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne([
            'usr_id' => $id,
            'est' => self::STATUS_ACTIVE
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }
    
    /**
     * Finds user by username
     *
     * @param string $nombre
     * @return static|null
     */
    public static function findByUsername(string $nombre): ?self
    {
        return static::findOne([
            'nombre' => $nombre,
            'est' => self::STATUS_ACTIVE
        ]);
    }
    
    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken(string $token): ?self
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        
        return static::findOne([
            'password_reset_token' => $token,
            'est' => self::STATUS_ACTIVE,
        ]);
    }
    
    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid(string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        
        $expire = Yii::$app->params['user.passwordResetTokenExpire'] ?? self::PASSWORD_RESET_TOKEN_EXPIRE;
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        
        return $timestamp + $expire >= time();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }
    
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword(string $password): bool
    {
        // Check if password is already hashed with password_hash()
        if (password_verify($password, $this->clave)) {
            return true;
        }
        
        // Fallback to MD5 for legacy passwords
        return $this->clave === md5($password);
    }
    
    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->clave = password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Legacy method for MD5 password hashing (deprecated)
     *
     * @param string $password
     * @return string
     * @deprecated Use setPassword() instead
     */
    public function hashPassword(string $password): string
    {
        return md5($password);
    }
    
    /**
     * Changes user password (legacy method)
     *
     * @param string $username
     * @param string $newPassword
     * @return bool
     */
    public function setNuevaPassword(string $username, string $newPassword): bool
    {
        $user = static::findByUsername($username);
        if ($user) {
            $user->setPassword($newPassword);
            return $user->save(false);
        }
        
        return false;
    }
    
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    
    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }
    
    /**
     * Removes password reset token
     */
    public function removePasswordResetToken(): void
    {
        $this->password_reset_token = null;
    }
    
    /**
     * Check if user belongs to specific groups
     *
     * @param array $groups
     * @return bool
     */
    public static function grupoInArray(array $groups): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        
        return in_array(Yii::$app->user->identity->grupo, $groups, true);
    }
    
    /**
     * Check if user is active
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        
        return Yii::$app->user->identity->est === self::STATUS_ACTIVE;
    }
    
    /**
     * Check if user is admin
     *
     * @return bool
     */
    public function esAdmin(): bool
    {
        return $this->grupo === self::GRUPO_ADMIN;
    }
    
    /**
     * Load user processes
     *
     * @param int $userId
     * @return array
     */
    public function loadProcesos(int $userId): array
    {
        try {
            $sql = 'SELECT pro_id FROM sam.sis_usuario_proceso WHERE usr_id = :userId ORDER BY pro_id';
            $procesos = Yii::$app->db->createCommand($sql, [':userId' => $userId])->queryColumn();
            
            Yii::$app->session->set('procesos', $procesos);
            self::$procesos = $procesos;
            
            return $procesos;
        } catch (\Exception $e) {
            Yii::error('Error loading user processes: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
    
    /**
     * Load user actions
     *
     * @param int $userId
     * @return array
     */
    public function loadAcciones(int $userId): array
    {
        try {
            $sql = 'SELECT DISTINCT p.accion FROM sam.sis_usuario_proceso up '
                 . 'INNER JOIN sam.sis_proceso_accion p ON up.pro_id = p.pro_id '
                 . 'WHERE up.usr_id = :userId ORDER BY p.accion';
            
            $acciones = Yii::$app->db->createCommand($sql, [':userId' => $userId])->queryColumn();
            
            Yii::$app->session->set('acciones', $acciones);
            self::$acciones = $acciones;
            
            return $acciones;
        } catch (\Exception $e) {
            Yii::error('Error loading user actions: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
    
    /**
     * Check if user has specific permission
     *
     * @param int $proceso
     * @return bool
     */
    public function existePermiso(int $proceso): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        
        $permisos = Yii::$app->session->get('permisos', []);
        return in_array($proceso, $permisos, true);
    }
    
    /**
     * Get user permissions
     *
     * @return array
     */
    public function getPermisos(): array
    {
        return Yii::$app->session->get('permisos', []);
    }
    
    /**
     * Check if user has specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        switch ($role) {
            case 'distribuidor':
                return (bool) $this->distrib;
            case 'censista':
                return (bool) $this->inspec_inm;
            case 'inspector_comercio':
                return (bool) $this->inspec_comer;
            case 'inspector_obras':
                return (bool) $this->inspec_op;
            case 'inspector_juzgado':
                return (bool) $this->inspec_juz;
            case 'inspector_reclamos':
                return (bool) $this->inspec_recl;
            case 'abogado':
                return (bool) $this->abogado;
            case 'cajero':
                return (bool) $this->cajero;
            default:
                return false;
        }
    }
    
    /**
     * Get user roles
     *
     * @return array
     */
    public function getRoles(): array
    {
        $roles = [];
        
        if ($this->distrib) $roles[] = 'distribuidor';
        if ($this->inspec_inm) $roles[] = 'censista';
        if ($this->inspec_comer) $roles[] = 'inspector_comercio';
        if ($this->inspec_op) $roles[] = 'inspector_obras';
        if ($this->inspec_juz) $roles[] = 'inspector_juzgado';
        if ($this->inspec_recl) $roles[] = 'inspector_reclamos';
        if ($this->abogado) $roles[] = 'abogado';
        if ($this->cajero) $roles[] = 'cajero';
        
        return $roles;
    }
    
    /**
     * Get full name
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->apenom ?: $this->nombre;
    }
    
    /**
     * Before save event
     *
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->generateAuthKey();
                $this->fchalta = date('Y-m-d H:i:s');
            }
            $this->fchmod = date('Y-m-d H:i:s');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        switch ($this->est) {
            case self::STATUS_ACTIVE:
                return 'Activo';
            case self::STATUS_INACTIVE:
                return 'Inactivo';
            case self::STATUS_DELETED:
                return 'Eliminado';
            default:
                return 'Desconocido';
        }
    }
    
    /**
     * Get group label
     *
     * @return string
     */
    public function getGroupLabel(): string
    {
        switch ($this->grupo) {
            case self::GRUPO_ADMIN:
                return 'Administrador';
            case self::GRUPO_USUARIO:
                return 'Usuario';
            case self::GRUPO_INSPECTOR:
                return 'Inspector';
            case self::GRUPO_CAJERO:
                return 'Cajero';
            default:
                return 'Sin Grupo';
        }
    }
}