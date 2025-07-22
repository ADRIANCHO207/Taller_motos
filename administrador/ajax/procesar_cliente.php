<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        // 1. Validar campos vacíos
        if (empty($_POST['documento']) || empty($_POST['nombre']) || empty($_POST['telefono']) || empty($_POST['fecha_ingreso'])) {
            echo json_encode(['status' => 'error', 'message' => 'Documento, nombre, teléfono y fecha de ingreso son obligatorios.']);
            exit;
        }

        $documento = $_POST['documento'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $fecha_ingreso = $_POST['fecha_ingreso'];
        

        $stmt_check_admin = $conexion->prepare("SELECT id_documento FROM administradores WHERE id_documento = ?");
        $stmt_check_admin->bind_param("s", $documento);
        $stmt_check_admin->execute();
        $result_admin_check = $stmt_check_admin->get_result();
        
        if ($result_admin_check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'El número de documento ya está registrado como un administrador.']);
            $stmt_check_admin->close();
            $conexion->close();
            exit;
        }
        $stmt_check_admin->close();


        // --- VERIFICACIÓN DE DUPLICADOS EN LA TABLA DE CLIENTES (la que ya tenías) ---
        $stmt_check_cliente = $conexion->prepare("SELECT id_documento_cli FROM clientes WHERE id_documento_cli = ? OR telefono = ?");
        $stmt_check_cliente->bind_param("ss", $documento, $telefono);
        $stmt_check_cliente->execute();
        $result_cliente_check = $stmt_check_cliente->get_result();
        
        if ($result_cliente_check->num_rows > 0) {
            $existing_user = $result_cliente_check->fetch_assoc();
            $message = ($existing_user['id_documento_cli'] == $documento) 
                ? 'El número de documento ya está registrado como cliente.' 
                : 'El número de teléfono ya está registrado por otro cliente.';
            
            echo json_encode(['status' => 'error', 'message' => $message]);
            $stmt_check_cliente->close();
            $conexion->close();
            exit;
        }
        $stmt_check_cliente->close();
        
        // Si todas las validaciones pasan, se procede con la inserción
        $stmt = $conexion->prepare("INSERT INTO clientes (id_documento_cli, nombre, telefono, email, direccion, fecha_ingreso, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $documento, $nombre, $telefono, $email, $direccion, $fecha_ingreso);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar cliente: ' . $stmt->error]);
        }
        $stmt->close();
        break;
        
    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_documento_cli = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        echo json_encode($cliente);
        $stmt->close();
        break;

    case 'actualizar':
        $id = $_POST['id_documento_cli'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        // Verificar duplicados (excluyendo al propio cliente)
        $stmt_check = $conexion->prepare("SELECT id_documento_cli FROM clientes WHERE (telefono = ?) AND id_documento_cli != ?");
        $stmt_check->bind_param("ss", $telefono, $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'El teléfono ya está en uso por otro cliente.']);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE clientes SET nombre = ?, telefono = ?, email = ?, direccion = ? WHERE id_documento_cli = ?");
        $stmt->bind_param("sssss", $nombre, $telefono, $email, $direccion, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id = $_POST['id'] ?? 0;
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_documento_cli = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente y sus datos asociados eliminados.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $stmt->error]);
        }
        $stmt->close();
        break;
}

$conexion->close();
?>