<?php
session_start();
header('Content-Type: application/json');
require_once('C:/xampp/htdocs/Taller_motos/conecct/conex.php');
$db = new Database();
$conexion = $db->conectar();

// Configurar el idioma de las fechas en español
$conexion->exec("SET lc_time_names = 'es_ES'");

// Obtener el ID del administrador actual o usar 'sistema' como valor por defecto
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$conexion->exec("SET @current_admin_id = '$id_admin_actual'");

$datos = [];


// Calcular ganancias del mes actual
$datos['ganancias_mes'] = $conexion->query("SELECT SUM(total) as total FROM mantenimientos WHERE MONTH(fecha_realizo) = MONTH(CURDATE()) AND YEAR(fecha_realizo) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Calcular ganancias del año actual
$datos['ganancias_anio'] = $conexion->query("SELECT SUM(total) as total FROM mantenimientos WHERE YEAR(fecha_realizo) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Contar mantenimientos del mes actual
$datos['mantenimientos_mes'] = $conexion->query("SELECT COUNT(*) as total FROM mantenimientos WHERE MONTH(fecha_realizo) = MONTH(CURDATE()) AND YEAR(fecha_realizo) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Contar nuevos clientes del mes actual
$datos['clientes_mes'] = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE MONTH(fecha_creacion) = MONTH(CURDATE()) AND YEAR(fecha_creacion) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Contar nuevas motos registradas en el mes actual
$datos['motos_mes'] = $conexion->query("SELECT COUNT(*) as total FROM motos WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


// Obtener datos para la gráfica de área (ingresos por mes)
// Usa MONTHNAME para obtener el nombre del mes en español
$stmt_area = $conexion->query("SELECT MONTHNAME(fecha_realizo) as mes, SUM(total) as total_mes 
    FROM mantenimientos 
    WHERE YEAR(fecha_realizo) = YEAR(CURDATE()) 
    GROUP BY MONTH(fecha_realizo) 
    ORDER BY MONTH(fecha_realizo)");
$datos['grafica_area'] = $stmt_area->fetchAll(PDO::FETCH_ASSOC);

// Obtener top 5 clientes con más motos para la gráfica de pie
$stmt_pie = $conexion->query("SELECT c.nombre, COUNT(m.id_placa) as total_motos 
    FROM clientes c 
    JOIN motos m ON c.id_documento_cli = m.id_documento_cli 
    GROUP BY c.id_documento_cli 
    ORDER BY total_motos DESC 
    LIMIT 5");
$datos['grafica_pie'] = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);



// Consulta compleja que obtiene los mantenimientos del día actual
// - Usa JOIN múltiples para relacionar todas las tablas necesarias
// - GROUP_CONCAT agrupa todos los trabajos realizados en una sola cadena
// - WHERE filtra solo los mantenimientos de hoy
$sql_mantenimientos_dia = "
    SELECT 
        m.id_mantenimientos, 
        m.id_placa, 
        c.nombre AS cliente, 
        m.total, 
        m.fecha_realizo,
        GROUP_CONCAT(tt.detalle SEPARATOR ', ') AS detalles_trabajos
    FROM mantenimientos m
    JOIN motos mo ON m.id_placa = mo.id_placa
    JOIN clientes c ON mo.id_documento_cli = c.id_documento_cli
    LEFT JOIN detalle_mantenimientos dm ON m.id_mantenimientos = dm.id_mantenimiento
    LEFT JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo
    WHERE DATE(m.fecha_realizo) = CURDATE()
    GROUP BY m.id_mantenimientos
    ORDER BY m.fecha_realizo DESC
";
$stmt_mantenimientos = $conexion->query($sql_mantenimientos_dia);
$datos['mantenimientos_dia'] = $stmt_mantenimientos->fetchAll(PDO::FETCH_ASSOC);

// Devolver todos los datos en formato JSON
echo json_encode(['status' => 'success', 'data' => $datos]);
?>