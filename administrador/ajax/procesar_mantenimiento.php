<?php
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

switch ($accion) {
    case 'agregar':
    case 'actualizar':
        $conexion->beginTransaction();
        try {
            // Validar datos principales
            if (empty($_POST['id_placa']) || empty($_POST['fecha_realizo']) || !isset($_POST['kilometraje'])) {
                throw new Exception('Faltan datos generales (placa, fecha, kilometraje).');
            }
            $detalles = json_decode($_POST['detalles'] ?? '[]', true);
            if (empty($detalles)) {
                throw new Exception('Debe agregar al menos un trabajo realizado.');
            }

            if ($accion === 'agregar') {
                $stmt = $conexion->prepare("INSERT INTO mantenimientos (fecha_realizo, kilometraje, total, observaciones_entrada, observaciones_salida, id_placa) VALUES (:fecha, :km, :total, :obs_in, :obs_out, :placa)");
                $stmt->execute([
                    ':fecha' => $_POST['fecha_realizo'],
                    ':km' => $_POST['kilometraje'],
                    ':total' => $_POST['total'],
                    ':obs_in' => $_POST['observaciones_entrada'],
                    ':obs_out' => $_POST['observaciones_salida'],
                    ':placa' => $_POST['id_placa']
                ]);
                $id_mantenimiento = $conexion->lastInsertId();
            } else { // actualizar
                $id_mantenimiento = intval($_POST['id_mantenimientos']);
                $stmt = $conexion->prepare("UPDATE mantenimientos SET fecha_realizo=:fecha, kilometraje=:km, total=:total, observaciones_entrada=:obs_in, observaciones_salida=:obs_out, id_placa=:placa WHERE id_mantenimientos=:id");
                $stmt->execute([
                    ':fecha' => $_POST['fecha_realizo'],
                    ':km' => $_POST['kilometraje'],
                    ':total' => $_POST['total'],
                    ':obs_in' => $_POST['observaciones_entrada'],
                    ':obs_out' => $_POST['observaciones_salida'],
                    ':placa' => $_POST['id_placa'],
                    ':id' => $id_mantenimiento
                ]);
                
                // Borrar detalles antiguos para reemplazarlos
                $stmt_delete = $conexion->prepare("DELETE FROM detalle_mantenimientos WHERE id_mantenimiento = :id");
                $stmt_delete->execute([':id' => $id_mantenimiento]);
            }

            if ($id_mantenimiento == 0) throw new Exception('No se pudo procesar el registro de mantenimiento.');

            // Insertar los nuevos detalles
            $stmt_detail = $conexion->prepare("INSERT INTO detalle_mantenimientos (id_tipo_trabajo, cantidad, subtotal, id_mantenimiento) VALUES (:id_tipo, :cant, :subtotal, :id_mant)");
            foreach ($detalles as $detalle) {
                $stmt_detail->execute([
                    ':id_tipo' => $detalle['id_tipo'],
                    ':cant' => $detalle['cantidad'],
                    ':subtotal' => $detalle['subtotal'],
                    ':id_mant' => $id_mantenimiento
                ]);
            }

            $conexion->commit();
            $message = $accion === 'agregar' ? 'registrado' : 'actualizado';
            echo json_encode(['status' => 'success', 'message' => "Mantenimiento $message con éxito."]);

        } catch (Exception $e) { 
            $conexion->rollBack(); 
            echo json_encode(['status' => 'error', 'message' => 'Error al procesar: ' . $e->getMessage()]); 
        }
        break;

    case 'obtener_mantenimiento':
        $id = $_GET['id'] ?? 0;
        $stmt_main = $conexion->prepare("SELECT * FROM mantenimientos WHERE id_mantenimientos = :id");
        $stmt_main->execute([':id' => $id]);
        $mantenimiento = $stmt_main->fetch(PDO::FETCH_ASSOC);
        
        $stmt_details = $conexion->prepare("SELECT dm.cantidad, dm.id_tipo_trabajo as id_tipo, tt.detalle, tt.precio_unitario FROM detalle_mantenimientos dm JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo WHERE dm.id_mantenimiento = :id");
        $stmt_details->execute([':id' => $id]);
        $detalles = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['main' => $mantenimiento, 'details' => $detalles]);
        break;

    case 'obtener_detalles':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT dm.cantidad, dm.subtotal, tt.detalle FROM detalle_mantenimientos dm JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo WHERE dm.id_mantenimiento = :id");
        $stmt->execute([':id' => $id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($detalles);
        break;

    case 'buscar_trabajos':
        $term = $_GET['term'] ?? '';
        $stmt = $conexion->prepare("SELECT id_tipo, detalle, precio_unitario FROM tipo_trabajo WHERE detalle LIKE :term LIMIT 10");
        $stmt->execute([':term' => "%" . $term . "%"]);
        $trabajos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($trabajos);
        break;
    
    case 'eliminar':
        $conexion->beginTransaction();
        try {
            $id = $_POST['id'] ?? 0;
            if ($id <= 0) throw new Exception('ID no válido.');

            $stmt1 = $conexion->prepare("DELETE FROM detalle_mantenimientos WHERE id_mantenimiento = :id");
            $stmt1->execute([':id' => $id]);

            $stmt2 = $conexion->prepare("DELETE FROM mantenimientos WHERE id_mantenimientos = :id");
            $stmt2->execute([':id' => $id]);

            if ($stmt2->rowCount() > 0) {
                $conexion->commit();
                echo json_encode(['status' => 'success', 'message' => 'Mantenimiento y sus detalles eliminados.']);
            } else {
                throw new Exception('No se encontró el mantenimiento para eliminar.');
            }
        } catch (Exception $e) {
            $conexion->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>