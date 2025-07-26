<?php
// Iniciar sesión para la auditoría.
session_start();
// Definir la respuesta como JSON.
header('Content-Type: application/json');

// Incluir la conexión a la base de datos (PDO).
require_once __DIR__ . '/../../conecct/conex.php';

// Instanciar la conexión.
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
// Expresión regular para el formato de placa de moto en Colombia (3 letras, 2 números, 1 letra).
$regex_placa = '/^[A-Z]{3}\d{2}[A-Z]$/';

switch ($accion) {
    /**
     * --- Caso para Agregar una Nueva Moto ---
     */
    case 'agregar':
        // Recoger y limpiar la placa, convirtiéndola siempre a mayúsculas.
        $placa = strtoupper(trim($_POST['id_placa'] ?? ''));
        // Recoger todas las llaves foráneas.
        $doc_cli = $_POST['id_documento_cli'] ?? 0;
        $id_cilindraje = $_POST['id_cilindraje'] ?? 0;
        $id_referencia = $_POST['id_referencia_marca'] ?? 0;
        $id_modelo = $_POST['id_modelo'] ?? 0;
        $id_color = $_POST['id_color'] ?? 0;

        // --- Validaciones de Backend ---
        // 1. Validar el formato de la placa.
        if (!preg_match($regex_placa, $placa)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la placa es inválido (ej: ABC12D).']);
            exit;
        }
        // 2. Validar que todos los campos obligatorios (las llaves foráneas) hayan sido seleccionados.
        if (empty($doc_cli) || empty($id_cilindraje) || empty($id_referencia) || empty($id_modelo) || empty($id_color)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // 3. Verificar si la placa ya existe para evitar duplicados. La placa es la clave primaria.
        $stmt = $conexion->prepare("SELECT id_placa FROM motos WHERE id_placa = :placa");
        $stmt->execute([':placa' => $placa]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'La placa ya está registrada.']);
            exit;
        }

        // --- Inserción ---
        // Si todas las validaciones pasan, se inserta la nueva moto.
        // Se incluye NOW() para llenar automáticamente la columna 'fecha_registro' si existe.
        $stmt = $conexion->prepare("INSERT INTO motos (id_placa, id_documento_cli, id_cilindraje, id_referencia_marca, id_modelo, id_color, fecha_registro) VALUES (:placa, :doc_cli, :cil, :ref, :mod, :col, NOW())");
        if ($stmt->execute([':placa' => $placa, ':doc_cli' => $doc_cli, ':cil' => $id_cilindraje, ':ref' => $id_referencia, ':mod' => $id_modelo, ':col' => $id_color])) {
            echo json_encode(['status' => 'success', 'message' => 'Moto registrada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al registrar la moto.']);
        }
        break;

    /**
     * --- Caso para Obtener los datos de una moto específica ---
     * Se usa para poblar el formulario de edición.
     */
    case 'obtener':
        $id = $_GET['id'] ?? '';
        $stmt = $conexion->prepare("SELECT * FROM motos WHERE id_placa = :id");
        $stmt->execute([':id' => $id]);
        $moto = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($moto);
        break;

    /**
     * --- Caso para Actualizar los datos de una moto ---
     */
    case 'actualizar':
        // La 'placa_original' se envía desde un campo oculto para identificar el registro a actualizar.
        $placa_original = $_POST['placa_original'] ?? '';
        $doc_cli = $_POST['id_documento_cli'] ?? 0;
        $id_cilindraje = $_POST['id_cilindraje'] ?? 0;
        $id_referencia = $_POST['id_referencia_marca'] ?? 0;
        $id_modelo = $_POST['id_modelo'] ?? 0;
        $id_color = $_POST['id_color'] ?? 0;

        // Validar que no haya campos vacíos. La placa no se valida porque no se puede editar.
        if (empty($placa_original) || empty($doc_cli) || empty($id_cilindraje) || empty($id_referencia) || empty($id_modelo) || empty($id_color)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // --- Actualización ---
        $stmt = $conexion->prepare("UPDATE motos SET id_documento_cli = :doc_cli, id_cilindraje = :cil, id_referencia_marca = :ref, id_modelo = :mod, id_color = :col WHERE id_placa = :placa_og");
        if ($stmt->execute([':doc_cli' => $doc_cli, ':cil' => $id_cilindraje, ':ref' => $id_referencia, ':mod' => $id_modelo, ':col' => $id_color, ':placa_og' => $placa_original])) {
            echo json_encode(['status' => 'success', 'message' => 'Moto actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la moto.']);
        }
        break;

    /**
     * --- Caso para Eliminar una Moto ---
     */
    case 'eliminar':
        $placa = $_POST['id_placa'] ?? '';
        if (empty($placa)) {
            echo json_encode(['status' => 'error', 'message' => 'No se proporcionó la placa de la moto.']);
            exit;
        }

        // --- Validación de Integridad Referencial ---
        // Antes de borrar, se comprueba si esta moto está siendo utilizada en la tabla 'mantenimientos'.
        // Esto evita dejar "mantenimientos huérfanos" y mantiene la consistencia de los datos.
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM mantenimientos WHERE id_placa = :placa");
        $stmt->execute([':placa' => $placa]);
        $total_mantenimientos = $stmt->fetchColumn();

        if ($total_mantenimientos > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. La moto tiene ' . $total_mantenimientos . ' mantenimientos registrados.']);
            exit;
        }
        
        // --- Eliminación ---
        $stmt = $conexion->prepare("DELETE FROM motos WHERE id_placa = :placa");
        if ($stmt->execute([':placa' => $placa])) {
            // rowCount() devuelve el número de filas afectadas.
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