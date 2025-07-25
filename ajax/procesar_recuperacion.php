<?php
session_start();
header('Content-Type: application/json');


require_once '../vendor/autoload.php';
require_once '../conecct/conex.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$db = new Database();
$conexion = $db->conectar();
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'solicitar':
        $email = $_POST['email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Por favor, ingresa un correo válido.']);
            exit;
        }

        $stmt = $conexion->prepare("SELECT id_documento, nombre FROM administradores WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt_update = $conexion->prepare("UPDATE administradores SET token_password = :token, token_expiracion = :expiracion WHERE email = :email");
            $stmt_update->execute([':token' => $token, ':expiracion' => $expiracion, ':email' => $email]);
            
            $mail = new PHPMailer(true);
            try {
                // Configuración del servidor SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'tallermotoslamejor@gmail.com'; // <<<===== ¡CAMBIA ESTO!
                $mail->Password   = 'fvno ibag mbzi kfbt'; // <<<===== ¡CAMBIA ESTO POR TU CONTRASEÑA DE APLICACIÓN DE 16 LETRAS!
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('tallermotoslamejor@gmail.com', 'Soporte Taller de Motos'); // <<<===== ¡CAMBIA ESTO!
                $mail->addAddress($email, $user['nombre']);

                $reset_link = "http://localhost/Taller_motos/reset_password.php?token=" . $token;
                $mail->isHTML(true);
                $mail->Subject = 'Restablecimiento de Contrasena - Taller de Motos';
                $mail->Body    = "Hola " . $user['nombre'] . ",<br><br>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:<br><br><a href='" . $reset_link . "' style='padding:10px 15px; background-color:#007bff; color:white; text-decoration:none; border-radius:5px;'>Restablecer Contraseña</a><br><br>Si no solicitaste esto, puedes ignorar este correo.<br>Este enlace expirará en 1 hora.";
                
                $mail->send();
            } catch (Exception $e) {
                // Para depuración, puedes registrar el error real.
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
                echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el correo de recuperación. Por favor, contacta al soporte.']);
                exit;
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Si tu correo está registrado, recibirás un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada y spam.']);
        break;


    case 'resetear':
        $token = $_POST['token'] ?? '';
        $password = $_POST['password_nueva'] ?? '';

        // Validar que el token sea válido y no haya expirado
        $stmt = $conexion->prepare("SELECT id_documento FROM administradores WHERE token_password = :token AND token_expiracion > NOW()");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'El enlace es inválido o ha expirado.']);
            exit;
        }

        // Si es válido, actualizar la contraseña y limpiar el token
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_update = $conexion->prepare("UPDATE administradores SET password = :password, token_password = NULL, token_expiracion = NULL WHERE id_documento = :id");
        if ($stmt_update->execute([':password' => $hashed_password, ':id' => $user['id_documento']])) {
            echo json_encode(['status' => 'success', 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);
        }
        break;
}
?>