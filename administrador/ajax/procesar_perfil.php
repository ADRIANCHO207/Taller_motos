<?php
// Iniciar sesión para acceder a las variables del administrador.
session_start();
// Definir la respuesta como JSON.
header('Content-Type: application/json');

// 1. --- Guardia de Seguridad: Validar que la sesión esté iniciada ---
// Si no existe la variable de sesión 'id_documento', significa que el usuario no está logueado.
if (empty($_SESSION['id_documento'])) {
    // Se envía un código de error HTTP 401 (No Autorizado), una buena práctica para APIs.
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit; // Detener la ejecución inmediatamente.
}

// Incluir la conexión a la base de datos (PDO).
// Se usa una ruta absoluta basada en la ubicación del archivo actual (__DIR__) para máxima portabilidad.
require_once __DIR__ . '/../../conecct/conex.php';

// Instanciar la conexión.
$db = new Database();
$conexion = $db->conectar();

// Determina la operación a realizar.
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
// Se obtiene el ID del administrador directamente de la sesión, no de los datos del formulario,
// para asegurar que un usuario solo pueda modificar su propio perfil.
$id_admin_actual = $_SESSION['id_documento'];

switch ($accion) {
    /**
     * --- Caso para Obtener los datos del perfil actual ---
     * Se usa para poblar el modal de "Mi Perfil" con los datos actuales.
     */
    case 'obtener':
        // Seleccionamos solo los campos necesarios (teléfono y email).
        $stmt = $conexion->prepare("SELECT telefono, email FROM administradores WHERE id_documento = :id");
        $stmt->execute([':id' => $id_admin_actual]);
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($perfil) {
            echo json_encode(['status' => 'success', 'data' => $perfil]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el perfil.']);
        }
        break;

    /**
     * --- Caso para Actualizar los datos del perfil ---
     */
    case 'actualizar':
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        $password_actual = $_POST['password_actual'] ?? '';
        $password_nueva = $_POST['password_nueva'] ?? '';

        // --- Validación de Duplicados ---
        // Se comprueba si el nuevo email o teléfono ya están en uso por OTRO administrador.
        // La condición 'AND id_documento != :id_actual' es crucial para permitir guardar sin cambios.
        $stmt_check = $conexion->prepare("SELECT id_documento FROM administradores WHERE (email = :email OR telefono = :telefono) AND id_documento != :id_actual");
        $stmt_check->execute([':email' => $email, ':telefono' => $telefono, ':id_actual' => $id_admin_actual]);
        
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El email o teléfono ya están en uso por otro administrador.']);
            exit;
        }

        // --- Construcción Dinámica de la Consulta de Actualización ---
        // Se inicia con los campos que siempre se actualizan.
        $sql = "UPDATE administradores SET telefono = :telefono, email = :email";
        $params = [':telefono' => $telefono, ':email' => $email];

        // --- Lógica Segura para el Cambio de Contraseña (solo si se proporciona una nueva) ---
        if (!empty($password_nueva)) {
            // 1. Es obligatorio proporcionar la contraseña actual para cambiarla.
            if (empty($password_actual)) {
                echo json_encode(['status' => 'error', 'message' => 'Debe ingresar su contraseña actual para poder cambiarla.']);
                exit;
            }

            // 2. Se obtiene la contraseña encriptada (hash) de la base de datos.
            $stmt_pass = $conexion->prepare("SELECT password FROM administradores WHERE id_documento = :id");
            $stmt_pass->execute([':id' => $id_admin_actual]);
            $user = $stmt_pass->fetch(PDO::FETCH_ASSOC);
            
            // 3. Se usa password_verify() para comparar de forma segura la contraseña que el usuario escribió
            //    con el hash guardado. NUNCA se desencripta la contraseña de la BD.
            if (!$user || !password_verify($password_actual, $user['password'])) {
                echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta.']);
                exit;
            }

            // 4. Si la contraseña actual es correcta, se añade la nueva contraseña (encriptada) a la consulta.
            $sql .= ", password = :password";
            $params[':password'] = password_hash($password_nueva, PASSWORD_DEFAULT);
        }
        
        // Se añade la cláusula WHERE al final para asegurar que solo se actualice el usuario logueado.
        $sql .= " WHERE id_documento = :id_actual";
        $params[':id_actual'] = $id_admin_actual;

        $stmt = $conexion->prepare($sql);

        // Se ejecuta la consulta final con todos los parámetros necesarios.
        if ($stmt->execute($params)) {
            // Opcional: Si se permitiera cambiar el nombre, aquí se actualizaría la variable de sesión.
            // $_SESSION['nombre'] = $nuevo_nombre;
            echo json_encode(['status' => 'success', 'message' => 'Perfil actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el perfil.']);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>