<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
    exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// --- ¡CORRECCIÓN CLAVE! Definimos la expresión regular aquí, una sola vez. ---
$regex_referencia = '/^[A-Za-z][A-Za-z0-9\s-]{1,19}$/';

switch ($accion) {
    case 'agregar':
        // Recogemos los datos una sola vez
        $id_marcas = $_POST['id_marcas'] ?? 0;
        $referencia_marca = trim($_POST['referencia_marca'] ?? '');
        
        // 1. Validar campos vacíos
        if (empty($referencia_marca) || $id_marcas <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ambos campos son obligatorios.']);
            exit;
        }

        // 2. Validar el formato de la referencia
        if (!preg_match($regex_referencia, $referencia_marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la referencia no es válido.']);
            exit;
        }
        
        // 3. Validar si ya existe la combinación marca-referencia
        $stmt_check = $conexion->prepare("SELECT id_referencia FROM referencia_marca WHERE LOWER(referencia_marca) = LOWER(?) AND id_marcas = ?");
        $stmt_check->bind_param("si", $referencia_marca, $id_marcas);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Esa referencia ya existe para la marca seleccionada.']);
            exit;
        }
        $stmt_check->close();

        // 4. Insertar los datos
        $stmt = $conexion->prepare("INSERT INTO referencia_marca (referencia_marca, id_marcas) VALUES (?, ?)");
        $stmt->bind_param("si", $referencia_marca, $id_marcas);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Referencia agregada correctamente.']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Error al agregar la referencia.']);
        }
        $stmt->close();
        break;

    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT id_referencia, referencia_marca, id_marcas FROM referencia_marca WHERE id_referencia = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $referencia = $stmt->get_result()->fetch_assoc();
        echo json_encode($referencia);
        $stmt->close();
        break;

    case 'actualizar':
        // Recogemos los datos una sola vez
        $id_referencia = $_POST['id_referencia'] ?? 0;
        $id_marcas = $_POST['id_marcas'] ?? 0;
        $referencia_marca = trim($_POST['referencia_marca'] ?? '');
        
        // 1. Validar datos básicos
        if (empty($referencia_marca) || $id_marcas <= 0 || $id_referencia <= 0) {
             echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }

        // 2. Validar el formato de la referencia
        if (!preg_match($regex_referencia, $referencia_marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la referencia no es válido.']);
            exit;
        }
        
        // 3. Validar si ya existe la combinación (excluyendo el registro actual)
        $stmt_check = $conexion->prepare("SELECT id_referencia FROM referencia_marca WHERE LOWER(referencia_marca) = LOWER(?) AND id_marcas = ? AND id_referencia != ?");
        $stmt_check->bind_param("sii", $referencia_marca, $id_marcas, $id_referencia);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Esa referencia ya existe para la marca seleccionada.']);
            exit;
        }
        $stmt_check->close();
        
        // 4. Actualizar los datos
        $stmt = $conexion->prepare("UPDATE referencia_marca SET referencia_marca = ?, id_marcas = ? WHERE id_referencia = ?");
        $stmt->bind_param("sii", $referencia_marca, $id_marcas, $id_referencia);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Referencia actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la referencia.']);
        }
        $stmt->close();
        break;

    case 'eliminar':
        $id = $_POST['id'] ?? 0;
        $stmt = $conexion->prepare("DELETE FROM referencia_marca WHERE id_referencia = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Referencia eliminada correctamente.']);
        }
        $stmt->close();
        break;
}

$conexion->close();
?>