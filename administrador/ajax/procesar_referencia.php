<?php
// Asegurar que la respuesta siempre sea de tipo JSON.
session_start();
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
// Expresión regular para validar el formato de una referencia de moto.
$regex_referencia = '/^[A-Za-z][A-Za-z0-9\s-]{1,19}$/';

switch ($accion) {
    /**
     * --- Caso para Agregar una Nueva Referencia ---
     */
    case 'agregar':
        // Recoge y convierte los datos. intval() asegura que los IDs sean números.
        $id_marcas = intval($_POST['id_marcas'] ?? 0);
        $referencia_marca = trim($_POST['referencia_marca'] ?? '');
        
        // --- Validaciones de Backend ---
        // 1. Verificar que los campos obligatorios no estén vacíos.
        if (empty($referencia_marca) || $id_marcas <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ambos campos son obligatorios.']);
            exit;
        }
        // 2. Verificar que el formato de la referencia sea válido.
        if (!preg_match($regex_referencia, $referencia_marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la referencia no es válido.']);
            exit;
        }
        
        // 3. Verificar si la combinación exacta de marca y referencia ya existe (case-insensitive).
        // Esto previene duplicados como "Yamaha - NMAX" y "Yamaha - nmax".
        $stmt = $conexion->prepare("SELECT id_referencia FROM referencia_marca WHERE LOWER(referencia_marca) = LOWER(:ref) AND id_marcas = :id_marca");
        $stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Esa referencia ya existe para la marca seleccionada.']);
            exit;
        }

        // --- Inserción ---
        $stmt = $conexion->prepare("INSERT INTO referencia_marca (referencia_marca, id_marcas) VALUES (:ref, :id_marca)");
        if ($stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas])) {
            echo json_encode(['status' => 'success', 'message' => 'Referencia agregada correctamente.']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Error al agregar la referencia.']);
        }
        break;

    /**
     * --- Caso para Obtener los datos de una referencia específica ---
     * Se usa para poblar el formulario de edición.
     */
    case 'obtener':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $conexion->prepare("SELECT id_referencia, referencia_marca, id_marcas FROM referencia_marca WHERE id_referencia = :id");
        $stmt->execute([':id' => $id]);
        $referencia = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($referencia);
        break;

    /**
     * --- Caso para Actualizar una Referencia Existente ---
     */
    case 'actualizar':
        $id_referencia = intval($_POST['id_referencia'] ?? 0);
        $id_marcas = intval($_POST['id_marcas'] ?? 0);
        $referencia_marca = trim($_POST['referencia_marca'] ?? '');
        
        // --- Validaciones de Backend ---
        // 1. Verificar que los datos necesarios no sean inválidos.
        if (empty($referencia_marca) || $id_marcas <= 0 || $id_referencia <= 0) {
             echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        // 2. Verificar el formato de la referencia.
        if (!preg_match($regex_referencia, $referencia_marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la referencia no es válido.']);
            exit;
        }
        
        // 3. Verificar duplicados, excluyendo el registro que se está editando.
        $stmt = $conexion->prepare("SELECT id_referencia FROM referencia_marca WHERE LOWER(referencia_marca) = LOWER(:ref) AND id_marcas = :id_marca AND id_referencia != :id_ref");
        $stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas, ':id_ref' => $id_referencia]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Esa referencia ya existe para la marca seleccionada.']);
            exit;
        }
        
        // --- Actualización ---
        $stmt = $conexion->prepare("UPDATE referencia_marca SET referencia_marca = :ref, id_marcas = :id_marca WHERE id_referencia = :id_ref");
        if ($stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas, ':id_ref' => $id_referencia])) {
            echo json_encode(['status' => 'success', 'message' => 'Referencia actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la referencia.']);
        }
        break;

    /**
     * --- Caso para Eliminar una Referencia ---
     */
    case 'eliminar':
        $id_referencia = intval($_POST['id'] ?? 0);
        if ($id_referencia <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // --- Validación de Integridad Referencial ---
        // Antes de borrar, se comprueba si esta referencia está siendo utilizada en la tabla 'motos'.
        // Esto previene dejar "motos huérfanas" sin una referencia asignada.
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM motos WHERE id_referencia_marca = :id_ref");
        $stmt_check->execute([':id_ref' => $id_referencia]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Esta referencia está en uso por una o más motos.']);
            exit;
        }

        // --- Eliminación ---
        $stmt = $conexion->prepare("DELETE FROM referencia_marca WHERE id_referencia = :id_ref");
        if ($stmt->execute([':id_ref' => $id_referencia])) {
            // rowCount() devuelve el número de filas afectadas.
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Referencia eliminada correctamente.']);
            } else {
                 echo json_encode(['status' => 'error', 'message' => 'No se encontró la referencia para eliminar.']);
            }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar la referencia.']);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>