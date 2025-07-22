<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
    exit;
}

// ¡CORRECCIÓN! El nombre de la columna en tu BD es 'marcas' (plural), lo usaremos consistentemente.
$nombre_columna = 'marcas'; 
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        $marca = trim($_POST['marca'] ?? '');
        if (empty($marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca no puede estar vacío.']);
            exit;
        }

        // Validación de solo letras en el backend
        if (!preg_match('/^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/', $marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca solo puede contener letras y espacios.']);
            exit;
        }

        // ¡CORRECCIÓN! Compara en minúsculas para evitar duplicados como "Honda" y "honda"
        $stmt_check = $conexion->prepare("SELECT id_marca FROM marcas WHERE LOWER($nombre_columna) = LOWER(?)");
        $stmt_check->bind_param("s", $marca);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Esa marca ya está registrada.']);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO marcas ($nombre_columna) VALUES (?)");
        $stmt->bind_param("s", $marca);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Marca agregada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar la marca.']);
        }
        $stmt->close();
        break;

    case 'actualizar':
        $id_marca = $_POST['id_marca'] ?? 0;
        $marca = trim($_POST['marca'] ?? '');
        if (empty($marca) || $id_marca <= 0) { /* ... */ }
        
        // Validación de solo letras
        if (!preg_match('/^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/', $marca)) { /* ... */ }

        // ¡CORRECCIÓN! Compara en minúsculas y excluye el ID actual
        $stmt_check = $conexion->prepare("SELECT id_marca FROM marcas WHERE LOWER($nombre_columna) = LOWER(?) AND id_marca != ?");
        $stmt_check->bind_param("si", $marca, $id_marca);
        if ($stmt_check->execute() && $stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Esa marca ya está en uso.']);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE marcas SET $nombre_columna = ? WHERE id_marca = ?");
        $stmt->bind_param("si", $marca, $id_marca);
        $id_marca = $_POST['id_marca'] ?? 0;
        $marca = trim($_POST['marca'] ?? '');
        if (empty($marca) || $id_marca <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        
        $stmt_check = $conexion->prepare("SELECT id_marca FROM marcas WHERE marcas = ? AND id_marca != ?");
        $stmt_check->bind_param("si", $marca, $id_marca);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Esa marca ya está en uso por otro registro.']);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE marcas SET marcas = ? WHERE id_marca = ?");
        $stmt->bind_param("si", $marca, $id_marca);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Marca actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la marca.']);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id_marca = $_POST['id'] ?? 0;
        if ($id_marca <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM marcas WHERE id_marca = ?");
        $stmt->bind_param("i", $id_marca);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Marca eliminada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar.']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}

$conexion->close();
?>