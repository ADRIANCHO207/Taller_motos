<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
    exit;
}

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        $color = trim($_POST['color'] ?? '');
        if (empty($color)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del color no puede estar vacío.']);
            exit;
        }

        $stmt_check = $conexion->prepare("SELECT id_color FROM color WHERE LOWER(color) = LOWER(?)");
        $stmt_check->bind_param("s", $color);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ese color ya está registrado.']);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO color (color) VALUES (?)");
        $stmt->bind_param("s", $color);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Color agregado correctamente.']);
        }
        $stmt->close();
        break;

    case 'actualizar':
        $id_color = intval($_POST['id_color'] ?? 0);
        $color = trim($_POST['color'] ?? '');
        if (empty($color) || $id_color <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        
        $stmt_check = $conexion->prepare("SELECT id_color FROM color WHERE LOWER(color) = LOWER(?) AND id_color != ?");
        $stmt_check->bind_param("si", $color, $id_color);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ese color ya está en uso.']);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE color SET color = ? WHERE id_color = ?");
        $stmt->bind_param("si", $color, $id_color);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Color actualizado correctamente.']);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id_color = intval($_POST['id'] ?? 0);
        if ($id_color <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM color WHERE id_color = ?");
        $stmt->bind_param("i", $id_color);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Color eliminado correctamente.']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}

$conexion->close();
?>