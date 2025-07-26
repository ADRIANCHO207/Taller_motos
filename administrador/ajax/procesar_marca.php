<?php
// Iniciar la sesión para acceder a las variables del administrador logueado.
session_start();
// Asegurar que la respuesta siempre sea de tipo JSON.
header('Content-Type: application/json');

// Incluir la configuración central y la conexión a la base de datos (PDO).
require_once __DIR__ . '/../../conecct/conex.php';

// Instanciar la conexión.
$db = new Database();
$conexion = $db->conectar(); 

/**
 * --- Auditoría con Triggers ---
 * Establece una variable de sesión en MySQL (@current_admin_id).
 * Esta variable será utilizada por los Triggers de la base de datos para registrar
 * qué administrador está realizando cada acción (INSERT, UPDATE, DELETE).
 */
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);


// Determina la operación a realizar.
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
// Variable para el nombre de la columna, facilita el mantenimiento si cambia en el futuro.
$nombre_columna = 'marcas';
// Expresión regular para validar que solo se ingresen letras y espacios.
$regex_letras = '/^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/';

switch ($accion) {
    /**
         * --- Caso para Agregar una Nueva Marca ---
         */
    case 'agregar':
        // Recoge el nombre de la marca y elimina espacios en blanco al inicio y al final.
        $marca = trim($_POST['marca'] ?? '');

         // --- Validaciones de Backend ---
        // 1. Verificar que el campo no esté vacío.
        if (empty($marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca no puede estar vacío.']);
            exit;
        }
        // 2. Verificar que el formato sea correcto (solo letras y espacios).
        if (!preg_match($regex_letras, $marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca solo puede contener letras y espacios.']);
            exit;
        }

        // 3. Verificar si la marca ya existe para evitar duplicados.
        // Se usa LOWER() en ambos lados para una comparación "case-insensitive" (ignora mayúsculas/minúsculas).
        // Evita registrar 'Honda' y 'honda' como dos marcas distintas.
        $stmt = $conexion->prepare("SELECT id_marca FROM marcas WHERE LOWER($nombre_columna) = LOWER(:marca)");
        $stmt->execute([':marca' => $marca]);
        if ($stmt->fetch()) { // fetch() devuelve 'true' si encuentra una fila.
            echo json_encode(['status' => 'error', 'message' => 'Esa marca ya está registrada.']);
            exit;
        }

        // --- Inserción ---
        // Si todas las validaciones pasan, se procede a insertar el nuevo registro.
        $stmt = $conexion->prepare("INSERT INTO marcas ($nombre_columna) VALUES (:marca)");
        if ($stmt->execute([':marca' => $marca])) {
            echo json_encode(['status' => 'success', 'message' => 'Marca agregada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar la marca.']);
        }
        break;


    /**
     * --- Caso para Actualizar una Marca Existente ---
     */
    case 'actualizar':
        $id_marca = intval($_POST['id_marca'] ?? 0);
        $marca = trim($_POST['marca'] ?? '');

        // --- Validaciones de Backend ---
        // 1. Verificar que los datos necesarios (ID y nombre) no estén vacíos o sean inválidos.
        if (empty($marca) || $id_marca <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        // 2. Verificar el formato del nuevo nombre.
        if (!preg_match($regex_letras, $marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca solo puede contener letras y espacios.']);
            exit;
        }
        
        // 3. Verificar duplicados, excluyendo el registro que se está editando.
        // La condición 'AND id_marca != :id_marca' es crucial para permitir guardar sin cambios
        // o cambiar a un nombre que no esté en uso por OTRO registro.
        $stmt = $conexion->prepare("SELECT id_marca FROM marcas WHERE LOWER($nombre_columna) = LOWER(:marca) AND id_marca != :id_marca");
        $stmt->execute([':marca' => $marca, ':id_marca' => $id_marca]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Esa marca ya está en uso por otro registro.']);
            exit;
        }
        
        // --- Actualización ---
        // Si las validaciones pasan, se actualiza el registro.
        $stmt = $conexion->prepare("UPDATE marcas SET $nombre_columna = :marca WHERE id_marca = :id_marca");
        if ($stmt->execute([':marca' => $marca, ':id_marca' => $id_marca])) {
            echo json_encode(['status' => 'success', 'message' => 'Marca actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la marca.']);
        }
        break;

    /**
     * --- Caso para Eliminar una Marca ---
     */

    case 'eliminar':
         $id_marca = intval($_POST['id'] ?? 0);
        if ($id_marca <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // --- Validación de Integridad Referencial ---
        // Antes de borrar, se comprueba si esta marca está siendo utilizada en la tabla 'referencia_marca'.
        // Esto previene dejar "referencias huérfanas" y mantiene la consistencia de los datos.
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM referencia_marca WHERE id_marcas = :id_marca");
        $stmt_check->execute([':id_marca' => $id_marca]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Esta marca está en uso por una o más referencias.']);
            exit;
        }

        // --- Eliminación ---
        $stmt = $conexion->prepare("DELETE FROM marcas WHERE id_marca = :id_marca");
        if ($stmt->execute([':id_marca' => $id_marca])) {
            // rowCount() devuelve el número de filas afectadas. Si es > 0, la eliminación fue exitosa.
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Marca eliminada correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró la marca para eliminar.']);
            }
        } else {
            // Este error podría ocurrir por problemas de permisos o bloqueos en la BD.
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar la marca.']);
        }
        break;

    default:
        // Si el parámetro 'accion' no coincide con ninguno de los casos anteriores.
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>