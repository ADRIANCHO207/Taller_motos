<?php
ob_start();

// Cargar el autoloader de Composer, que maneja PhpSpreadsheet y otras librerías PSR-4
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php';
require_once __DIR__ . '/../../conecct/conex.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$db = new Database();
$conexion = $db->conectar();

// Recibir y validar las fechas
$fecha_inicio = $_GET['inicio'] ?? '';
$fecha_fin = $_GET['fin'] ?? '';
$formato = $_GET['formato'] ?? 'excel';

if (empty($fecha_inicio) || empty($fecha_fin) || $fecha_inicio > $fecha_fin) {
    die("Error: Rango de fechas no válido.");
}

$fecha_fin_ajustada = date('Y-m-d 23:59:59', strtotime($fecha_fin));
$titulo_reporte = 'Reporte de Actividad del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin));

// Consulta con rango
$sql = "SELECT a.fecha_hora, a.tabla_afectada, a.accion_realizada, a.descripcion, a.id_admin, adm.nombre as nombre_admin
        FROM auditoria a
        LEFT JOIN administradores adm ON a.id_admin = adm.id_documento
        WHERE a.fecha_hora BETWEEN :inicio AND :fin
        ORDER BY a.fecha_hora ASC";
$stmt = $conexion->prepare($sql);
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin_ajustada]);
$datos_rango = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($datos_rango)) {
    ob_end_clean(); // Limpiar el búfer antes de mostrar el mensaje
    echo "<h1>No hay actividad registrada para el rango de fechas seleccionado.</h1><button onclick='window.close();'>Cerrar</button>";
    exit;
}

if ($formato === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte ' . $fecha_inicio);
    $sheet->setCellValue('A1', $titulo_reporte);
    $sheet->mergeCells('A1:E1')->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->setCellValue('A2', 'A continuación, se presentan las actividades registradas en el sistema para el período seleccionado:');
    $sheet->mergeCells('A2:E2')->getStyle('A2')->getFont()->setSize(12);
    
    $sheet->setCellValue('A3', 'Fecha y Hora')->getStyle('A3')->getFont()->setBold(true);
    $sheet->setCellValue('B3', 'Módulo')->getStyle('B3')->getFont()->setBold(true);
    $sheet->setCellValue('C3', 'Acción')->getStyle('C3')->getFont()->setBold(true);
    $sheet->setCellValue('D3', 'Descripción')->getStyle('D3')->getFont()->setBold(true);
    $sheet->setCellValue('E3', 'Realizado Por')->getStyle('E3')->getFont()->setBold(true);
    
    $row = 4;
    foreach ($datos_rango as $dato) {
        $sheet->setCellValue('A' . $row, date('d/m/Y h:i:s a', strtotime($dato['fecha_hora'])));
        $sheet->setCellValue('B' . $row, $dato['tabla_afectada']);
        $sheet->setCellValue('C' . $row, $dato['accion_realizada']);
        $sheet->setCellValue('D' . $row, $dato['descripcion']);
        // Si se encontró el nombre del admin, lo usamos. Si no, mostramos el ID que se guardó.
        $realizado_por = $dato['nombre_admin'] ?? ($dato['id_admin'] ?? 'Desconocido');
        $sheet->setCellValue('E' . $row, $realizado_por);
        $row++;
    }

    foreach(range('A','E') as $columnID) { $sheet->getColumnDimension($columnID)->setAutoSize(true); }

    ob_end_clean();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_actividad_'.$fecha_inicio.'_a_'.$fecha_fin.'.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else { // pdf
    ob_end_clean();

    // Ahora PHP ya sabe qué es FPDF gracias al require_once de arriba
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, utf8_decode($titulo_reporte), 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', '', 12); // Cambiamos a una fuente normal
    $texto_descriptivo = "A continuación, se presentan las actividades registradas en el sistema para el período seleccionado:";
    $pdf->MultiCell(0, 5, utf8_decode($texto_descriptivo), 0, 'L');
    $pdf->Ln(5); // Añadimos un espacio extra antes de la tabla
    
    // Encabezados de la tabla
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(40, 7, 'Fecha y Hora', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Modulo', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Accion', 1, 0, 'C', true);
    $pdf->Cell(132, 7, 'Descripcion', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Realizado Por', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 9);
    foreach ($datos_rango as $dato) {
        $realizado_por = $dato['nombre_admin'] ?? ($dato['id_admin'] ?? 'Desconocido');
        
        // Obtener la altura actual de la fila antes de dibujar las celdas
        $y_inicial = $pdf->GetY();
        
        // Dibujar las celdas de ancho fijo
        $pdf->Cell(40, 7, date('d/m/Y h:i a', strtotime($dato['fecha_hora'])), 1);
        $pdf->Cell(30, 7, utf8_decode($dato['tabla_afectada']), 1);
        $pdf->Cell(35, 7, utf8_decode($dato['accion_realizada']), 1);
        
        // Guardar la posición para la siguiente celda
        $x_pos_final = $pdf->GetX() + 132;
        
        // Usar MultiCell solo para la descripción, permitiendo que crezca
        $pdf->MultiCell(132, 7, utf8_decode($dato['descripcion']), 1);
        
        // Calcular la altura real que ocupó el MultiCell
        $altura_fila = $pdf->GetY() - $y_inicial;
        
        // Volver a la posición Y inicial y dibujar la celda final con la altura correcta
        $pdf->SetXY($x_pos_final, $y_inicial);
        $pdf->Cell(40, $altura_fila, utf8_decode($realizado_por), 1, 1);
    }
    
    $pdf->Output('D', 'reporte_actividad_'.$fecha_inicio.'_a_'.$fecha_fin.'.pdf');
    exit;
}
?>