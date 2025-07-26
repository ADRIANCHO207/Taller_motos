<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Administrador</title>
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Reutiliza los estilos del login -->
    <link rel="stylesheet" href="css/login_styles.css"> 
    <link rel="shortcut icon" href="img/logo.jpg" type="image/x-icon">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo-panel"><img src="img/logo.jpg" alt="Logo"></div>
            <div class="login-form-panel">
                <form id="formRecuperar" novalidate>
                    <h2>Recuperar Contraseña</h2>
                    <p class="text-muted mb-4">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

                    <br>

                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="Correo Electrónico" required>
                        <i class="fas validation-icon"></i>
                        <small class="error-message"></small>
                    </div>
                    
                    <button type="submit" id="btnEnviar">Enviar Enlace</button>

                    <div id="form-message" style="margin-bottom: 15px;"></div>

                    <div class="extra-links">
                        <a href="index.php">Volver al Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/recuperar_script.js"></script>
</body>
</html>