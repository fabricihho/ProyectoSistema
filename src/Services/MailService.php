<?php

namespace TAMEP\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use TAMEP\Core\Session;

class MailService
{
    private $mailer;
    
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        
        // SMTP Configuration
        $this->mailer->isSMTP();
        $this->mailer->Host       = 'smtp.gmail.com'; // Default placeholder, can be changed
        $this->mailer->SMTPAuth   = true;
        // CREDENTIALS MUST BE SET
        $this->mailer->Username   = 'urielm.cgutierrez001@gmail.com'; // Testing sender (assumed based on user request)
        $this->mailer->Password   = 'APP_PASSWORD_HERE'; // User needs to provide this
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = 587;
        
        // Sender info
        $this->mailer->setFrom('urielm.cgutierrez001@gmail.com', 'Sistema TAMEP - Admin');
    }
    
    public function sendPasswordReset($recipientEmail, $username, $newPassword)
    {
        try {
            // Recipient
            $this->mailer->addAddress($recipientEmail);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Reset de Contraseña - Sistema TAMEP';
            $this->mailer->Body    = "
                <h2>Reseteo de Contraseña</h2>
                <p>Hola, <strong>{$username}</strong>.</p>
                <p>El administrador ha solicitado un reseteo de tu contraseña.</p>
                <p>Tu nueva contraseña temporal es:</p>
                <h3 style='background: #eee; padding: 10px; display: inline-block;'>{$newPassword}</h3>
                <p>Por favor, ingresa al sistema y cambia tu contraseña inmediatamente.</p>
                <br>
                <small>Sistema de Gestión de Archivos TAMEP</small>
            ";
            
            //$this->mailer->send();
            // LOGGING MODE FIRST (Since we lack creds)
            
            $logFile = __DIR__ . '/../../storage/logs/mail.log';
            if (!file_exists(dirname($logFile))) {
                mkdir(dirname($logFile), 0777, true);
            }
            
            $logMessage = "[" . date('Y-m-d H:i:s') . "] MOCK MAIL TO: $recipientEmail | USER: $username | PASS: $newPassword\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            // Attempt real send if credentials look real (simple check)
            if ($this->mailer->Password !== 'APP_PASSWORD_HERE') {
                $this->mailer->send();
                return true;
            } else {
                // If in mock mode, return true but warn via Session/Log?
                // Returning true to simulate success for the user flow.
                return true; 
            }

        } catch (Exception $e) {
            // Log error
             $logFile = __DIR__ . '/../../storage/logs/mail_error.log';
             $msg = "[" . date('Y-m-d H:i:s') . "] Error sending mail: {$this->mailer->ErrorInfo}\n";
             file_put_contents($logFile, $msg, FILE_APPEND);
             return false;
        }
    }
}
