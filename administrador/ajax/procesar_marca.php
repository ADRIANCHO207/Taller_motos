<?php
// ajax/procesar_marca.php (VERSIÓN FINAL CON PDO Y AUDITORÍA)
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
$nombre_columna = 'marcas'; // El nombre de tu columna
$regex_letras = '/^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/';

switch ($accion) {
    case 'agregar':
        $marca = trim($_POST['marca'] ?? '');

        // Validaciones
        if (empty($marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca no puede estar vacío.']);
            exit;
        }
        if (!preg_match($regex_letras, $marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca solo puede contener letras y espacios.']);
            exit;
        }

        // Verificar duplicados (case-insensitive)
        $stmt = $conexion->prepare("SELECT id_marca FROM marcas WHERE LOWER($nombre_columna) = LOWER(:marca)");
        $stmt->execute([':marca' => $marca]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Esa marca ya está registrada.']);
            exit;
        }

        // Insertar
        $stmt = $conexion->prepare("INSERT INTO marcas ($nombre_columna) VALUES (:marca)");
        if ($stmt->execute([':marca' => $marca])) {
            echo json_encode(['status' => 'success', 'message' => 'Marca agregada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar la marca.']);
        }
        break;

    case 'actualizar':
        $id_marca = intval($_POST['id_marca'] ?? 0);
        $marca = trim($_POST['marca'] ?? '');

        // Validaciones
        if (empty($marca) || $id_marca <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        if (!preg_match($regex_letras, $marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre de la marca solo puede contener letras y espacios.']);
            exit;
        }
        
        // Verificar duplicados (excluyendo el registro actual, case-insensitive)
        $stmt = $conexion->prepare("SELECT id_marca FROM marcas WHERE LOWER($nombre_columna) = LOWER(:marca) AND id_marca != :id_marca");
        $stmt->execute([':marca' => $marca, ':id_marca' => $id_marca]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Esa marca ya está en uso por otro registro.']);
            exit;
        }
        
        // Actualizar
        $stmt = $conexion->prepare("UPDATE marcas SET $nombre_columna = :marca WHERE id_marca = :id_marca");
        if ($stmt->execute([':marca' => $marca, ':id_marca' => $id_marca])) {
            echo json_encode(['status' => 'success', 'message' => 'Marca actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la marca.']);
        }
        break;

    case 'eliminar':
        $id_marca = intval($_POST['id'] ?? 0);
        if ($id_marca <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // Opcional: Verificar dependencias en 'referencia_marca'
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM referencia_marca WHERE id_marcas = :id_marca");
        $stmt_check->execute([':id_marca' => $id_marca]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Esta marca está en uso por una o más referencias.']);
            exit;
        }

        // Eliminar
        $stmt = $conexion->prepare("DELETE FROM marcas WHERE id_marca = :id_marca");
        if ($stmt->execute([':id_marca' => $id_marca])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Marca eliminada correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró la marca para eliminar.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar la marca.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>