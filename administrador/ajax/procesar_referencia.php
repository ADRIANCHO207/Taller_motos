<?php
// ajax/procesar_referencia.php (VERSIÓN FINAL CON PDO Y AUDITORÍA)
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
$regex_referencia = '/^[A-Za-z][A-Za-z0-9\s-]{1,19}$/';

switch ($accion) {
    case 'agregar':
        $id_marcas = intval($_POST['id_marcas'] ?? 0);
        $referencia_marca = trim($_POST['referencia_marca'] ?? '');
        
        // Validaciones
        if (empty($referencia_marca) || $id_marcas <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ambos campos son obligatorios.']);
            exit;
        }
        if (!preg_match($regex_referencia, $referencia_marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la referencia no es válido.']);
            exit;
        }
        
        // Verificar duplicados (case-insensitive)
        $stmt = $conexion->prepare("SELECT id_referencia FROM referencia_marca WHERE LOWER(referencia_marca) = LOWER(:ref) AND id_marcas = :id_marca");
        $stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Esa referencia ya existe para la marca seleccionada.']);
            exit;
        }

        // Insertar
        $stmt = $conexion->prepare("INSERT INTO referencia_marca (referencia_marca, id_marcas) VALUES (:ref, :id_marca)");
        if ($stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas])) {
            echo json_encode(['status' => 'success', 'message' => 'Referencia agregada correctamente.']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Error al agregar la referencia.']);
        }
        break;

    case 'obtener':
        $id = intval($_GET['id'] ?? 0);
        $stmt = $conexion->prepare("SELECT id_referencia, referencia_marca, id_marcas FROM referencia_marca WHERE id_referencia = :id");
        $stmt->execute([':id' => $id]);
        $referencia = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($referencia);
        break;

    case 'actualizar':
        $id_referencia = intval($_POST['id_referencia'] ?? 0);
        $id_marcas = intval($_POST['id_marcas'] ?? 0);
        $referencia_marca = trim($_POST['referencia_marca'] ?? '');
        
        // Validaciones
        if (empty($referencia_marca) || $id_marcas <= 0 || $id_referencia <= 0) {
             echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
            exit;
        }
        if (!preg_match($regex_referencia, $referencia_marca)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato de la referencia no es válido.']);
            exit;
        }
        
        // Verificar duplicados (excluyendo el registro actual, case-insensitive)
        $stmt = $conexion->prepare("SELECT id_referencia FROM referencia_marca WHERE LOWER(referencia_marca) = LOWER(:ref) AND id_marcas = :id_marca AND id_referencia != :id_ref");
        $stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas, ':id_ref' => $id_referencia]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Esa referencia ya existe para la marca seleccionada.']);
            exit;
        }
        
        // Actualizar
        $stmt = $conexion->prepare("UPDATE referencia_marca SET referencia_marca = :ref, id_marcas = :id_marca WHERE id_referencia = :id_ref");
        if ($stmt->execute([':ref' => $referencia_marca, ':id_marca' => $id_marcas, ':id_ref' => $id_referencia])) {
            echo json_encode(['status' => 'success', 'message' => 'Referencia actualizada correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la referencia.']);
        }
        break;

    case 'eliminar':
        $id_referencia = intval($_POST['id'] ?? 0);
        if ($id_referencia <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // Opcional: Verificar dependencias en la tabla 'motos'
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM motos WHERE id_referencia_marca = :id_ref");
        $stmt_check->execute([':id_ref' => $id_referencia]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Esta referencia está en uso por una o más motos.']);
            exit;
        }

        // Eliminar
        $stmt = $conexion->prepare("DELETE FROM referencia_marca WHERE id_referencia = :id_ref");
        if ($stmt->execute([':id_ref' => $id_referencia])) {
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