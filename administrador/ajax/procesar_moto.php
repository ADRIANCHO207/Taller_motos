<?php
// ajax/procesar_moto.php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        // Aquí iría tu lógica de validación de campos vacíos y formato de placa
        $placa = strtoupper($_POST['id_placa']); // Convertir a mayúsculas
        $doc_cli = $_POST['id_documento_cli'];
        // ... recoger los demás IDs
        
        // Verificar si la placa ya existe
        $stmt_check = $conexion->prepare("SELECT id_placa FROM motos WHERE id_placa = ?");
        $stmt_check->bind_param("s", $placa);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'La placa ya está registrada.']);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO motos (id_placa, id_documento_cli, id_cilindraje, id_referencia_marca, id_modelo, id_color) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiii", $placa, $doc_cli, $_POST['id_cilindraje'], $_POST['id_referencia_marca'], $_POST['id_modelo'], $_POST['id_color']);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Moto registrada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar la moto: ' . $stmt->error]);
        }
        break;

    case 'obtener':
        $id = $_GET['id'] ?? '';
        $stmt = $conexion->prepare("SELECT * FROM motos WHERE id_placa = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $moto = $stmt->get_result()->fetch_assoc();
        echo json_encode($moto);
        break;

    case 'actualizar':
        // La placa no se actualiza, pero los demás campos sí
        $placa_original = $_POST['placa_original'];
        $doc_cli = $_POST['id_documento_cli'];
        // ... recoger los demás IDs
        
        $stmt = $conexion->prepare("UPDATE motos SET id_documento_cli = ?, id_cilindraje = ?, id_referencia_marca = ?, id_modelo = ?, id_color = ? WHERE id_placa = ?");
        $stmt->bind_param("iiiiis", $doc_cli, $_POST['id_cilindraje'], $_POST['id_referencia_marca'], $_POST['id_modelo'], $_POST['id_color'], $placa_original);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Moto actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la moto: ' . $stmt->error]);
        }
        break;

    case 'eliminar':
        $placa = $_POST['id_placa'] ?? '';

        if (empty($placa)) {
            echo json_encode(['status' => 'error', 'message' => 'No se proporcionó la placa de la moto.']);
            exit;
        }

        // --- VERIFICACIÓN DE MANTENIMIENTOS ---
        $stmt_check = $conexion->prepare("SELECT COUNT(*) AS total FROM mantenimientos WHERE id_placa = ?");
        $stmt_check->bind_param("s", $placa);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        // 2. Si tiene uno o más mantenimientos, rechazamos la eliminación.
        if ($resultado['total'] > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se puede eliminar la moto porque tiene ' . $resultado['total'] . ' mantenimientos registrados. Primero debe eliminar sus mantenimientos.'
            ]);
            exit;
        }
        
        // --- SI PASA LA VERIFICACIÓN, PROCEDEMOS A ELIMINAR ---
        // 3. Si no tiene mantenimientos, procedemos a eliminar la moto.
        $stmt_delete = $conexion->prepare("DELETE FROM motos WHERE id_placa = ?");
        $stmt_delete->bind_param("s", $placa);

        if ($stmt_delete->execute()) {
            // Verificamos si realmente se eliminó una fila
            if ($stmt_delete->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Moto eliminada correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró la moto con la placa proporcionada.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar la moto: ' . $stmt_delete->error]);
        }
        $stmt_delete->close();
        break;
}
$conexion->close();
?>