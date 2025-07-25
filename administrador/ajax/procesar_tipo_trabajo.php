<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../conecct/conex.php';

$db = new Database();
$conexion = $db->conectar();

/**
 * --- Auditoría con Triggers ---
 * Establece una variable de sesión en MySQL (@current_admin_id) para que los Triggers
 * puedan registrar qué administrador está realizando cada acción.
 */
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);


// Determina la operación a realizar.
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    /**
     * --- Caso para Agregar y Actualizar Tipos de Trabajo ---
     * Se agrupan porque la validación y la estructura de datos son casi idénticas.
     */
    case 'agregar':
    case 'actualizar':
        // Recoger y limpiar los datos del formulario.
        $id_tipo = intval($_POST['id_tipo'] ?? 0);
        $detalle = trim($_POST['detalle'] ?? '');
        $cc_inicial = intval($_POST['cc_inicial'] ?? 0);
        $cc_final = intval($_POST['cc_final'] ?? 0);
        $precio = floatval($_POST['precio_unitario'] ?? 0);

        // --- Validaciones de Backend ---
        // 1. Validar que los campos obligatorios no estén vacíos o con valores inválidos.
        if (empty($detalle) || $cc_inicial < 1 || $cc_final < 1 || $precio < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios y deben ser válidos.']);
            exit;
        }
        // 2. Validar que los cilindrajes no superen un límite razonable.
        if ($cc_inicial > 2000 || $cc_final > 2000) {
            echo json_encode(['status' => 'error', 'message' => 'El cilindraje no puede ser mayor a 2000.']);
            exit;
        }
        // 3. Validar que el rango de cilindraje sea lógico.
        if ($cc_inicial > $cc_final) {
            echo json_encode(['status' => 'error', 'message' => 'El CC Inicial no puede ser mayor que el CC Final.']);
            exit;
        }

        /**
         * 4. --- Validación Avanzada de Solapamiento de Rangos ---
         * Esta es la validación más compleja. Evita que se registren trabajos con el mismo nombre
         * y rangos de CC que se crucen. Por ejemplo, si ya existe "Cambio de Aceite" para 100-200cc,
         * no se puede registrar otro "Cambio de Aceite" para 150-250cc porque se solapan.
         * La lógica es: buscar si existe un registro con el mismo 'detalle' donde
         * [rango_existente_inicio, rango_existente_fin] se cruce con [nuevo_inicio, nuevo_fin].
         * Esto se logra con la condición: (inicio_existente <= nuevo_fin) AND (fin_existente >= nuevo_inicio).
         */
        $sql_check = "SELECT id_tipo FROM tipo_trabajo WHERE LOWER(detalle) = LOWER(:detalle) AND (cc_inicial <= :cc_final AND cc_final >= :cc_inicial)";
        $params_check = [':detalle' => $detalle, ':cc_final' => $cc_final, ':cc_inicial' => $cc_inicial];

        // Si estamos actualizando, debemos excluir el propio registro de la verificación.
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

        // --- Inserción o Actualización ---
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
    
    /**
     * --- Caso para Obtener los datos de un tipo de trabajo específico ---
     * Se usa para poblar el formulario de edición.
     */
    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT * FROM tipo_trabajo WHERE id_tipo = :id");
        $stmt->execute([':id' => $id]);
        $trabajo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($trabajo);
        break;

    /**
     * --- Caso para Eliminar un Tipo de Trabajo ---
     */
    case 'eliminar':
        $id_tipo = intval($_POST['id'] ?? 0);
        if ($id_tipo <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // --- Validación de Integridad Referencial ---
        // Antes de borrar, se comprueba si este tipo de trabajo está siendo utilizado en 'detalle_mantenimientos'.
        // Esto previene inconsistencias en los registros históricos de mantenimientos.
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