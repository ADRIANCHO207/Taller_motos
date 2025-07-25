<?php
// Validar que el token exista y sea válido
$token = $_GET['token'] ?? '';
if (empty($token)) { die("Token no proporcionado."); }

require_once 'conecct/conex.php';
$db = new Database();
$conexion = $db->conectar();

$stmt = $conexion->prepare("SELECT id_documento FROM administradores WHERE token_password = :token AND token_expiracion > NOW()");
$stmt->execute([':token' => $token]);
if (!$stmt->fetch()) {
    die("El enlace de recuperación es inválido o ha expirado. Por favor, solicita uno nuevo.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/login_styles.css">
    <link rel="shortcut icon" href="img/logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo-panel"><img src="img/logo.jpg" alt="Logo"></div>
            <div class="login-form-panel">
                <form id="formReset" novalidate>
                    <h2>Establecer Nueva Contraseña</h2>
                    <br>
                    
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="input-group">
                        <input type="password" id="password_nueva" name="password_nueva" placeholder="Nueva Contraseña" required>
                        <i class="fas validation-icon"></i><small class="error-message"></small>
                    </div>
                    <div class="input-group">
                        <input type="password" id="confirmar_password" name="confirmar_password" placeholder="Confirmar Contraseña" required>
                        <i class="fas validation-icon"></i><small class="error-message"></small>
                    </div>
                    
                    <button type="submit">Guardar Contraseña</button>

                    <div id="form-message"></div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/recuperar_script.js"></script>
</body>
</html>