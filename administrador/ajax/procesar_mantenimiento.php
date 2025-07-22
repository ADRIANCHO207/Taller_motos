<?php
// --- ajax/procesar_mantenimiento.php ---
header('Content-Type: application/json');
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { /* ... */ }

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
    case 'actualizar':
        $conexion->begin_transaction();
        try {
            if (empty($_POST['id_placa']) || empty($_POST['fecha_realizo']) || empty($_POST['kilometraje'])) {
                throw new Exception('Faltan datos generales.');
            }
            $detalles = json_decode($_POST['detalles'] ?? '[]', true);
            if (empty($detalles)) {
                throw new Exception('Debe agregar al menos un trabajo realizado.');
            }

            if ($accion === 'agregar') {
                $stmt = $conexion->prepare("INSERT INTO mantenimientos (fecha_realizo, kilometraje, total, observaciones_entrada, observaciones_salida, id_placa) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdsss", $_POST['fecha_realizo'], $_POST['kilometraje'], $_POST['total'], $_POST['observaciones_entrada'], $_POST['observaciones_salida'], $_POST['id_placa']);
                $stmt->execute();
                $id_mantenimiento = $conexion->insert_id;
            } else { // actualizar
                $id_mantenimiento = intval($_POST['id_mantenimientos']);
                $stmt = $conexion->prepare("UPDATE mantenimientos SET fecha_realizo=?, kilometraje=?, total=?, observaciones_entrada=?, observaciones_salida=?, id_placa=? WHERE id_mantenimientos=?");
                $stmt->bind_param("ssdsssi", $_POST['fecha_realizo'], $_POST['kilometraje'], $_POST['total'], $_POST['observaciones_entrada'], $_POST['observaciones_salida'], $_POST['id_placa'], $id_mantenimiento);
                $stmt->execute();
                
                $stmt_delete = $conexion->prepare("DELETE FROM detalle_mantenimientos WHERE id_mantenimiento = ?");
                $stmt_delete->bind_param("i", $id_mantenimiento);
                $stmt_delete->execute();
            }

            if ($id_mantenimiento == 0) throw new Exception('No se pudo procesar el registro de mantenimiento.');

            $stmt_detail = $conexion->prepare("INSERT INTO detalle_mantenimientos (id_tipo_trabajo, cantidad, subtotal, id_mantenimiento) VALUES (?, ?, ?, ?)");
            foreach ($detalles as $detalle) {
                $stmt_detail->bind_param("iidi", $detalle['id_tipo'], $detalle['cantidad'], $detalle['subtotal'], $id_mantenimiento);
                $stmt_detail->execute();
            }

            $conexion->commit();
            $message = $accion === 'agregar' ? 'registrado' : 'actualizado';
            echo json_encode(['status' => 'success', 'message' => "Mantenimiento $message con éxito."]);

        } catch (Exception $e) { $conexion->rollback(); echo json_encode(['status' => 'error', 'message' => 'Error al procesar: ' . $e->getMessage()]); }
        break;

    case 'obtener_mantenimiento':
        $id = $_GET['id'] ?? 0;
        $stmt_main = $conexion->prepare("SELECT * FROM mantenimientos WHERE id_mantenimientos = ?");
        $stmt_main->bind_param("i", $id);
        $stmt_main->execute();
        $mantenimiento = $stmt_main->get_result()->fetch_assoc();
        
        $stmt_details = $conexion->prepare("SELECT dm.cantidad, dm.id_tipo_trabajo as id_tipo, tt.detalle, tt.precio_unitario FROM detalle_mantenimientos dm JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo WHERE dm.id_mantenimiento = ?");
        $stmt_details->bind_param("i", $id);
        $stmt_details->execute();
        $detalles = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['main' => $mantenimiento, 'details' => $detalles]);
        break;

    case 'obtener_detalles':
        $id = $_GET['id'] ?? 0;
        $sql = "SELECT dm.cantidad, dm.subtotal, tt.detalle 
                FROM detalle_mantenimientos dm 
                JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo 
                WHERE dm.id_mantenimiento = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($detalles);
        break;

    case 'buscar_trabajos':
        $term = $_GET['term'] ?? '';
        $sql = "SELECT id_tipo, detalle, precio_unitario FROM tipo_trabajo WHERE detalle LIKE ? LIMIT 10";
        $stmt = $conexion->prepare($sql);
        $like_term = "%" . $term . "%";
        $stmt->bind_param("s", $like_term);
        $stmt->execute();
        $trabajos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($trabajos);
        break;
    
    case 'eliminar':
        $conexion->begin_transaction();
        try {
            $id = $_POST['id'] ?? 0;
            if ($id <= 0) throw new Exception('ID no válido.');

            $stmt1 = $conexion->prepare("DELETE FROM detalle_mantenimientos WHERE id_mantenimiento = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();

            $stmt2 = $conexion->prepare("DELETE FROM mantenimientos WHERE id_mantenimientos = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();

            if ($stmt2->affected_rows > 0) {
                $conexion->commit();
                echo json_encode(['status' => 'success', 'message' => 'Mantenimiento y sus detalles eliminados.']);
            } else {
                throw new Exception('No se encontró el mantenimiento para eliminar.');
            }
        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
        break;
}
$conexion->close();
?>