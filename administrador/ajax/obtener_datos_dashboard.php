<?php
session_start();
header('Content-Type: application/json');
require_once('C:/xampp/htdocs/Taller_motos/conecct/conex.php');
$db = new Database();
$conexion = $db->conectar();

$conexion->exec("SET lc_time_names = 'es_ES'");
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$conexion->exec("SET @current_admin_id = '$id_admin_actual'");

$datos = [];

// Tarjetas
$datos['ganancias_mes'] = $conexion->query("SELECT SUM(total) as total FROM mantenimientos WHERE MONTH(fecha_realizo) = MONTH(CURDATE()) AND YEAR(fecha_realizo) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$datos['ganancias_anio'] = $conexion->query("SELECT SUM(total) as total FROM mantenimientos WHERE YEAR(fecha_realizo) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$datos['mantenimientos_mes'] = $conexion->query("SELECT COUNT(*) as total FROM mantenimientos WHERE MONTH(fecha_realizo) = MONTH(CURDATE()) AND YEAR(fecha_realizo) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$datos['clientes_mes'] = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE MONTH(fecha_creacion) = MONTH(CURDATE()) AND YEAR(fecha_creacion) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$datos['motos_mes'] = $conexion->query("SELECT COUNT(*) as total FROM motos WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Gráficas
$stmt_area = $conexion->query("SELECT MONTHNAME(fecha_realizo) as mes, SUM(total) as total_mes FROM mantenimientos WHERE YEAR(fecha_realizo) = YEAR(CURDATE()) GROUP BY MONTH(fecha_realizo) ORDER BY MONTH(fecha_realizo)");
$datos['grafica_area'] = $stmt_area->fetchAll(PDO::FETCH_ASSOC);

$stmt_pie = $conexion->query("SELECT c.nombre, COUNT(m.id_placa) as total_motos FROM clientes c JOIN motos m ON c.id_documento_cli = m.id_documento_cli GROUP BY c.id_documento_cli ORDER BY total_motos DESC LIMIT 5");
$datos['grafica_pie'] = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);

// --- ¡CAMBIO IMPORTANTE! Enviamos TODOS los registros de auditoría ---
$stmt_audit = $conexion->query("SELECT tabla_afectada, accion_realizada, descripcion, fecha_hora, id_admin, nombre FROM auditoria, administradores WHERE DATE(fecha_hora) = CURDATE() AND auditoria.id_admin = administradores.id_documento ORDER BY fecha_hora DESC;");
$datos['auditoria'] = $stmt_audit->fetchAll(PDO::FETCH_ASSOC);
// --- FIN DEL CAMBIO ---

echo json_encode(['status' => 'success', 'data' => $datos]);
?>
