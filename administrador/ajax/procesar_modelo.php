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
// Obtiene el año actual para usarlo en las validaciones de rango.
$current_year = date('Y');

switch ($accion) {
    /**
     * --- Caso para Agregar un Nuevo Modelo (Año) ---
     */
    case 'agregar':
        // Convierte el 'anio' recibido a un entero. Si no se recibe, el valor es 0.
        $anio = intval($_POST['anio'] ?? 0);

        // --- Validaciones de Backend ---
        // 1. Verificar que el año esté dentro de un rango lógico (ej. desde 1980 hasta 2 años en el futuro).
        if ($anio < 1980 || $anio > ($current_year + 2)) {
            echo json_encode(['status' => 'error', 'message' => "El año debe ser un número válido entre 1980 y " . ($current_year + 2) . "."]);
            exit;
        }

        // 2. Verificar si el año ya existe para evitar duplicados.
        $stmt = $conexion->prepare("SELECT id_modelo FROM modelos WHERE anio = :anio");
        $stmt->execute([':anio' => $anio]);
        if ($stmt->fetch()) { // fetch() devuelve 'true' si encuentra una fila.
            echo json_encode(['status' => 'error', 'message' => 'Ese año ya está registrado.']);
            exit;
        }

        // --- Inserción ---
        // Si las validaciones pasan, se inserta el nuevo año.
        $stmt = $conexion->prepare("INSERT INTO modelos (anio) VALUES (:anio)");
        if ($stmt->execute([':anio' => $anio])) {
            echo json_encode(['status' => 'success', 'message' => 'Modelo (año) agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el modelo (año).']);
        }
        break;

    /**
     * --- Caso para Actualizar un Modelo (Año) Existente ---
     */
    case 'actualizar':
        $id_modelo = intval($_POST['id_modelo'] ?? 0);
        $anio = intval($_POST['anio'] ?? 0);

        // --- Validaciones de Backend ---
        // 1. Verificar que el ID sea válido y que el año esté en el rango permitido.
        if ($id_modelo <= 0 || $anio < 1980 || $anio > ($current_year + 2)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos o fuera de rango.']);
            exit;
        }
        
        // 2. Verificar duplicados, excluyendo el registro que se está editando.
        // La condición 'AND id_modelo != :id_modelo' es crucial.
        $stmt = $conexion->prepare("SELECT id_modelo FROM modelos WHERE anio = :anio AND id_modelo != :id_modelo");
        $stmt->execute([':anio' => $anio, ':id_modelo' => $id_modelo]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ese año ya está en uso por otro registro.']);
            exit;
        }
        
        // --- Actualización ---
        $stmt = $conexion->prepare("UPDATE modelos SET anio = :anio WHERE id_modelo = :id_modelo");
        if ($stmt->execute([':anio' => $anio, ':id_modelo' => $id_modelo])) {
            echo json_encode(['status' => 'success', 'message' => 'Modelo (año) actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el modelo (año).']);
        }
        break;

    /**
     * --- Caso para Eliminar un Modelo (Año) ---
     */

    $id_modelo = intval($_POST['id'] ?? 0);
        if ($id_modelo <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // --- Validación de Integridad Referencial ---
        // Antes de borrar, se comprueba si este modelo (año) está siendo utilizado en la tabla 'motos'.
        // Esto previene dejar "motos huérfanas" sin un año de modelo asignado.
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM motos WHERE id_modelo = :id_modelo");
        $stmt_check->execute([':id_modelo' => $id_modelo]);
        // fetchColumn() es una forma eficiente de obtener el resultado de una sola columna (en este caso, el COUNT).
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Este modelo (año) está en uso por una o más motos.']);
            exit;
        }

        // --- Eliminación ---
        $stmt = $conexion->prepare("DELETE FROM modelos WHERE id_modelo = :id_modelo");
        if ($stmt->execute([':id_modelo' => $id_modelo])) {
            // rowCount() devuelve el número de filas afectadas. Si es > 0, la eliminación fue exitosa.
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Modelo (año) eliminado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró el modelo para eliminar.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar el modelo (año).']);
        }
        break;

    default:
        // Si el parámetro 'accion' no coincide con ninguno de los casos anteriores.
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>