<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$current_year = date('Y');

switch ($accion) {
    case 'agregar':
        $anio = intval($_POST['anio'] ?? 0);
        if ($anio < 1980 || $anio > ($current_year + 2)) {
            echo json_encode(['status' => 'error', 'message' => "El año debe ser un número válido entre 1980 y " . ($current_year + 2) . "."]);
            exit;
        }

        $stmt_check = $conexion->prepare("SELECT id_modelo FROM modelos WHERE anio = ?");
        $stmt_check->bind_param("i", $anio);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ese año ya está registrado.']);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO modelos (anio) VALUES (?)");
        $stmt->bind_param("i", $anio);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Modelo (año) agregado correctamente.']);
        }
        $stmt->close();
        break;

    case 'actualizar':
        $id_modelo = intval($_POST['id_modelo'] ?? 0);
        $anio = intval($_POST['anio'] ?? 0);
        if ($anio < 1980 || $anio > ($current_year + 2) || $id_modelo <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        
        $stmt_check = $conexion->prepare("SELECT id_modelo FROM modelos WHERE anio = ? AND id_modelo != ?");
        $stmt_check->bind_param("ii", $anio, $id_modelo);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ese año ya está en uso por otro registro.']);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE modelos SET anio = ? WHERE id_modelo = ?");
        $stmt->bind_param("ii", $anio, $id_modelo);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Modelo (año) actualizado correctamente.']);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id_modelo = intval($_POST['id'] ?? 0);
        if ($id_modelo <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM modelos WHERE id_modelo = ?");
        $stmt->bind_param("i", $id_modelo);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Modelo (año) eliminado correctamente.']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}

$conexion->close();
?>