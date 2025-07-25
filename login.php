<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Administrador - Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link rel="shortcut icon" href="img/logo.jpg" type="image/x-icon">

    <link rel="stylesheet" href="css/login_styles.css">

</head>

<body onload="loginForm.documento.focus()">

    <div class="login-container">
        <div class="login-box">
            <!-- Panel del Logo -->
            <div class="login-logo-panel">
                <img src="img/logo.jpg" alt="Logo Taller de Motos">
            </div>
            
            <!-- Panel del Formulario -->
            <div class="login-form-panel">
                <form id="loginForm" novalidate>
                    <h2>Bienvenido Administrador</h2>
                    
                    <div class="input-group">
                        <input type="text" id="documento" name="documento" placeholder="Documento" required>
                        <i class="fas validation-icon"></i>
                        <small class="error-message"></small>
                    </div>

                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <i class="fas validation-icon"></i>
                        <small class="error-message"></small>
                    </div>

                    <!-- =========== AÑADE ESTE DIV AQUÍ =========== -->
                    <div id="form-message"></div>
                    <!-- ============================================== -->
                    
                    <button type="submit" id="loginButton">Login</button>
                    
                    <div class="extra-links">
                        <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
                    </div>
                </form>
            </div>

    <!-- Script de JavaScript -->
    <script src="js/login_script.js"></script>
</body>
</html>