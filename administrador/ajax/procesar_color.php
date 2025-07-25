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
$regex_letras = '/^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/';

switch ($accion) {
    case 'agregar':
        $color = trim($_POST['color'] ?? '');

        // Validaciones
        if (empty($color)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del color no puede estar vacío.']);
            exit;
        }
        if (!preg_match($regex_letras, $color)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del color solo puede contener letras y espacios.']);
            exit;
        }

        // Verificar duplicados (case-insensitive)
        $stmt = $conexion->prepare("SELECT id_color FROM color WHERE LOWER(color) = LOWER(:color)");
        $stmt->execute([':color' => $color]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ese color ya está registrado.']);
            exit;
        }

        // Insertar
        $stmt = $conexion->prepare("INSERT INTO color (color) VALUES (:color)");
        if ($stmt->execute([':color' => $color])) {
            echo json_encode(['status' => 'success', 'message' => 'Color agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el color.']);
        }
        break;

    case 'actualizar':
        $id_color = intval($_POST['id_color'] ?? 0);
        $color = trim($_POST['color'] ?? '');

        // Validaciones
        if (empty($color) || $id_color <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        if (!preg_match($regex_letras, $color)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del color solo puede contener letras y espacios.']);
            exit;
        }
        
        // Verificar duplicados (excluyendo el registro actual, case-insensitive)
        $stmt = $conexion->prepare("SELECT id_color FROM color WHERE LOWER(color) = LOWER(:color) AND id_color != :id_color");
        $stmt->execute([':color' => $color, ':id_color' => $id_color]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ese color ya está en uso.']);
            exit;
        }
        
        // Actualizar
        $stmt = $conexion->prepare("UPDATE color SET color = :color WHERE id_color = :id_color");
        if ($stmt->execute([':color' => $color, ':id_color' => $id_color])) {
            echo json_encode(['status' => 'success', 'message' => 'Color actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el color.']);
        }
        break;

    case 'eliminar':
        $id_color = intval($_POST['id'] ?? 0);
        if ($id_color <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // Opcional: Verificar dependencias en la tabla 'motos'
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM motos WHERE id_color = :id_color");
        $stmt_check->execute([':id_color' => $id_color]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Este color está en uso por una o más motos.']);
            exit;
        }

        // Eliminar
        $stmt = $conexion->prepare("DELETE FROM color WHERE id_color = :id_color");
        if ($stmt->execute([':id_color' => $id_color])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Color eliminado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró el color para eliminar.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar el color.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>