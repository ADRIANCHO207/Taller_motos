<?php
// Inicia la sesión para poder acceder a las variables de sesión existentes
session_start();
// Elimina la variable de sesión 'id_documento' que almacena la identificación del usuario
unset($_SESSION['id_documento']);
// Elimina la variable de sesión 'nombre' que almacena el nombre del usuario
unset($_SESSION['nombre']);
// Destruye completamente la sesión actual, eliminando todos los datos
session_destroy();
// Fuerza la escritura de los datos de sesión y cierra la sesión
session_write_close();

// Verifica si la constante BASE_URL ya está definida para evitar redefinición
if (!defined('BASE_URL')) {
    // Detecta si el servidor es localhost (entorno de desarrollo)
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        // Define BASE_URL para entorno local (XAMPP)
        define('BASE_URL', '/Taller_motos');
    } else {
        // Define BASE_URL para entorno de producción (hosting)
        define('BASE_URL', ''); // O '/subcarpeta' si tu proyecto está en una subcarpeta en el hosting
    }
}

    // Preparar respuesta JSON
    header('Content-Type: application/json');

    // Enviar respuesta de éxito
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente'
    ]);
    exit;
?>
