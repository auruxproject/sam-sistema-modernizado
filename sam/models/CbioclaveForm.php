<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Html;

/**
 * CbioclaveForm is the model behind the password change form.
 *
 * @property string $nombre
 * @property string $clave_old
 * @property string $clave_new
 * @property string $clave_newr
 * @property User|null $user
 */
class CbioclaveForm extends Model
{
    public $nombre;
    public $clave_old;
    public $clave_new;
    public $clave_newr;
    
    private $_user = false;
    
    // Password validation constants
    const MIN_PASSWORD_LENGTH = 8;
    const MAX_PASSWORD_LENGTH = 128;
    const PASSWORD_HISTORY_LIMIT = 5; // Number of previous passwords to check
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Required fields
            [['clave_new', 'clave_newr'], 'required'],
            
            // Old password validation (only if user has password set)
            ['clave_old', 'validateOldPassword'],
            
            // New password validation
            ['clave_new', 'string', 'min' => self::MIN_PASSWORD_LENGTH, 'max' => self::MAX_PASSWORD_LENGTH],
            ['clave_new', 'validateNewPassword'],
            
            // Password confirmation validation
            ['clave_newr', 'string', 'min' => self::MIN_PASSWORD_LENGTH, 'max' => self::MAX_PASSWORD_LENGTH],
            ['clave_newr', 'validatePasswordConfirmation'],
            
            // Trim whitespace
            [['clave_old', 'clave_new', 'clave_newr'], 'trim'],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'nombre' => 'Usuario',
            'clave_old' => 'Contraseña Actual',
            'clave_new' => 'Nueva Contraseña',
            'clave_newr' => 'Confirmar Nueva Contraseña',
        ];
    }
    
    /**
     * Validates the current password.
     * Only validates if user has a password set (not empty password hash).
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateOldPassword(string $attribute, array $params): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            
            if (!$user) {
                $this->addError($attribute, 'Usuario no encontrado.');
                return;
            }
            
            // Check if user has empty password (first time setting password)
            if ($this->isEmptyPassword($user->clave)) {
                // Skip old password validation for users without password
                return;
            }
            
            // Validate old password
            if (empty($this->clave_old)) {
                $this->addError($attribute, 'La contraseña actual es requerida.');
                return;
            }
            
            if (!$user->validatePassword($this->clave_old)) {
                $this->addError($attribute, 'La contraseña actual es incorrecta.');
            }
        }
    }
    
    /**
     * Validates the new password strength and requirements.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateNewPassword(string $attribute, array $params): void
    {
        if (!$this->hasErrors() && !empty($this->clave_new)) {
            $password = $this->clave_new;
            $errors = [];
            
            // Check minimum length
            if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
                $errors[] = 'Debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres';
            }
            
            // Check maximum length
            if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
                $errors[] = 'No debe exceder ' . self::MAX_PASSWORD_LENGTH . ' caracteres';
            }
            
            // Check for at least one lowercase letter
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Debe contener al menos una letra minúscula';
            }
            
            // Check for at least one uppercase letter
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Debe contener al menos una letra mayúscula';
            }
            
            // Check for at least one digit
            if (!preg_match('/\d/', $password)) {
                $errors[] = 'Debe contener al menos un número';
            }
            
            // Check for at least one special character
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
                $errors[] = 'Debe contener al menos un carácter especial (!@#$%^&*()_+-=[]{};\':"|,.<>/?)';
            }
            
            // Check for common weak passwords
            if ($this->isCommonPassword($password)) {
                $errors[] = 'La contraseña es demasiado común. Elija una más segura';
            }
            
            // Check if password is same as username
            if (!empty($this->nombre) && strtolower($password) === strtolower($this->nombre)) {
                $errors[] = 'La contraseña no puede ser igual al nombre de usuario';
            }
            
            // Check against password history
            if ($this->isPasswordInHistory($password)) {
                $errors[] = 'No puede reutilizar una de sus últimas ' . self::PASSWORD_HISTORY_LIMIT . ' contraseñas';
            }
            
            // Check for sequential characters
            if ($this->hasSequentialCharacters($password)) {
                $errors[] = 'No debe contener secuencias de caracteres (ej: 123, abc, qwerty)';
            }
            
            // Check for repeated characters
            if ($this->hasRepeatedCharacters($password)) {
                $errors[] = 'No debe contener más de 2 caracteres consecutivos iguales';
            }
            
            if (!empty($errors)) {
                $this->addError($attribute, 'La nueva contraseña no cumple los requisitos:\n• ' . implode('\n• ', $errors));
            }
        }
    }
    
    /**
     * Validates that the password confirmation matches the new password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePasswordConfirmation(string $attribute, array $params): void
    {
        if (!$this->hasErrors()) {
            if ($this->clave_new !== $this->clave_newr) {
                $this->addError($attribute, 'La confirmación de contraseña no coincide.');
            }
        }
    }
    
    /**
     * Changes the user's password.
     *
     * @return bool whether the password was changed successfully
     */
    public function changePassword(): bool
    {
        try {
            // Set username from current user
            $this->nombre = Yii::$app->user->identity->nombre;
            
            if (!$this->validate()) {
                return false;
            }
            
            $user = $this->getUser();
            
            if (!$user) {
                $this->addError('nombre', 'Usuario no encontrado.');
                return false;
            }
            
            // Store old password in history before changing
            $this->storePasswordInHistory($user);
            
            // Set new password
            $user->setPassword($this->clave_new);
            $user->generateAuthKey();
            $user->removePasswordResetToken();
            
            if (!$user->save(false)) {
                $this->addError('clave_new', 'Error al guardar la nueva contraseña.');
                return false;
            }
            
            // Update session to indicate user has password
            Yii::$app->session->set('user_sinclave', 0);
            
            // Reload user permissions
            if ($user->usr_id > 0) {
                $user->loadProcesos($user->usr_id);
                $user->loadAcciones($user->usr_id);
            }
            
            // Log password change
            $this->logPasswordChange($user->usr_id);
            
            return true;
            
        } catch (\Exception $e) {
            Yii::error('Password change error: ' . $e->getMessage(), __METHOD__);
            $this->addError('clave_new', 'Error interno del sistema. Intente nuevamente.');
            return false;
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
        return $passwordHash === 'd41d8cd98f00b204e9800998ecf8427e';
    }
    
    /**
     * Checks if password is in the list of common weak passwords
     *
     * @param string $password
     * @return bool
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password123', '123456', '123456789', 'qwerty',
            'abc123', 'password1', 'admin', 'letmein', 'welcome',
            '12345678', 'iloveyou', 'princess', 'monkey', 'shadow',
            'master', 'jennifer', '111111', '000000', 'superman'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * Checks if password contains sequential characters
     *
     * @param string $password
     * @return bool
     */
    private function hasSequentialCharacters(string $password): bool
    {
        $sequences = [
            '123456789', 'abcdefghijklmnopqrstuvwxyz', 'qwertyuiop',
            'asdfghjkl', 'zxcvbnm', '987654321', 'zyxwvutsrqponmlkjihgfedcba'
        ];
        
        $password = strtolower($password);
        
        foreach ($sequences as $sequence) {
            for ($i = 0; $i <= strlen($sequence) - 3; $i++) {
                $subseq = substr($sequence, $i, 3);
                if (strpos($password, $subseq) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Checks if password has more than 2 consecutive repeated characters
     *
     * @param string $password
     * @return bool
     */
    private function hasRepeatedCharacters(string $password): bool
    {
        return preg_match('/(.)\1{2,}/', $password) === 1;
    }
    
    /**
     * Checks if password is in user's password history
     *
     * @param string $password
     * @return bool
     */
    private function isPasswordInHistory(string $password): bool
    {
        // This would require a password history table in the database
        // For now, we'll just check against current password
        $user = $this->getUser();
        
        if (!$user || $this->isEmptyPassword($user->clave)) {
            return false;
        }
        
        return $user->validatePassword($password);
    }
    
    /**
     * Stores current password in history before changing
     *
     * @param User $user
     */
    private function storePasswordInHistory(User $user): void
    {
        try {
            // This would store in a password history table
            // For now, we'll just log it
            if (!$this->isEmptyPassword($user->clave)) {
                Yii::info('Password changed for user: ' . $user->usr_id, 'password-change');
            }
        } catch (\Exception $e) {
            Yii::error('Error storing password history: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Logs password change event
     *
     * @param int $userId
     */
    private function logPasswordChange(int $userId): void
    {
        try {
            $logData = [
                'user_id' => $userId,
                'ip' => Yii::$app->request->getUserIP(),
                'timestamp' => date('Y-m-d H:i:s'),
                'user_agent' => Yii::$app->request->getUserAgent()
            ];
            
            Yii::info('Password changed: ' . json_encode($logData), 'security');
            
        } catch (\Exception $e) {
            Yii::error('Error logging password change: ' . $e->getMessage(), __METHOD__);
        }
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
     * Legacy method for backward compatibility
     *
     * @return bool
     * @deprecated Use changePassword() instead
     */
    public function cbioclave(): bool
    {
        return $this->changePassword();
    }
    
    /**
     * Gets password strength score (0-100)
     *
     * @param string $password
     * @return int
     */
    public function getPasswordStrength(string $password = null): int
    {
        if ($password === null) {
            $password = $this->clave_new;
        }
        
        if (empty($password)) {
            return 0;
        }
        
        $score = 0;
        
        // Length bonus
        $score += min(25, strlen($password) * 2);
        
        // Character variety bonus
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/\d/', $password)) $score += 10;
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 15;
        
        // Complexity bonus
        if (strlen($password) >= 12) $score += 10;
        if (preg_match('/[a-z].*[A-Z]|[A-Z].*[a-z]/', $password)) $score += 5;
        if (preg_match('/\d.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]|[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?].*\d/', $password)) $score += 5;
        
        // Penalties
        if ($this->hasSequentialCharacters($password)) $score -= 15;
        if ($this->hasRepeatedCharacters($password)) $score -= 10;
        if ($this->isCommonPassword($password)) $score -= 20;
        
        return max(0, min(100, $score));
    }
}
