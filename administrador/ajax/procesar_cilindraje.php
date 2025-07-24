<?php
// ajax/procesar_cilindraje.php (VERSIÓN FINAL CON PDO Y AUDITORÍA)
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
        $cilindraje = intval($_POST['cilindraje'] ?? 0);

        // Validaciones
        if ($cilindraje < 50 || $cilindraje > 2000) {
            echo json_encode(['status' => 'error', 'message' => 'El cilindraje debe ser un número válido entre 50 y 2000.']);
            exit;
        }

        // Verificar duplicados
        $stmt = $conexion->prepare("SELECT id_cc FROM cilindraje WHERE cilindraje = :cilindraje");
        $stmt->execute([':cilindraje' => $cilindraje]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ese cilindraje ya está registrado.']);
            exit;
        }

        // Insertar
        $stmt = $conexion->prepare("INSERT INTO cilindraje (cilindraje) VALUES (:cilindraje)");
        if ($stmt->execute([':cilindraje' => $cilindraje])) {
            echo json_encode(['status' => 'success', 'message' => 'Cilindraje agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el cilindraje.']);
        }
        break;

    case 'actualizar':
        $id_cc = intval($_POST['id_cc'] ?? 0);
        $cilindraje = intval($_POST['cilindraje'] ?? 0);

        // Validaciones
        if ($id_cc <= 0 || $cilindraje < 50 || $cilindraje > 2000) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos o fuera de rango.']);
            exit;
        }
        
        // Verificar duplicados (excluyendo el registro actual)
        $stmt = $conexion->prepare("SELECT id_cc FROM cilindraje WHERE cilindraje = :cilindraje AND id_cc != :id_cc");
        $stmt->execute([':cilindraje' => $cilindraje, ':id_cc' => $id_cc]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ese cilindraje ya está en uso por otro registro.']);
            exit;
        }
        
        // Actualizar
        $stmt = $conexion->prepare("UPDATE cilindraje SET cilindraje = :cilindraje WHERE id_cc = :id_cc");
        if ($stmt->execute([':cilindraje' => $cilindraje, ':id_cc' => $id_cc])) {
            echo json_encode(['status' => 'success', 'message' => 'Cilindraje actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el cilindraje.']);
        }
        break;

    case 'eliminar':
        $id_cc = intval($_POST['id'] ?? 0);
        if ($id_cc <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        // Opcional: Verificar dependencias en la tabla 'motos'
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM motos WHERE id_cilindraje = :id_cc");
        $stmt_check->execute([':id_cc' => $id_cc]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Este cilindraje está en uso por una o más motos.']);
            exit;
        }

        // Eliminar
        $stmt = $conexion->prepare("DELETE FROM cilindraje WHERE id_cc = :id_cc");
        if ($stmt->execute([':id_cc' => $id_cc])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Cilindraje eliminado correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró el cilindraje para eliminar.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al intentar eliminar el cilindraje.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>