<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        $cilindraje = $_POST['cilindraje'] ?? 0;
        if (!is_numeric($cilindraje) || $cilindraje < 50 || $cilindraje > 2000) {
            echo json_encode(['status' => 'error', 'message' => 'El cilindraje debe ser un número válido entre 50 y 2000.']);
            exit;
        }

        // --- ¡NUEVA VALIDACIÓN DE DUPLICADOS AL AGREGAR! ---
        $stmt_check = $conexion->prepare("SELECT id_cc FROM cilindraje WHERE cilindraje = ?");
        $stmt_check->bind_param("i", $cilindraje);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ese cilindraje ya está registrado.']);
            $stmt_check->close();
            $conexion->close();
            exit;
        }
        $stmt_check->close();
        // --- FIN DE LA VALIDACIÓN ---

        $stmt = $conexion->prepare("INSERT INTO cilindraje (cilindraje) VALUES (?)");
        $stmt->bind_param("i", $cilindraje);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cilindraje agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el cilindraje.']);
        }
        $stmt->close();
        break;

    case 'actualizar':
        $id_cc = $_POST['id_cc'] ?? 0;
        $cilindraje = $_POST['cilindraje'] ?? 0;
        if (!is_numeric($cilindraje) || $cilindraje < 50 || $cilindraje > 2000 || !is_numeric($id_cc) || $id_cc <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        
        // --- ¡NUEVA VALIDACIÓN DE DUPLICADOS AL EDITAR! ---
        // Verifica si el nuevo valor de cilindraje ya existe en OTRO registro
        $stmt_check = $conexion->prepare("SELECT id_cc FROM cilindraje WHERE cilindraje = ? AND id_cc != ?");
        $stmt_check->bind_param("ii", $cilindraje, $id_cc);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ese cilindraje ya está en uso por otro registro.']);
            $stmt_check->close();
            $conexion->close();
            exit;
        }
        $stmt_check->close();
        // --- FIN DE LA VALIDACIÓN ---
        
        $stmt = $conexion->prepare("UPDATE cilindraje SET cilindraje = ? WHERE id_cc = ?");
        $stmt->bind_param("ii", $cilindraje, $id_cc);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cilindraje actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el cilindraje.']);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id_cc = $_POST['id'] ?? 0;
        if (!is_numeric($id_cc) || $id_cc <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM cilindraje WHERE id_cc = ?");
        $stmt->bind_param("i", $id_cc);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Cilindraje eliminado correctamente.']);
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