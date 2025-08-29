<?php
/**
 * SERVICIO DE EMAIL ALTERNATIVO - SIN SMTP EXTERNO
 */

class EmailServiceAlternativo {
    
    private $admin_email = 'dayer12392@gmail.com';
    private $empresa_nombre = 'Jaguar Expeditions';
    private $empresa_telefono = '+51 999 123 456';
    
    /**
     * Enviar email usando función mail() nativa de PHP
     */
    public function enviarEmail($para, $asunto, $mensaje) {
        // Headers para email HTML
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->empresa_nombre} <{$this->admin_email}>\r\n";
        $headers .= "Reply-To: {$this->admin_email}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Intentar enviar el email
        $resultado = @mail($para, $asunto, $mensaje, $headers);
        
        // Log del intento
        $this->logEmail($para, $asunto, $resultado);
        
        return $resultado;
    }
    
    /**
     * Enviar notificación simplificada al administrador
     */
    public function enviarNotificacionContacto($datosContacto) {
        $asunto = "Nuevo contacto - " . $this->empresa_nombre;
        
        $mensaje = $this->generarEmailSimpleAdmin($datosContacto);
        
        return $this->enviarEmail($this->admin_email, $asunto, $mensaje);
    }
    
    /**
     * Enviar confirmación simple al cliente
     */
    public function enviarConfirmacionCliente($datosContacto) {
        $asunto = "Hemos recibido tu mensaje - " . $this->empresa_nombre;
        
        $mensaje = $this->generarEmailSimpleCliente($datosContacto);
        
        return $this->enviarEmail($datosContacto['email'], $asunto, $mensaje);
    }
    
    /**
     * Generar email simple para el administrador
     */
    private function generarEmailSimpleAdmin($datos) {
        $tourTexto = $this->obtenerNombreTour($datos['tour_interes']) ?: $datos['tour_interes_texto'] ?: 'No especificado';
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Nuevo mensaje de contacto recibido</h2>
            
            <h3>Información del cliente:</h3>
            <ul>
                <li><strong>ID:</strong> {$datos['id']}</li>
                <li><strong>Nombre:</strong> {$datos['nombre']}</li>
                <li><strong>Email:</strong> {$datos['email']}</li>
                <li><strong>Teléfono:</strong> {$datos['telefono']}</li>
                <li><strong>País:</strong> {$datos['pais']}</li>
                <li><strong>Tour de interés:</strong> {$tourTexto}</li>
                <li><strong>Fecha preferida:</strong> {$datos['fecha_viaje']}</li>
                <li><strong>Personas:</strong> {$datos['personas']}</li>
                <li><strong>Newsletter:</strong> " . ($datos['newsletter'] ? 'Sí' : 'No') . "</li>
            </ul>
            
            <h3>Mensaje:</h3>
            <p style='background: #f5f5f5; padding: 15px; border-left: 4px solid #007cba;'>
                {$datos['mensaje']}
            </p>
            
            <hr>
            <p><small>Enviado desde el formulario de contacto | IP: {$datos['ip']} | " . date('d/m/Y H:i:s') . "</small></p>
        </body>
        </html>";
    }
    
    /**
     * Generar email simple para el cliente
     */
    private function generarEmailSimpleCliente($datos) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>¡Gracias por contactarnos, {$datos['nombre']}!</h2>
            
            <p>Hemos recibido tu mensaje y nos pondremos en contacto contigo en las próximas 24 horas.</p>
            
            <h3>Resumen de tu consulta:</h3>
            <p><strong>Mensaje:</strong> {$datos['mensaje']}</p>
            
            <hr>
            
            <h3>¿Necesitas ayuda inmediata?</h3>
            <p>Llámanos al: <strong>{$this->empresa_telefono}</strong></p>
            
            <p>Saludos cordiales,<br>
            <strong>Equipo de {$this->empresa_nombre}</strong></p>
        </body>
        </html>";
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
     * Log de intentos de email
     */
    private function logEmail($para, $asunto, $resultado) {
        $timestamp = date('Y-m-d H:i:s');
        $status = $resultado ? 'ÉXITO' : 'ERROR';
        $logMessage = "[{$timestamp}] {$status} - Para: {$para} - Asunto: {$asunto}\n";
        
        @file_put_contents(__DIR__ . '/logs/email_simple.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}
?>
