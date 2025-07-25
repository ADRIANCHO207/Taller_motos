<?php
// ajax/procesar_cliente.php (VERSIÓN FINAL CON PDO Y AUDITORÍA)
session_start();
header('Content-Type: application/json');

// Usar una ruta relativa segura
require_once __DIR__ . '/../../conecct/conex.php';

$db = new Database();
$conexion = $db->conectar(); // $conexion es ahora un objeto PDO

// Establecer la variable de sesión de MySQL para los Triggers
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        if (empty($_POST['documento']) || empty($_POST['nombre']) || empty($_POST['telefono']) || empty($_POST['fecha_ingreso'])) {
            echo json_encode(['status' => 'error', 'message' => 'Documento, nombre, teléfono y fecha de ingreso son obligatorios.']);
            exit;
        }

        $documento = $_POST['documento'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $fecha_ingreso = $_POST['fecha_ingreso'];
        
        // Validar en tabla de administradores
        $stmt = $conexion->prepare("SELECT id_documento FROM administradores WHERE id_documento = :documento");
        $stmt->execute([':documento' => $documento]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El documento ya está registrado como administrador.']);
            exit;
        }

        // Validar duplicados en tabla de clientes
        $stmt = $conexion->prepare("SELECT id_documento_cli, telefono FROM clientes WHERE id_documento_cli = :documento OR telefono = :telefono");
        $stmt->execute([':documento' => $documento, ':telefono' => $telefono]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing_user) {
            $message = ($existing_user['id_documento_cli'] == $documento) 
                ? 'El documento ya está registrado como cliente.' 
                : 'El teléfono ya está registrado por otro cliente.';
            echo json_encode(['status' => 'error', 'message' => $message]);
            exit;
        }

        // Insertar
        $stmt = $conexion->prepare("INSERT INTO clientes (id_documento_cli, nombre, telefono, email, direccion, fecha_ingreso, fecha_creacion) VALUES (:doc, :nom, :tel, :email, :dir, :fecha_ing, NOW())");
        if ($stmt->execute([':doc' => $documento, ':nom' => $nombre, ':tel' => $telefono, ':email' => $email, ':dir' => $direccion, ':fecha_ing' => $fecha_ingreso])) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el cliente.']);
        }
        break;
        
    case 'obtener':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_documento_cli = :id");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($cliente);
        break;

    case 'actualizar':
        $id = $_POST['id_documento_cli'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        if (empty($id) || empty($nombre) || empty($telefono)) {
            echo json_encode(['status' => 'error', 'message' => 'Documento, nombre y teléfono son obligatorios.']);
            exit;
        }

        // Validar duplicados de teléfono (excluyendo al propio cliente)
        $stmt = $conexion->prepare("SELECT id_documento_cli FROM clientes WHERE telefono = :telefono AND id_documento_cli != :id");
        $stmt->execute([':telefono' => $telefono, ':id' => $id]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El teléfono ya está en uso por otro cliente.']);
            exit;
        }
        
        // Actualizar
        $stmt = $conexion->prepare("UPDATE clientes SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion WHERE id_documento_cli = :id");
        if ($stmt->execute([':nombre' => $nombre, ':telefono' => $telefono, ':email' => $email, ':direccion' => $direccion, ':id' => $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el cliente.']);
        }
        break;

    case 'eliminar':
        $id = $_POST['id'] ?? 0;
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado.']);
            exit;
        }

        // ¡Este código ahora funcionará gracias a ON DELETE CASCADE!
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_documento_cli = :id");
        if ($stmt->execute([':id' => $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente y todos sus datos asociados (motos, mantenimientos) han sido eliminados.']);
        } else {
            // Este error ya no debería ocurrir por violación de integridad
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el cliente.']);
        }
        break;
}
?>