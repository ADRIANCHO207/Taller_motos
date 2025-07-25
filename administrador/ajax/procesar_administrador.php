<?php
session_start();
header('Content-Type: application/json');


require_once __DIR__ . '/../../conecct/conex.php';

$db = new Database();
$conexion = $db->conectar(); 

// Establecer la variable de sesión de MySQL para los Triggers
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        if (empty($_POST['documento']) || empty($_POST['nombre']) || empty($_POST['email']) || empty($_POST['telefono']) || empty($_POST['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        $documento = $_POST['documento'];
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Validar en tabla de clientes
        $stmt = $conexion->prepare("SELECT id_documento_cli FROM clientes WHERE id_documento_cli = :documento");
        $stmt->execute([':documento' => $documento]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El documento ya está registrado como cliente.']);
            exit;
        }

        // Validar duplicados en administradores
        $stmt = $conexion->prepare("SELECT id_documento FROM administradores WHERE id_documento = :documento OR email = :email OR telefono = :telefono");
        $stmt->execute([':documento' => $documento, ':email' => $email, ':telefono' => $telefono]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El documento, email o teléfono ya están registrados.']);
            exit;
        }

        // Insertar
        $stmt = $conexion->prepare("INSERT INTO administradores (id_documento, nombre, email, telefono, password, fecha_creacion) VALUES (:doc, :nom, :email, :tel, :pass, NOW())");
        if ($stmt->execute([':doc' => $documento, ':nom' => $nombre, ':email' => $email, ':tel' => $telefono, ':pass' => $password])) {
            echo json_encode(['status' => 'success', 'message' => 'Administrador agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el administrador.']);
        }
        break;

    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT id_documento, nombre, email, telefono FROM administradores WHERE id_documento = :id");
        $stmt->execute([':id' => $id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($admin);
        break;

    case 'actualizar':
        $id = $_POST['id_documento'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($id) || empty($nombre) || empty($email) || empty($telefono)) {
            echo json_encode(['status' => 'error', 'message' => 'Los campos obligatorios no pueden estar vacíos.']);
            exit;
        }

        // Validar duplicados
        $stmt = $conexion->prepare("SELECT nombre FROM administradores WHERE (nombre = :nombre OR email = :email OR telefono = :telefono) AND id_documento != :id");
        $stmt->execute([':nombre' => $nombre, ':email' => $email, ':telefono' => $telefono, ':id' => $id]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre, email o teléfono ya están en uso por otro administrador.']);
            exit;
        }
        
        // Construcción de la consulta de actualización
        $sql = "UPDATE administradores SET nombre = :nombre, email = :email, telefono = :telefono";
        $params = [':nombre' => $nombre, ':email' => $email, ':telefono' => $telefono];

        if (!empty($password)) {
            $sql .= ", password = :password";
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id_documento = :id";
        $params[':id'] = $id;

        $stmt = $conexion->prepare($sql);

        if ($stmt->execute($params)) {
            echo json_encode(['status' => 'success', 'message' => 'Administrador actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el administrador.']);
        }
        break;

    case 'eliminar':
        $id = $_POST['id'] ?? 0;
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado.']);
            exit;
        }

        $stmt = $conexion->prepare("DELETE FROM administradores WHERE id_documento = :id");
        if ($stmt->execute([':id' => $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Administrador eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el administrador.']);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>