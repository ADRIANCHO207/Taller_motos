<?php
/**
 * Procesador de operaciones CRUD para cilindrajes de motos
 * 
 * Este archivo maneja todas las operaciones relacionadas con los cilindrajes:
 * - Crear nuevo cilindraje
 * - Actualizar cilindraje existente
 * - Eliminar cilindraje (con validación de dependencias)
 * - Validaciones y control de duplicados
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../conecct/conex.php';

$db = new Database();
$conexion = $db->conectar();

/**
 * Configuración para Auditoría
 * - Establece el ID del administrador actual para los triggers
 * - Usa una variable de sesión MySQL para tracking
 */
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        /**
         * Proceso de inserción de nuevo cilindraje
         * 
         * Validaciones:
         * 1. Rango válido (50-2000cc)
         * 2. No duplicados en la base de datos
         */
        $cilindraje = intval($_POST['cilindraje'] ?? 0);

        // Validación de rango permitido
        if ($cilindraje < 50 || $cilindraje > 2000) {
            echo json_encode(['status' => 'error', 'message' => 'El cilindraje debe ser un número válido entre 50 y 2000.']);
            exit;
        }

        // Control de duplicados mediante consulta preparada
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
        /**
         * Actualización de cilindraje existente
         * 
         * Proceso:
         * 1. Validación de datos de entrada
         * 2. Verificación de duplicados (excluyendo el registro actual)
         * 3. Actualización en base de datos
         */
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
        /**
         * Eliminación segura de cilindraje
         * 
         * Seguridad:
         * 1. Validación del ID recibido
         * 2. Verificación de dependencias en tabla 'motos'
         * 3. Confirmación de eliminación exitosa
         */
        $id_cc = intval($_POST['id'] ?? 0);
        if ($id_cc <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            exit;
        }
        
        /**
         * Verificación crítica de dependencias
         * Previene eliminación de cilindrajes que están en uso
         * para mantener la integridad referencial
         */
        $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM motos WHERE id_cilindraje = :id_cc");
        $stmt_check->execute([':id_cc' => $id_cc]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar. Este cilindraje está en uso por una o más motos.']);
            exit;
        }

        /**
         * Proceso de eliminación con verificación
         * Usa rowCount() para confirmar que el registro existía
         * y fue eliminado correctamente
         */
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