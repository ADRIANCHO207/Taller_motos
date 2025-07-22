<?php
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');

if ($conexion->connect_error) { /* ... */ }

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
    case 'actualizar':
        $id_tipo = intval($_POST['id_tipo'] ?? 0);
        $detalle = trim($_POST['detalle'] ?? '');
        $cc_inicial = intval($_POST['cc_inicial'] ?? 0);
        $cc_final = intval($_POST['cc_final'] ?? 0);
        $precio = floatval($_POST['precio_unitario'] ?? 0);

        // --- VALIDACIONES DE BACKEND MEJORADAS ---
        if (empty($detalle) || $cc_inicial < 1 || $cc_final < 1 || $precio < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios y deben ser válidos.']);
            exit;
        }
        if ($cc_inicial > 2000 || $cc_final > 2000) {
            echo json_encode(['status' => 'error', 'message' => 'El cilindraje no puede ser mayor a 2000.']);
            exit;
        }
        if ($cc_inicial > $cc_final) {
            echo json_encode(['status' => 'error', 'message' => 'El CC Inicial no puede ser mayor que el CC Final.']);
            exit;
        }

        // --- ¡NUEVA VALIDACIÓN DE SOLAPAMIENTO DE RANGOS! ---
        // Busca si ya existe un trabajo con el mismo detalle cuyo rango de CC se cruce con el nuevo.
        $sql_check = "SELECT id_tipo FROM tipo_trabajo WHERE LOWER(detalle) = LOWER(?) AND (cc_inicial <= ? AND cc_final >= ?)";
        $params_check = [$detalle, $cc_final, $cc_inicial];
        $types_check = "sii";

        if ($accion === 'actualizar') {
            $sql_check .= " AND id_tipo != ?";
            $params_check[] = $id_tipo;
            $types_check .= "i";
        }
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param($types_check, ...$params_check);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe un trabajo con el mismo detalle que se solapa con este rango de CC.']);
            exit;
        }
        $stmt_check->close();
        // --- FIN DE LA VALIDACIÓN DE SOLAPAMIENTO ---

        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("INSERT INTO tipo_trabajo (detalle, cc_inicial, cc_final, precio_unitario) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siid", $detalle, $cc_inicial, $cc_final, $precio);
        } else { // actualizar
            $stmt = $conexion->prepare("UPDATE tipo_trabajo SET detalle = ?, cc_inicial = ?, cc_final = ?, precio_unitario = ? WHERE id_tipo = ?");
            $stmt->bind_param("siidi", $detalle, $cc_inicial, $cc_final, $precio, $id_tipo);
        }
        
        if ($stmt->execute()) {
            $message = $accion === 'agregar' ? 'Trabajo agregado correctamente.' : 'Trabajo actualizado correctamente.';
            echo json_encode(['status' => 'success', 'message' => $message]);
        }
        $stmt->close();
        break;
    
    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT * FROM tipo_trabajo WHERE id_tipo = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
        break;

    case 'eliminar':
        $id_tipo = intval($_POST['id'] ?? 0);
        if ($id_tipo <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // --- VALIDACIÓN DE ELIMINACIÓN COMPLETADA ---
        // Verifica si el tipo de trabajo está en uso en la tabla 'detalle_mantenimiento'
        $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM detalle_mantenimientos WHERE id_tipo_trabajo = ?");
        $stmt_check->bind_param("i", $id_tipo);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if ($resultado['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Este trabajo está en uso en ' . $resultado['total'] . ' mantenimientos registrados.']);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM tipo_trabajo WHERE id_tipo = ?");
        $stmt->bind_param("i", $id_tipo);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Tipo de trabajo eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar o el registro no fue encontrado.']);
        }
        $stmt->close();
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
    }
$conexion->close();
?>