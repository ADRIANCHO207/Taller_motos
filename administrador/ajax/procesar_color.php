<?php
// Inicia la sesión para acceder a variables de sesión del usuario logueado
session_start();
// Establece que la respuesta será en formato JSON
header('Content-Type: application/json');
// Incluye el archivo de conexión a la base de datos usando ruta absoluta
require_once __DIR__ . '/../../conecct/conex.php';

// Crea una nueva instancia de la clase Database
$db = new Database();
// Establece la conexión con la base de datos
$conexion = $db->conectar();

// Obtiene el ID del administrador actual o usa 'sistema' como valor por defecto
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
// Prepara una consulta para establecer variable de sesión MySQL (usada en triggers)
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
// Ejecuta la consulta con el ID del administrador
$stmt_session_var->execute([$id_admin_actual]);

// Obtiene la acción a realizar desde POST o GET, con prioridad en POST
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
// Expresión regular para validar que solo se ingresen letras y espacios
$regex_letras = '/^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/';

// Switch para manejar las diferentes operaciones CRUD
switch ($accion) {
    case 'agregar':
        // Obtiene y limpia espacios del color enviado
        $color = trim($_POST['color'] ?? '');

        // Valida que el color no esté vacío
        if (empty($color)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del color no puede estar vacío.']);
            exit;
        }
        // Valida que el color solo contenga letras y espacios
        if (!preg_match($regex_letras, $color)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del color solo puede contener letras y espacios.']);
            exit;
        }

        // Verifica si el color ya existe (ignorando mayúsculas/minúsculas)
        $stmt = $conexion->prepare("SELECT id_color FROM color WHERE LOWER(color) = LOWER(:color)");
        $stmt->execute([':color' => $color]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ese color ya está registrado.']);
            exit;
        }

        // Inserta el nuevo color en la base de datos
        $stmt = $conexion->prepare("INSERT INTO color (color) VALUES (:color)");
        if ($stmt->execute([':color' => $color])) {
            echo json_encode(['status' => 'success', 'message' => 'Color agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el color.']);
        }
        break;

    case 'actualizar':
        // Convierte el ID a entero y obtiene el color limpio
        $id_color = intval($_POST['id_color'] ?? 0);
        $color = trim($_POST['color'] ?? '');

        // Valida que los datos sean correctos
        if (empty($color) || $id_color <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        // Valida formato del color
        if (!preg_match($regex_letras, $color)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del color solo puede contener letras y espacios.']);
            exit;
        }
        
        // Verifica duplicados excluyendo el registro actual
        $stmt = $conexion->prepare("SELECT id_color FROM color WHERE LOWER(color) = LOWER(:color) AND id_color != :id_color");
        $stmt->execute([':color' => $color, ':id_color' => $id_color]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ese color ya está en uso.']);
            exit;
        }
        
        // Actualiza el color en la base de datos
        $stmt = $conexion->prepare("UPDATE color SET color = :color WHERE id_color = :id_color");
        if ($stmt->execute([':color' => $color, ':id_color' => $id_color])) {
            echo json_encode(['status' => 'success', 'message' => 'Color actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el color.']);
        }
        break;

    case 'eliminar':
        // Convierte el ID a entero
        $id_color = intval($_POST['id'] ?? 0);
        if ($id_color <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // Verifica si el color está siendo usado por alguna moto
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM motos WHERE id_color = :id_color");
        $stmt_check->execute([':id_color' => $id_color]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Este color está en uso por una o más motos.']);
            exit;
        }

        // Elimina el color si no está siendo usado
        $stmt = $conexion->prepare("DELETE FROM color WHERE id_color = :id_color");
        if ($stmt->execute([':id_color' => $id_color])) {
            // Verifica si realmente se eliminó algún registro
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Color eliminado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró el color para eliminar.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar el color.']);
        }
        break;

    default:
        // Manejo de acciones no válidas
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>