<?php
// ajax/procesar_tipo_trabajo.php (VERSIÓN FINAL CON PDO Y AUDITORÍA)
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../conecct/conex.php';

$db = new Database();
$conexion = $db->conectar(); // $conexion es ahora un objeto PDO

// Establecer la variable de sesión de MySQL para los Triggers
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
    case 'actualizar':
        $id_tipo = intval($_POST['id_tipo'] ?? 0);
        $detalle = trim($_POST['detalle'] ?? '');
        $cc_inicial = intval($_POST['cc_inicial'] ?? 0);
        $cc_final = intval($_POST['cc_final'] ?? 0);
        $precio = floatval($_POST['precio_unitario'] ?? 0);

        // Validaciones de backend
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

        // Validación de solapamiento de rangos
        $sql_check = "SELECT id_tipo FROM tipo_trabajo WHERE LOWER(detalle) = LOWER(:detalle) AND (cc_inicial <= :cc_final AND cc_final >= :cc_inicial)";
        $params_check = [':detalle' => $detalle, ':cc_final' => $cc_final, ':cc_inicial' => $cc_inicial];

        if ($accion === 'actualizar') {
            $sql_check .= " AND id_tipo != :id_tipo";
            $params_check[':id_tipo'] = $id_tipo;
        }
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->execute($params_check);
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe un trabajo con el mismo detalle que se solapa con este rango de CC.']);
            exit;
        }

        if ($accion === 'agregar') {
            $stmt = $conexion->prepare("INSERT INTO tipo_trabajo (detalle, cc_inicial, cc_final, precio_unitario) VALUES (:detalle, :cc_ini, :cc_fin, :precio)");
            $params_exec = [':detalle' => $detalle, ':cc_ini' => $cc_inicial, ':cc_fin' => $cc_final, ':precio' => $precio];
        } else { // actualizar
            $stmt = $conexion->prepare("UPDATE tipo_trabajo SET detalle = :detalle, cc_inicial = :cc_ini, cc_final = :cc_fin, precio_unitario = :precio WHERE id_tipo = :id");
            $params_exec = [':detalle' => $detalle, ':cc_ini' => $cc_inicial, ':cc_fin' => $cc_final, ':precio' => $precio, ':id' => $id_tipo];
        }
        
        if ($stmt->execute($params_exec)) {
            $message = $accion === 'agregar' ? 'Trabajo agregado correctamente.' : 'Trabajo actualizado correctamente.';
            echo json_encode(['status' => 'success', 'message' => $message]);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Error al procesar la solicitud.']);
        }
        break;
    
    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT * FROM tipo_trabajo WHERE id_tipo = :id");
        $stmt->execute([':id' => $id]);
        $trabajo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($trabajo);
        break;

    case 'eliminar':
        $id_tipo = intval($_POST['id'] ?? 0);
        if ($id_tipo <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // Validación de eliminación
        $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM detalle_mantenimientos WHERE id_tipo_trabajo = :id");
        $stmt_check->execute([':id' => $id_tipo]);
        $resultado = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($resultado['total'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Este trabajo está en uso en ' . $resultado['total'] . ' mantenimientos.']);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM tipo_trabajo WHERE id_tipo = :id");
        if ($stmt->execute([':id' => $id_tipo]) && $stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Tipo de trabajo eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar o el registro no fue encontrado.']);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>