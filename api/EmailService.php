<?php
/**
 * SERVICIO DE ENVÍO DE EMAILS - JAGUAR EXPEDITIONS
 * ===============================================
 * 
 * Maneja el envío de emails usando PHPMailer y SMTP de Gmail
 */

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_username = 'dayer12392@gmail.com';
    private $smtp_password = 'atoa mbya ircm vwwm';
    private $admin_email = 'dayer12392@gmail.com';
    private $empresa_nombre = 'Jaguar Expeditions';
    private $empresa_telefono = '+51 999 123 456';
    
    /**
     * Enviar email - Versión simplificada que usa mail() básico
     */
    public function enviarEmail($para, $asunto, $mensaje, $esHTML = true) {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración del servidor SMTP para Gmail (igual que el test exitoso)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dayer12392@gmail.com';
            $mail->Password = 'atoa mbya ircm vwwm';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            // Configuración del remitente
            $mail->setFrom('dayer12392@gmail.com', $this->empresa_nombre);
            $mail->addAddress($para);
            
            // Configuración del mensaje
            $mail->isHTML($esHTML);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;
            
            // Enviar el email
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            // Log del error detallado
            $error = "Error enviando email: " . $e->getMessage();
            $this->logError($error);
            return false;
        }
    }
    
    /**
     * Enviar notificación de contacto al administrador
     */
    public function enviarNotificacionContacto($datosContacto) {
        $asunto = "🔔 Nuevo mensaje de contacto - " . $this->empresa_nombre;
        
        $mensaje = $this->generarEmailAdmin($datosContacto);
        
        return $this->enviarEmail($this->admin_email, $asunto, $mensaje, true);
    }
    
    /**
     * Enviar confirmación al cliente
     */
    public function enviarConfirmacionCliente($datosContacto) {
        $asunto = "Hemos recibido tu mensaje - " . $this->empresa_nombre;
        
        $mensaje = $this->generarEmailCliente($datosContacto);
        
        return $this->enviarEmail($datosContacto['email'], $asunto, $mensaje, true);
    }
    
    /**
     * Generar HTML del email para el administrador
     */
    private function generarEmailAdmin($datos) {
        $tourTexto = $this->obtenerNombreTour($datos['tour_interes']) ?: $datos['tour_interes_texto'] ?: 'No especificado';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 10px;'>
                    🌿 Nuevo mensaje recibido - Jaguar Expeditions
                </h2>
                
                <div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>📧 ID de contacto:</strong> {$datos['id']}</p>
                    <p><strong>⏰ Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
                </div>
                
                <h3 style='color: #155724;'>👤 Información del cliente:</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Nombre:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$datos['nombre']}</td></tr>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><a href='mailto:{$datos['email']}'>{$datos['email']}</a></td></tr>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Teléfono:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$datos['telefono']}</td></tr>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>País:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$datos['pais']}</td></tr>
                    <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>🎯 Tour de interés:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><span style='background: #fff3cd; padding: 3px 8px; border-radius: 3px;'>{$tourTexto}</span></td></tr>";
        
        if ($datos['fecha_viaje']) {
            $mensaje .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>📅 Fecha preferida:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$datos['fecha_viaje']}</td></tr>";
        }
        
        if ($datos['personas']) {
            $mensaje .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>👥 Personas:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$datos['personas']}</td></tr>";
        }
        
        if ($datos['newsletter']) {
            $mensaje .= "<tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>📧 Newsletter:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'><span style='color: #28a745;'>✅ Sí quiere recibir ofertas</span></td></tr>";
        }
        
        $mensaje .= "</table>
                
                <h3 style='color: #155724; margin-top: 25px;'>💬 Mensaje del cliente:</h3>
                <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; font-style: italic;'>
                    {$datos['mensaje']}
                </div>
                
                <div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;'>
                    <h4 style='margin: 0; color: #1976d2;'>🚀 Próximos pasos:</h4>
                    <ul style='margin: 10px 0;'>
                        <li>Responder al cliente en las próximas 2-4 horas</li>
                        <li>Enviar información detallada del tour solicitado</li>
                        <li>Coordinar fecha y detalles específicos</li>
                    </ul>
                </div>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                <p style='font-size: 12px; color: #666; text-align: center;'>
                    <strong>IP:</strong> {$datos['ip']} | 
                    <strong>Sistema:</strong> Jaguar Expeditions
                </p>
            </div>
        </div>";
        
        return $mensaje;
    }
    
    /**
     * Generar HTML del email para el cliente
     */
    private function generarEmailCliente($datos) {
        $tourTexto = $this->obtenerNombreTour($datos['tour_interes']) ?: $datos['tour_interes_texto'] ?: '';
        
        $mensaje = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px;'>
            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #28a745; text-align: center;'>
                    🌿 ¡Gracias por contactarnos, {$datos['nombre']}!
                </h2>
                
                <div style='background: #e8f5e8; padding: 20px; border-radius: 5px; margin: 25px 0; text-align: center;'>
                    <h3 style='color: #155724; margin: 0;'>✅ Tu mensaje ha sido recibido</h3>
                    <p style='margin: 10px 0 0 0;'>Nos pondremos en contacto contigo en las próximas 24 horas</p>
                </div>
                
                <h3 style='color: #155724;'>📋 Resumen de tu consulta:</h3>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        
        if ($tourTexto) {
            $mensaje .= "<p><strong>🎯 Tour de interés:</strong> {$tourTexto}</p>";
        }
        
        if ($datos['fecha_viaje']) {
            $mensaje .= "<p><strong>📅 Fecha preferida:</strong> {$datos['fecha_viaje']}</p>";
        }
        
        if ($datos['personas']) {
            $mensaje .= "<p><strong>👥 Número de personas:</strong> {$datos['personas']}</p>";
        }
        
        $mensaje .= "</div>
                
                <h3 style='color: #155724;'>💬 Tu mensaje:</h3>
                <div style='background: #f1f3f4; padding: 15px; border-left: 4px solid #28a745; font-style: italic;'>
                    {$datos['mensaje']}
                </div>
                
                <div style='margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white; text-align: center;'>
                    <h3 style='margin: 0; color: white;'>🗂️ ¿Necesitas ayuda inmediata?</h3>
                    <p style='margin: 10px 0;'>Llámanos o escríbenos por WhatsApp</p>
                    <p style='margin: 5px 0; font-size: 18px; font-weight: bold;'>{$this->empresa_telefono}</p>
                </div>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                <div style='text-align: center; color: #666;'>
                    <p><strong>Equipo de {$this->empresa_nombre}</strong></p>
                    <p style='font-size: 12px;'>Tu aventura amazónica comienza aquí 🌿</p>
                </div>
            </div>
        </div>";
        
        return $mensaje;
    }
    
    /**
     * Obtener nombre del tour por ID
     */
    private function obtenerNombreTour($tourId) {
        if (!$tourId) return null;
        
        $tours = [
            1 => 'Expedición Río Amazonas',
            2 => 'Safari Nocturno Amazonico',
            3 => 'Comunidades Nativas',
            4 => 'Aventura Extrema',
            5 => 'Tour Personalizado'
        ];
        
        return $tours[$tourId] ?? null;
    }
    
    /**
     * Método de respaldo usando mail() básico
     */
    private function enviarEmailBasico($para, $asunto, $mensaje, $esHTML = true) {
        $headers = "From: {$this->admin_email}\r\n";
        $headers .= "Reply-To: {$this->admin_email}\r\n";
        if ($esHTML) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        // Usar @ para suprimir warnings y devolver resultado
        return @mail($para, $asunto, $mensaje, $headers);
    }
    
    /**
     * Log de errores
     */
    private function logError($mensaje) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$mensaje}\n";
        @file_put_contents(__DIR__ . '/logs/email_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}
?>
