<?php

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['id_documento'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

require_once('C:/xampp/htdocs/Taller_motos/conecct/conex.php');
$db = new Database();
$conexion = $db->conectar();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$id_admin_actual = $_SESSION['id_documento'];

switch ($accion) {
    case 'obtener':
        $stmt = $conexion->prepare("SELECT telefono, email FROM administradores WHERE id_documento = :id");
        $stmt->execute([':id' => $id_admin_actual]);
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($perfil) {
            echo json_encode(['status' => 'success', 'data' => $perfil]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el perfil.']);
        }
        break;

    case 'actualizar':
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        $password_actual = $_POST['password_actual'] ?? '';
        $password_nueva = $_POST['password_nueva'] ?? '';

        // Validar duplicados
        $stmt_check = $conexion->prepare("SELECT id_documento FROM administradores WHERE (email = :email OR telefono = :telefono) AND id_documento != :id_actual");
        $stmt_check->execute([':email' => $email, ':telefono' => $telefono, ':id_actual' => $id_admin_actual]);
        
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El email o teléfono ya están en uso por otro administrador.']);
            exit;
        }

        // Construcción de la consulta de actualización
        $sql = "UPDATE administradores SET telefono = :telefono, email = :email";
        $params = [':telefono' => $telefono, ':email' => $email];

        if (!empty($password_nueva)) {
            if (empty($password_actual)) {
                echo json_encode(['status' => 'error', 'message' => 'Debe ingresar su contraseña actual para poder cambiarla.']);
                exit;
            }

            $stmt_pass = $conexion->prepare("SELECT password FROM administradores WHERE id_documento = :id");
            $stmt_pass->execute([':id' => $id_admin_actual]);
            $user = $stmt_pass->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password_actual, $user['password'])) {
                echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta.']);
                exit;
            }

            $sql .= ", password = :password";
            $params[':password'] = password_hash($password_nueva, PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id_documento = :id_actual";
        $params[':id_actual'] = $id_admin_actual;

        $stmt = $conexion->prepare($sql);

        if ($stmt->execute($params)) {
            // Actualizar nombre en la sesión si se cambia (aunque en este form no se cambia, es buena práctica)
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
// No se cierra la conexión PDO de esta forma
?>