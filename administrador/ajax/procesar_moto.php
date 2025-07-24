<?php
// ajax/procesar_moto.php (VERSIÓN FINAL CON PDO Y AUDITORÍA)
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../conecct/conex.php';

$db = new Database();
$conexion = $db->conectar(); // $conexion es un objeto PDO

// Establecer la variable de sesión de MySQL para los Triggers
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$regex_placa = '/^[A-Z]{3}\d{2}[A-Z]$/';

switch ($accion) {
    case 'agregar':
        $placa = strtoupper(trim($_POST['id_placa'] ?? ''));
        $doc_cli = $_POST['id_documento_cli'] ?? 0;
        $id_cilindraje = $_POST['id_cilindraje'] ?? 0;
        $id_referencia = $_POST['id_referencia_marca'] ?? 0;
        $id_modelo = $_POST['id_modelo'] ?? 0;
        $id_color = $_POST['id_color'] ?? 0;

        // Validaciones
        if (!preg_match($regex_placa, $placa)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la placa es inválido (ej: ABC12D).']);
            exit;
        }
        if (empty($doc_cli) || empty($id_cilindraje) || empty($id_referencia) || empty($id_modelo) || empty($id_color)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Verificar si la placa ya existe
        $stmt = $conexion->prepare("SELECT id_placa FROM motos WHERE id_placa = :placa");
        $stmt->execute([':placa' => $placa]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'La placa ya está registrada.']);
            exit;
        }

        // Insertar
        $stmt = $conexion->prepare("INSERT INTO motos (id_placa, id_documento_cli, id_cilindraje, id_referencia_marca, id_modelo, id_color, fecha_registro) VALUES (:placa, :doc_cli, :cil, :ref, :mod, :col, NOW())");
        if ($stmt->execute([':placa' => $placa, ':doc_cli' => $doc_cli, ':cil' => $id_cilindraje, ':ref' => $id_referencia, ':mod' => $id_modelo, ':col' => $id_color])) {
            echo json_encode(['status' => 'success', 'message' => 'Moto registrada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar la moto.']);
        }
        break;

    case 'obtener':
        $id = $_GET['id'] ?? '';
        $stmt = $conexion->prepare("SELECT * FROM motos WHERE id_placa = :id");
        $stmt->execute([':id' => $id]);
        $moto = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($moto);
        break;

    case 'actualizar':
        $placa_original = $_POST['placa_original'] ?? '';
        $doc_cli = $_POST['id_documento_cli'] ?? 0;
        $id_cilindraje = $_POST['id_cilindraje'] ?? 0;
        $id_referencia = $_POST['id_referencia_marca'] ?? 0;
        $id_modelo = $_POST['id_modelo'] ?? 0;
        $id_color = $_POST['id_color'] ?? 0;

        if (empty($placa_original) || empty($doc_cli) || empty($id_cilindraje) || empty($id_referencia) || empty($id_modelo) || empty($id_color)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        $stmt = $conexion->prepare("UPDATE motos SET id_documento_cli = :doc_cli, id_cilindraje = :cil, id_referencia_marca = :ref, id_modelo = :mod, id_color = :col WHERE id_placa = :placa_og");
        if ($stmt->execute([':doc_cli' => $doc_cli, ':cil' => $id_cilindraje, ':ref' => $id_referencia, ':mod' => $id_modelo, ':col' => $id_color, ':placa_og' => $placa_original])) {
            echo json_encode(['status' => 'success', 'message' => 'Moto actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la moto.']);
        }
        break;

    case 'eliminar':
        $placa = $_POST['id_placa'] ?? '';
        if (empty($placa)) {
            echo json_encode(['status' => 'error', 'message' => 'No se proporcionó la placa de la moto.']);
            exit;
        }

        // Verificar dependencias
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM mantenimientos WHERE id_placa = :placa");
        $stmt->execute([':placa' => $placa]);
        $total_mantenimientos = $stmt->fetchColumn();

        if ($total_mantenimientos > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. La moto tiene ' . $total_mantenimientos . ' mantenimientos registrados.']);
            exit;
        }
        
        // Eliminar
        $stmt = $conexion->prepare("DELETE FROM motos WHERE id_placa = :placa");
        if ($stmt->execute([':placa' => $placa])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Moto eliminada correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró la moto para eliminar.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar la moto.']);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>