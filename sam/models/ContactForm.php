<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Html;
use yii\web\ServerErrorHttpException;

/**
 * ContactForm is the model behind the contact form.
 *
 * @property string $name
 * @property string $email
 * @property string $subject
 * @property string $body
 * @property string $verifyCode
 */
class ContactForm extends Model
{
    public $name;
    public $email;
    public $subject;
    public $body;
    public $verifyCode;
    
    // Constants for validation
    const MAX_NAME_LENGTH = 100;
    const MAX_EMAIL_LENGTH = 255;
    const MAX_SUBJECT_LENGTH = 200;
    const MAX_BODY_LENGTH = 5000;
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Required fields
            [['name', 'email', 'subject', 'body'], 'required'],
            
            // String length validations
            ['name', 'string', 'max' => self::MAX_NAME_LENGTH],
            ['email', 'string', 'max' => self::MAX_EMAIL_LENGTH],
            ['subject', 'string', 'max' => self::MAX_SUBJECT_LENGTH],
            ['body', 'string', 'max' => self::MAX_BODY_LENGTH],
            
            // Trim whitespace
            [['name', 'email', 'subject', 'body'], 'trim'],
            
            // Email validation
            ['email', 'email', 'message' => 'Por favor ingrese un email válido.'],
            
            // Captcha validation
            ['verifyCode', 'captcha', 'captchaAction' => 'site/captcha'],
            
            // Content filtering
            [['name', 'subject'], 'filter', 'filter' => function($value) {
                return Html::encode($value);
            }],
            
            // Body content validation (basic HTML allowed)
            ['body', 'string', 'min' => 10],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'name' => 'Nombre',
            'email' => 'Correo Electrónico',
            'subject' => 'Asunto',
            'body' => 'Mensaje',
            'verifyCode' => 'Código de Verificación',
        ];
    }
    
    /**
     * Validates that the name doesn't contain suspicious patterns
     */
    public function validateName(): void
    {
        if (!empty($this->name)) {
            // Check for suspicious patterns
            $suspiciousPatterns = [
                '/\b(script|javascript|vbscript)\b/i',
                '/<[^>]*>/',
                '/\b(select|insert|update|delete|drop|union)\b/i'
            ];
            
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $this->name)) {
                    $this->addError('name', 'El nombre contiene caracteres no permitidos.');
                    break;
                }
            }
        }
    }
    
    /**
     * Validates that the email is not from a suspicious domain
     */
    public function validateEmailDomain(): void
    {
        if (!empty($this->email)) {
            $domain = substr(strrchr($this->email, '@'), 1);
            
            // List of commonly blocked domains (can be configured)
            $blockedDomains = [
                'tempmail.org',
                '10minutemail.com',
                'guerrillamail.com',
                'mailinator.com'
            ];
            
            if (in_array(strtolower($domain), $blockedDomains)) {
                $this->addError('email', 'Este dominio de email no está permitido.');
            }
        }
    }
    
    /**
     * Validates that the message doesn't contain spam patterns
     */
    public function validateSpamContent(): void
    {
        if (!empty($this->body)) {
            $spamPatterns = [
                '/\b(viagra|cialis|casino|lottery|winner)\b/i',
                '/\b(click here|free money|make money fast)\b/i',
                '/(http:\/\/|https:\/\/|www\.).*\.(tk|ml|ga|cf)/i', // Suspicious TLDs
                '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', // Credit card patterns
            ];
            
            foreach ($spamPatterns as $pattern) {
                if (preg_match($pattern, $this->body)) {
                    $this->addError('body', 'El mensaje contiene contenido no permitido.');
                    break;
                }
            }
            
            // Check for excessive links
            $linkCount = preg_match_all('/(http:\/\/|https:\/\/|www\.)/i', $this->body);
            if ($linkCount > 3) {
                $this->addError('body', 'El mensaje contiene demasiados enlaces.');
            }
        }
    }
    
    /**
     * Sends an email to the specified email address using the information collected by this model.
     *
     * @param string $email the target email address
     * @return bool whether the email was sent successfully
     */
    public function contact(string $email): bool
    {
        if (!$this->validate()) {
            return false;
        }
        
        try {
            // Additional spam checks
            $this->validateName();
            $this->validateEmailDomain();
            $this->validateSpamContent();
            
            if ($this->hasErrors()) {
                return false;
            }
            
            // Rate limiting check
            if (!$this->checkRateLimit()) {
                $this->addError('email', 'Ha enviado demasiados mensajes. Intente nuevamente más tarde.');
                return false;
            }
            
            // Prepare email content
            $emailBody = $this->prepareEmailBody();
            
            // Send email
            $mailSent = Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setReplyTo([$this->email => $this->name])
                ->setSubject($this->getEmailSubject())
                ->setTextBody($emailBody)
                ->setHtmlBody($this->prepareHtmlEmailBody())
                ->send();
            
            if ($mailSent) {
                // Log successful contact
                $this->logContactAttempt(true);
                return true;
            } else {
                $this->addError('email', 'Error al enviar el mensaje. Intente nuevamente.');
                $this->logContactAttempt(false);
                return false;
            }
            
        } catch (\Exception $e) {
            Yii::error('Contact form error: ' . $e->getMessage(), __METHOD__);
            $this->addError('email', 'Error interno del sistema. Intente nuevamente más tarde.');
            return false;
        }
    }
    
    /**
     * Checks rate limiting for contact form submissions
     *
     * @return bool
     */
    private function checkRateLimit(): bool
    {
        $session = Yii::$app->session;
        $ip = Yii::$app->request->getUserIP();
        $now = time();
        
        // Check session-based rate limiting
        $lastSubmission = $session->get('contact_last_submission', 0);
        $submissionCount = $session->get('contact_submission_count', 0);
        
        // Reset counter if more than 1 hour has passed
        if ($now - $lastSubmission > 3600) {
            $submissionCount = 0;
        }
        
        // Allow maximum 3 submissions per hour
        if ($submissionCount >= 3) {
            return false;
        }
        
        // Update session data
        $session->set('contact_last_submission', $now);
        $session->set('contact_submission_count', $submissionCount + 1);
        
        return true;
    }
    
    /**
     * Prepares the email subject with security prefix
     *
     * @return string
     */
    private function getEmailSubject(): string
    {
        $siteName = Yii::$app->name ?? 'SAM';
        return '[' . $siteName . ' - Contacto] ' . Html::encode($this->subject);
    }
    
    /**
     * Prepares the plain text email body
     *
     * @return string
     */
    private function prepareEmailBody(): string
    {
        $body = "Mensaje de contacto desde el sitio web:\n\n";
        $body .= "Nombre: " . Html::encode($this->name) . "\n";
        $body .= "Email: " . Html::encode($this->email) . "\n";
        $body .= "Asunto: " . Html::encode($this->subject) . "\n\n";
        $body .= "Mensaje:\n" . Html::encode($this->body) . "\n\n";
        $body .= "---\n";
        $body .= "IP: " . Yii::$app->request->getUserIP() . "\n";
        $body .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
        $body .= "User Agent: " . Html::encode(Yii::$app->request->getUserAgent()) . "\n";
        
        return $body;
    }
    
    /**
     * Prepares the HTML email body
     *
     * @return string
     */
    private function prepareHtmlEmailBody(): string
    {
        $html = "<h3>Mensaje de contacto desde el sitio web</h3>";
        $html .= "<p><strong>Nombre:</strong> " . Html::encode($this->name) . "</p>";
        $html .= "<p><strong>Email:</strong> " . Html::encode($this->email) . "</p>";
        $html .= "<p><strong>Asunto:</strong> " . Html::encode($this->subject) . "</p>";
        $html .= "<p><strong>Mensaje:</strong></p>";
        $html .= "<div style='border-left: 3px solid #ccc; padding-left: 15px; margin: 10px 0;'>";
        $html .= nl2br(Html::encode($this->body));
        $html .= "</div>";
        $html .= "<hr>";
        $html .= "<p style='font-size: 12px; color: #666;'>";
        $html .= "IP: " . Yii::$app->request->getUserIP() . "<br>";
        $html .= "Fecha: " . date('Y-m-d H:i:s') . "<br>";
        $html .= "User Agent: " . Html::encode(Yii::$app->request->getUserAgent());
        $html .= "</p>";
        
        return $html;
    }
    
    /**
     * Logs contact form attempts for security monitoring
     *
     * @param bool $success
     */
    private function logContactAttempt(bool $success): void
    {
        try {
            $logData = [
                'ip' => Yii::$app->request->getUserIP(),
                'email' => $this->email,
                'name' => $this->name,
                'subject' => $this->subject,
                'success' => $success,
                'timestamp' => date('Y-m-d H:i:s'),
                'user_agent' => Yii::$app->request->getUserAgent()
            ];
            
            Yii::info('Contact form submission: ' . json_encode($logData), 'contact');
            
        } catch (\Exception $e) {
            Yii::error('Error logging contact attempt: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Legacy method for backward compatibility
     *
     * @param string $email
     * @return bool
     * @deprecated Use contact() method instead
     */
    public function sendEmail(string $email): bool
    {
        return $this->contact($email);
    }
}
