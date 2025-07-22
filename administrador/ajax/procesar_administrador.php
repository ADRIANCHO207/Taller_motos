<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Determinar la acción a realizar
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        // 1. Validar campos vacíos
        if (empty($_POST['documento']) || empty($_POST['nombre']) || empty($_POST['email']) || empty($_POST['telefono']) || empty($_POST['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // 2. Recoger y sanitizar datos
        $documento = $_POST['documento'];
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // --- ¡NUEVA VALIDACIÓN! VERIFICAR EN LA TABLA DE CLIENTES ---
        // Asegúrate de que la columna en la tabla 'clientes' se llame 'id_documento_cli'
        $stmt_check_cliente = $conexion->prepare("SELECT id_documento_cli FROM clientes WHERE id_documento_cli = ?");
        $stmt_check_cliente->bind_param("s", $documento);
        $stmt_check_cliente->execute();
        
        if ($stmt_check_cliente->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'El número de documento ya está registrado como un cliente.']);
            $stmt_check_cliente->close();
            $conexion->close();
            exit;
        }
        $stmt_check_cliente->close();
        // --- FIN DE LA NUEVA VALIDACIÓN ---

        // 3. Validar duplicados en la propia tabla de administradores
        $stmt_check_admin = $conexion->prepare("SELECT id_documento FROM administradores WHERE id_documento = ? OR email = ? OR telefono = ?");
        $stmt_check_admin->bind_param("sss", $documento, $email, $telefono);
        $stmt_check_admin->execute();
        $result_admin = $stmt_check_admin->get_result();

        if ($result_admin->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'El documento, email o teléfono ya están registrados por otro administrador.']);
            $stmt_check_admin->close();
            $conexion->close();
            exit;
        }
        $stmt_check_admin->close();

        // 4. Si todas las validaciones pasan, insertar
        $stmt_insert = $conexion->prepare("INSERT INTO administradores (id_documento, nombre, email, telefono, password, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt_insert->bind_param("sssss", $documento, $nombre, $email, $telefono, $password);

        if ($stmt_insert->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Administrador agregado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el administrador: ' . $stmt_insert->error]);
        }
        $stmt_insert->close();
        break;

    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT id_documento, nombre, email, telefono FROM administradores WHERE id_documento = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $admin = $resultado->fetch_assoc();
        echo json_encode($admin);
        $stmt->close();
        break;

    case 'actualizar':
        $id = $_POST['id_documento'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $password = $_POST['password'] ?? '';

        // --- VALIDACIÓN DE CAMPOS VACÍOS PARA ACTUALIZAR ---
        if (empty($id) || empty($nombre) || empty($email) || empty($telefono)) {
            echo json_encode(['status' => 'error', 'message' => 'Los campos nombre, email y teléfono no pueden estar vacíos.']);
            exit;
        }

        // --- VALIDACIÓN PARA EVITAR DATOS DUPLICADOS (NOMBRE, EMAIL Y TELÉFONO) ---
        // Consulta para verificar si el nuevo nombre, email O teléfono ya existen en OTRO administrador.
        $stmt_check = $conexion->prepare("SELECT nombre, email, telefono FROM administradores WHERE (nombre = ? OR email = ? OR telefono = ?) AND id_documento != ?");
        $stmt_check->bind_param("sssi", $nombre, $email, $telefono, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Si encontró filas, determinamos cuál campo es el duplicado para dar un mensaje específico.
            $existing_user = $result_check->fetch_assoc();
            $message = 'Error desconocido de duplicado.'; // Mensaje por defecto

            if ($existing_user['nombre'] == $nombre) {
                $message = 'El nombre de usuario ya está en uso por otro administrador.';
            } elseif ($existing_user['email'] == $email) {
                $message = 'El email ya está en uso por otro administrador.';
            } elseif ($existing_user['telefono'] == $telefono) {
                $message = 'El número de teléfono ya está en uso por otro administrador.';
            }

            echo json_encode(['status' => 'error', 'message' => $message]);
            $stmt_check->close();
            $conexion->close();
            exit;
        }
        $stmt_check->close();
        // --- FIN DE LA VALIDACIÓN DE DUPLICADOS ---


        // Construir la consulta de actualización dinámicamente
        $sql = "UPDATE administradores SET nombre = ?, email = ?, telefono = ?";
        $params = [$nombre, $email, $telefono];
        $types = "sss";

        if (!empty($password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $types .= "s";
        }

        $sql .= " WHERE id_documento = ?";
        $params[] = $id;
        $types .= "s";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Administrador actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id = $_POST['id'] ?? 0;
        $stmt = $conexion->prepare("DELETE FROM administradores WHERE id_documento = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Administrador eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $stmt->error]);
        }
        $stmt->close();
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}

$conexion->close();
?>