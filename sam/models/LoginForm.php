<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\utils\db\utb;
use yii\helpers\Html;
use yii\web\ServerErrorHttpException;
use yii\db\Exception as DbException;
use yii\validators\RequiredValidator;
use yii\validators\StringValidator;
use yii\validators\BooleanValidator;

/**
 * LoginForm is the model behind the login form.
 *
 * @property string $nombre
 * @property string $clave
 * @property bool $rememberMe
 * @property User|null $user
 */
class LoginForm extends Model
{
    public $nombre;
    public $clave;
    public $rememberMe = false;
    
    private $_user = false;
    
    // Constants for session validation
    const SESSION_MODE_NORMAL = 'N';
    const SESSION_MODE_AUTO_CLOSE = 'A';
    const SESSION_MODE_EXPIRED = 'V';
    const SESSION_MODE_FORCED = 'F';
    
    // Constants for password validation
    const EMPTY_PASSWORD_HASH = 'd41d8cd98f00b204e9800998ecf8427e'; // MD5 of empty string
    
    // Remember me duration (30 days)
    const REMEMBER_ME_DURATION = 3600 * 24 * 30;
    
    // Login attempt limits
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900; // 15 minutes
    
    // Username constraints
    const MIN_USERNAME_LENGTH = 3;
    const MAX_USERNAME_LENGTH = 20;
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Username validation
            [['nombre'], 'required', 'message' => 'El nombre de usuario es requerido.'],
            [['nombre'], 'string', 'min' => self::MIN_USERNAME_LENGTH, 'max' => self::MAX_USERNAME_LENGTH],
            [['nombre'], 'trim'],
            [['nombre'], 'match', 'pattern' => '/^[a-zA-Z0-9_.-]+$/', 'message' => 'El usuario solo puede contener letras, números, guiones y puntos.'],
            
            // Password validation
            [['clave'], 'string', 'max' => 255],
            [['clave'], 'required', 'when' => function($model) {
                return !$this->isEmptyPasswordAllowed();
            }, 'message' => 'La contraseña es requerida.'],
            
            // Remember me validation
            ['rememberMe', 'boolean'],
            
            // Custom validations
            ['nombre', 'validateUserExists'],
            ['nombre', 'validateUserStatus'],
            ['nombre', 'validateLoginAttempts'],
            ['clave', 'validatePassword'],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'nombre' => 'Usuario',
            'clave' => 'Contraseña',
            'rememberMe' => 'Recordar mis datos',
        ];
    }
    
    /**
     * Validates that the user exists in the database
     *
     * @param string $attribute
     * @param array $params
     */
    public function validateUserExists(string $attribute, array $params): void
    {
        if (!$this->hasErrors($attribute)) {
            try {
                $user = $this->getUser();
                
                if (!$user) {
                    $this->addError($attribute, 'Usuario inexistente.');
                }
            } catch (\Exception $e) {
                Yii::error('Error validating user existence: ' . $e->getMessage(), __METHOD__);
                $this->addError($attribute, 'Error al validar el usuario.');
            }
        }
    }
    
    /**
     * Validates user status (active/inactive)
     *
     * @param string $attribute
     * @param array $params
     */
    public function validateUserStatus(string $attribute, array $params): void
    {
        if (!$this->hasErrors($attribute)) {
            $user = $this->getUser();
            
            if ($user && $user->status !== User::STATUS_ACTIVE) {
                $this->addError($attribute, 'Usuario inactivo. Contacte al administrador.');
            }
        }
    }
    
    /**
     * Validates login attempts to prevent brute force attacks
     *
     * @param string $attribute
     * @param array $params
     */
    public function validateLoginAttempts(string $attribute, array $params): void
    {
        if (!$this->hasErrors($attribute)) {
            $user = $this->getUser();
            
            if ($user && $this->isUserLocked($user->usr_id)) {
                $this->addError($attribute, 'Usuario bloqueado temporalmente por múltiples intentos fallidos. Intente en ' . ceil(self::LOCKOUT_DURATION / 60) . ' minutos.');
            }
        }
    }
    
    /**
     * Validates the password
     *
     * @param string $attribute
     * @param array $params
     */
    public function validatePassword(string $attribute, array $params): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            
            if (!$user) {
                return; // User validation will handle this
            }
            
            // Allow empty password only if user has empty password hash
            if ($this->isEmptyPassword($user->clave)) {
                return; // Empty password is allowed for this user
            }
            
            // Validate password
            if (empty($this->clave) || !$user->validatePassword($this->clave)) {
                $this->addError($attribute, 'Contraseña incorrecta.');
            }
        }
    }
    
    /**
     * Logs in a user using the provided username and password
     *
     * @return bool whether the user is logged in successfully
     */
    public function login(): bool
    {
        try {
            if (!$this->validate()) {
                $this->handleFailedLogin();
                return false;
            }
            
            // Validate session before login
            if (!$this->validateSession()) {
                $this->addError('nombre', 'No se permite habilitar múltiples sesiones. Consulte al Administrador del Sistema.');
                return false;
            }
            
            $user = $this->getUser();
            
            if (!$user) {
                return false;
            }
            
            // Attempt login
            $duration = $this->rememberMe ? self::REMEMBER_ME_DURATION : 0;
            $loginSuccess = Yii::$app->user->login($user, $duration);
            
            if (!$loginSuccess) {
                $this->recordFailedLogin($user->usr_id);
                $this->addError('nombre', 'Error al iniciar sesión.');
                return false;
            }
            
            // Handle empty password scenario
            $hasEmptyPassword = $this->isEmptyPassword($user->clave);
            Yii::$app->session->set('user_sinclave', $hasEmptyPassword ? 1 : 0);
            
            // Record successful login
            $this->recordSuccessfulLogin($user->usr_id);
            
            // Load user permissions if password is set
            if (!$hasEmptyPassword) {
                $this->loadUserPermissions($user);
            }
            
            // Clear failed login attempts
            $this->clearFailedLoginAttempts($user->usr_id);
            
            return true;
            
        } catch (\Exception $e) {
            Yii::error('Login error: ' . $e->getMessage(), __METHOD__);
            $this->addError('nombre', 'Error interno del sistema. Intente nuevamente.');
            return false;
        }
    }
    
    /**
     * Validates that the user doesn't have conflicting open sessions
     *
     * @return bool
     */
    public function validateSession(): bool
    {
        try {
            $user = $this->getUser();
            
            if (!$user) {
                return true;
            }
            
            // Check for open sessions
            $openSession = $this->getOpenSession($user->usr_id);
            
            if (!$openSession) {
                return true; // No open sessions
            }
            
            $currentIp = Yii::$app->request->getUserIP();
            $sessionIp = $openSession['ip'];
            $sessionDate = date('Y-m-d', strtotime($openSession['fchingreso']));
            $currentDate = date('Y-m-d');
            
            // Same IP - close previous session automatically
            if ($sessionIp === $currentIp) {
                $this->closeUserSession($user->usr_id, self::SESSION_MODE_AUTO_CLOSE);
                return true;
            }
            
            // Session from previous day - close it
            if ($sessionDate < $currentDate) {
                $this->closeUserSession($user->usr_id, self::SESSION_MODE_EXPIRED);
                return true;
            }
            
            // Active session from different IP on same day - deny login
            return false;
            
        } catch (\Exception $e) {
            Yii::error('Session validation error: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
    
    /**
     * Gets open session information for a user
     *
     * @param int $userId
     * @return array|null
     */
    private function getOpenSession(int $userId): ?array
    {
        try {
            $sql = 'SELECT ip, fchingreso FROM sam.sis_usuario_acc '
                 . 'WHERE fchsalida IS NULL AND usr_id = :userId '
                 . 'ORDER BY fchingreso DESC LIMIT 1';
            
            $result = Yii::$app->db->createCommand($sql, [':userId' => $userId])->queryOne();
            
            return $result ?: null;
        } catch (\Exception $e) {
            Yii::error('Error getting open session: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }
    
    /**
     * Checks if user is locked due to failed login attempts
     *
     * @param int $userId
     * @return bool
     */
    private function isUserLocked(int $userId): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM sam.sis_usuario_acc_err '
                 . 'WHERE usr_id = :userId '
                 . 'AND fchintento > (CURRENT_TIMESTAMP - INTERVAL \'' . self::LOCKOUT_DURATION . ' seconds\')';
            
            $attempts = Yii::$app->db->createCommand($sql, [':userId' => $userId])->queryScalar();
            
            return (int) $attempts >= self::MAX_LOGIN_ATTEMPTS;
        } catch (\Exception $e) {
            Yii::error('Error checking user lock status: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
    
    /**
     * Handles failed login attempt
     */
    private function handleFailedLogin(): void
    {
        $user = $this->getUser();
        
        if ($user) {
            $this->recordFailedLogin($user->usr_id);
        }
    }
    
    /**
     * Records successful login
     *
     * @param int $userId
     */
    public function recordSuccessfulLogin(int $userId): void
    {
        try {
            $sql = 'INSERT INTO sam.sis_usuario_acc (usr_id, fchingreso, ip, user_agent) '
                 . 'VALUES (:userId, CURRENT_TIMESTAMP, :ip, :userAgent)';
            
            Yii::$app->db->createCommand($sql, [
                ':userId' => $userId,
                ':ip' => Yii::$app->request->getUserIP(),
                ':userAgent' => substr(Yii::$app->request->getUserAgent() ?? '', 0, 255)
            ])->execute();
            
        } catch (\Exception $e) {
            Yii::error('Error recording successful login: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Records failed login attempt
     *
     * @param int $userId
     */
    public function recordFailedLogin(int $userId): void
    {
        try {
            if ($userId > 0) {
                $sql = 'INSERT INTO sam.sis_usuario_acc_err (usr_id, fchintento, ip, user_agent) '
                     . 'VALUES (:userId, CURRENT_TIMESTAMP, :ip, :userAgent)';
                
                Yii::$app->db->createCommand($sql, [
                    ':userId' => $userId,
                    ':ip' => Yii::$app->request->getUserIP(),
                    ':userAgent' => substr(Yii::$app->request->getUserAgent() ?? '', 0, 255)
                ])->execute();
            }
        } catch (\Exception $e) {
            Yii::error('Error recording failed login: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Clears failed login attempts for a user
     *
     * @param int $userId
     */
    private function clearFailedLoginAttempts(int $userId): void
    {
        try {
            $sql = 'DELETE FROM sam.sis_usuario_acc_err WHERE usr_id = :userId';
            Yii::$app->db->createCommand($sql, [':userId' => $userId])->execute();
        } catch (\Exception $e) {
            Yii::error('Error clearing failed login attempts: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Records user logout
     */
    public function recordLogout(): void
    {
        try {
            if (!Yii::$app->user->isGuest && Yii::$app->user->id) {
                $sql = 'UPDATE sam.sis_usuario_acc SET fchsalida = CURRENT_TIMESTAMP, modo = :modo '
                     . 'WHERE usr_id = :userId AND fchsalida IS NULL AND ip = :ip';
                
                Yii::$app->db->createCommand($sql, [
                    ':modo' => self::SESSION_MODE_NORMAL,
                    ':userId' => Yii::$app->user->id,
                    ':ip' => Yii::$app->request->getUserIP()
                ])->execute();
            }
        } catch (\Exception $e) {
            Yii::error('Error recording logout: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Closes user session
     *
     * @param int $userId
     * @param string $mode
     */
    private function closeUserSession(int $userId, string $mode): void
    {
        try {
            $sql = 'UPDATE sam.sis_usuario_acc SET fchsalida = CURRENT_TIMESTAMP, modo = :modo '
                 . 'WHERE usr_id = :userId AND fchsalida IS NULL';
            
            Yii::$app->db->createCommand($sql, [
                ':modo' => $mode,
                ':userId' => $userId
            ])->execute();
            
        } catch (\Exception $e) {
            Yii::error('Error closing user session: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Loads user permissions into session
     *
     * @param User $user
     */
    private function loadUserPermissions(User $user): void
    {
        try {
            if ($user->usr_id > 0) {
                $user->loadProcesos($user->usr_id);
                $user->loadAcciones($user->usr_id);
            }
        } catch (\Exception $e) {
            Yii::error('Error loading user permissions: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Checks if password is empty (MD5 hash of empty string)
     *
     * @param string $passwordHash
     * @return bool
     */
    private function isEmptyPassword(string $passwordHash): bool
    {
        return $passwordHash === self::EMPTY_PASSWORD_HASH;
    }
    
    /**
     * Checks if empty password is allowed for current user
     *
     * @return bool
     */
    private function isEmptyPasswordAllowed(): bool
    {
        $user = $this->getUser();
        return $user && $this->isEmptyPassword($user->clave);
    }
    
    /**
     * Finds user by username
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->nombre);
        }
        
        return $this->_user;
    }
    
    /**
     * Gets login statistics for a user
     *
     * @param int $userId
     * @return array
     */
    public function getLoginStats(int $userId): array
    {
        try {
            $sql = 'SELECT '
                 . 'COUNT(*) as total_logins, '
                 . 'MAX(fchingreso) as last_login, '
                 . 'COUNT(CASE WHEN fchingreso::date = CURRENT_DATE THEN 1 END) as today_logins '
                 . 'FROM sam.sis_usuario_acc WHERE usr_id = :userId';
            
            $stats = Yii::$app->db->createCommand($sql, [':userId' => $userId])->queryOne();
            
            $failedSql = 'SELECT COUNT(*) as failed_attempts '
                       . 'FROM sam.sis_usuario_acc_err '
                       . 'WHERE usr_id = :userId AND fchintento > (CURRENT_TIMESTAMP - INTERVAL \'24 hours\')';
            
            $failedStats = Yii::$app->db->createCommand($failedSql, [':userId' => $userId])->queryOne();
            
            return array_merge($stats ?: [], $failedStats ?: []);
            
        } catch (\Exception $e) {
            Yii::error('Error getting login stats: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
    
    /**
     * Loads municipalities from XML configuration
     *
     * @return array
     */
    public function loadMunicipalities(): array
    {
        try {
            $xmlPath = Yii::getAlias('@app/config/municipios.xml');
            
            if (!file_exists($xmlPath)) {
                Yii::warning('Municipalities XML file not found: ' . $xmlPath, __METHOD__);
                return [];
            }
            
            // Validate XML file size
            if (filesize($xmlPath) > 1024 * 1024) { // 1MB limit
                Yii::error('Municipalities XML file too large', __METHOD__);
                return [];
            }
            
            $municipiosXml = simplexml_load_file($xmlPath, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
            
            if ($municipiosXml === false) {
                Yii::error('Failed to load municipalities XML file', __METHOD__);
                return [];
            }
            
            $municipios = [];
            
            foreach ($municipiosXml as $datos) {
                $cod = Html::encode((string) $datos->cod);
                $nombre = Html::encode((string) $datos->nombre);
                
                if (!empty($cod) && !empty($nombre)) {
                    $municipios[$cod] = $nombre;
                }
            }
            
            return $municipios;
            
        } catch (\Exception $e) {
            Yii::error('Error loading municipalities: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }
    
    /**
     * Forces logout of all user sessions
     *
     * @param int $userId
     * @return bool
     */
    public function forceLogoutUser(int $userId): bool
    {
        try {
            $this->closeUserSession($userId, self::SESSION_MODE_FORCED);
            return true;
        } catch (\Exception $e) {
            Yii::error('Error forcing user logout: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
    
    // Legacy methods for backward compatibility
    
    /**
     * @deprecated Use loadMunicipalities() instead
     */
    public function CargarMunicipios(): array
    {
        return $this->loadMunicipalities();
    }
    
    /**
     * @deprecated Use recordSuccessfulLogin() instead
     */
    public function getGrabarAcce(int $id): void
    {
        $this->recordSuccessfulLogin($id);
    }
    
    /**
     * @deprecated Use recordFailedLogin() instead
     */
    public function getGrabarAcceError(int $id): void
    {
        $this->recordFailedLogin($id);
    }
    
    /**
     * @deprecated Use recordLogout() instead
     */
    public function getGrabarSalida(): void
    {
        $this->recordLogout();
    }
    
    /**
     * @deprecated Use validateSession() instead
     */
    public function ValidaSesion(): bool
    {
        return $this->validateSession();
    }
}
