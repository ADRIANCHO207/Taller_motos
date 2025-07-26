<?php
/**
 * Procesamiento de operaciones CRUD para administradores
 * 
 * Este archivo maneja todas las operaciones relacionadas con los administradores:
 * - Crear nuevo administrador
 * - Leer datos de administrador
 * - Actualizar información de administrador
 * - Eliminar administrador
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../conecct/conex.php';

$db = new Database();
$conexion = $db->conectar(); 

// Configuración para Triggers: Establece el ID del administrador actual para auditoría
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);

// Determinar la acción a realizar (POST tiene prioridad sobre GET)
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        /**
         * Proceso de creación de nuevo administrador
         * 
         * Validaciones:
         * 1. Campos requeridos
         * 2. Documento no existe como cliente
         * 3. No hay duplicados en administradores (documento, email, teléfono)
         */
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
        /**
         * Obtiene los datos de un administrador específico
         * Nota: No devuelve la contraseña por seguridad
         */
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT id_documento, nombre, email, telefono FROM administradores WHERE id_documento = :id");
        $stmt->execute([':id' => $id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($admin);
        break;

    case 'actualizar':
        /**
         * Actualización de datos de administrador
         * 
         * Características:
         * - Actualización selectiva de campos
         * - Construcción dinámica de la consulta SQL
         * - Validación de duplicados excluyendo el registro actual
         * - Actualización opcional de contraseña
         */
        $id = $_POST['id_documento'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validación de campos obligatorios
        if (empty($id) || empty($nombre) || empty($email) || empty($telefono)) {
            echo json_encode(['status' => 'error', 'message' => 'Los campos obligatorios no pueden estar vacíos.']);
            exit;
        }

        // Verificación de duplicados excluyendo el registro actual
        $stmt = $conexion->prepare("SELECT nombre FROM administradores WHERE (nombre = :nombre OR email = :email OR telefono = :telefono) AND id_documento != :id");
        $stmt->execute([':nombre' => $nombre, ':email' => $email, ':telefono' => $telefono, ':id' => $id]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre, email o teléfono ya están en uso por otro administrador.']);
            exit;
        }
        
        /**
         * Construcción dinámica de la consulta SQL
         * - Base: Actualiza campos obligatorios
         * - Opcional: Añade actualización de contraseña si se proporciona
         */
        $sql = "UPDATE administradores SET nombre = :nombre, email = :email, telefono = :telefono";
        $params = [':nombre' => $nombre, ':email' => $email, ':telefono' => $telefono];

        // Añadir contraseña a la actualización solo si se proporciona una nueva
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
        /**
         * Eliminación de administrador
         * 
         * Importante: Verificar antes de implementar si:
         * - Se requiere eliminación lógica en lugar de física
         * - Hay restricciones de foreign key que considerar
         * - Se necesita respaldo de los datos antes de eliminar
         */
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