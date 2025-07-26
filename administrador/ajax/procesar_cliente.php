<?php
// Inicia la sesión para manejar variables de sesión del usuario
session_start();
// Establece el tipo de respuesta como JSON
header('Content-Type: application/json');

// Importa el archivo de conexión a la base de datos
require_once __DIR__ . '/../../conecct/conex.php';

// Crea una nueva instancia de la clase Database
$db = new Database();
// Obtiene la conexión a la base de datos
$conexion = $db->conectar(); 

// Obtiene el ID del administrador actual o usa 'sistema' como valor por defecto
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
// Prepara una consulta para establecer variable de sesión MySQL (usado en triggers)
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
// Ejecuta la consulta con el ID del administrador
$stmt_session_var->execute([$id_admin_actual]);

// Obtiene la acción a realizar desde POST o GET
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// Switch para manejar diferentes operaciones CRUD
switch ($accion) {
    case 'agregar':
        // Verifica que los campos obligatorios no estén vacíos
        if (empty($_POST['documento']) || empty($_POST['nombre']) || empty($_POST['telefono']) || empty($_POST['fecha_ingreso'])) {
            echo json_encode(['status' => 'error', 'message' => 'Documento, nombre, teléfono y fecha de ingreso son obligatorios.']);
            exit;
        }

        // Captura y sanitiza los datos del formulario
        $documento = $_POST['documento'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        // Campos opcionales con valor por defecto vacío
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $fecha_ingreso = $_POST['fecha_ingreso'];
        
        // Verifica si el documento ya existe como administrador
        $stmt = $conexion->prepare("SELECT id_documento FROM administradores WHERE id_documento = :documento");
        $stmt->execute([':documento' => $documento]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El documento ya está registrado como administrador.']);
            exit;
        }

        // Verifica duplicados en la tabla de clientes
        $stmt = $conexion->prepare("SELECT id_documento_cli, telefono FROM clientes WHERE id_documento_cli = :documento OR telefono = :telefono");
        $stmt->execute([':documento' => $documento, ':telefono' => $telefono]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Si encuentra duplicados, devuelve mensaje específico
        if ($existing_user) {
            $message = ($existing_user['id_documento_cli'] == $documento) 
                ? 'El documento ya está registrado como cliente.' 
                : 'El teléfono ya está registrado por otro cliente.';
            echo json_encode(['status' => 'error', 'message' => $message]);
            exit;
        }

        // Prepara la consulta de inserción del nuevo cliente
        $stmt = $conexion->prepare("INSERT INTO clientes (id_documento_cli, nombre, telefono, email, direccion, fecha_ingreso, fecha_creacion) VALUES (:doc, :nom, :tel, :email, :dir, :fecha_ing, NOW())");
        // Ejecuta la inserción y devuelve respuesta
        if ($stmt->execute([
            ':doc' => $documento, 
            ':nom' => $nombre, 
            ':tel' => $telefono, 
            ':email' => $email, 
            ':dir' => $direccion, 
            ':fecha_ing' => $fecha_ingreso
        ])) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente agregado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el cliente.']);
        }
        break;

    case 'obtener':
        // Obtiene el ID del cliente a consultar
        $id = $_GET['id'] ?? 0;
        // Prepara y ejecuta consulta para obtener datos del cliente
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_documento_cli = :id");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        // Devuelve los datos del cliente en formato JSON
        echo json_encode($cliente);
        break;

    case 'actualizar':
        // Captura los datos a actualizar
        $id = $_POST['id_documento_cli'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        // Valida campos obligatorios
        if (empty($id) || empty($nombre) || empty($telefono)) {
            echo json_encode(['status' => 'error', 'message' => 'Documento, nombre y teléfono son obligatorios.']);
            exit;
        }

        // Verifica duplicados de teléfono excepto el cliente actual
        $stmt = $conexion->prepare("SELECT id_documento_cli FROM clientes WHERE telefono = :telefono AND id_documento_cli != :id");
        $stmt->execute([':telefono' => $telefono, ':id' => $id]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El teléfono ya está en uso por otro cliente.']);
            exit;
        }
        
        // Actualiza los datos del cliente
        $stmt = $conexion->prepare("UPDATE clientes SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion WHERE id_documento_cli = :id");
        if ($stmt->execute([':nombre' => $nombre, ':telefono' => $telefono, ':email' => $email, ':direccion' => $direccion, ':id' => $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente actualizado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el cliente.']);
        }
        break;

    case 'eliminar':
        // Obtiene el ID del cliente a eliminar
        $id = $_POST['id'] ?? 0;
        // Valida que se haya proporcionado un ID
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado.']);
            exit;
        }

        // Elimina el cliente (ON DELETE CASCADE maneja las dependencias)
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_documento_cli = :id");
        if ($stmt->execute([':id' => $id])) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente y todos sus datos asociados (motos, mantenimientos) han sido eliminados.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el cliente.']);
        }
        break;
}
?>