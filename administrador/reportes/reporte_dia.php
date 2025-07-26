<?php
// --- administrador/reportes/reporte_dia.php (VERSIÓN REPORTE DE MANTENIMIENTOS) ---
ob_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../conecct/conex.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

date_default_timezone_set('America/Bogota');
$db = new Database();
$conexion = $db->conectar();

$fecha_inicio = $_GET['inicio'] ?? '';
$fecha_fin = $_GET['fin'] ?? '';
$formato = $_GET['formato'] ?? 'excel';

if (empty($fecha_inicio) || empty($fecha_fin) || $fecha_inicio > $fecha_fin) {
    die("Error: Rango de fechas no válido.");
}

$fecha_fin_ajustada = date('Y-m-d 23:59:59', strtotime($fecha_fin));
$titulo_reporte = 'Reporte de Mantenimientos del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin));

// --- CONSULTA PRINCIPAL: Obtiene todos los detalles de mantenimientos en el rango ---
$sql = "SELECT 
            m.id_mantenimientos, m.fecha_realizo, m.kilometraje, m.total, m.observaciones_entrada, m.observaciones_salida,
            mo.id_placa,
            c.nombre as nombre_cliente,
            tt.detalle, tt.cc_inicial, tt.cc_final,
            dm.cantidad, dm.subtotal
        FROM mantenimientos m
        JOIN motos mo ON m.id_placa = mo.id_placa
        JOIN clientes c ON mo.id_documento_cli = c.id_documento_cli
        LEFT JOIN detalle_mantenimientos dm ON m.id_mantenimientos = dm.id_mantenimiento
        LEFT JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo
        WHERE m.fecha_realizo BETWEEN :inicio AND :fin
        ORDER BY m.id_mantenimientos ASC, tt.detalle ASC";
$stmt = $conexion->prepare($sql);
$stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin_ajustada]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($resultados)) {
    ob_end_clean();
    echo "<h1>No hay mantenimientos registrados para el rango de fechas seleccionado.</h1><button onclick='window.close();'>Cerrar</button>";
    exit;
}

// --- Agrupar resultados por mantenimiento ---
$mantenimientos_agrupados = [];
foreach ($resultados as $fila) {
    $id = $fila['id_mantenimientos'];
    if (!isset($mantenimientos_agrupados[$id])) {
        $mantenimientos_agrupados[$id] = [
            'info' => $fila,
            'detalles' => []
        ];
    }
    if ($fila['detalle']) { // Solo añadir si hay detalles de trabajo
        $mantenimientos_agrupados[$id]['detalles'][] = $fila;
    }
}

// --- Generación del reporte ---
if ($formato === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Mantenimientos');
    
    $sheet->setCellValue('A1', $titulo_reporte)->mergeCells('A1:F1')->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    
    $row = 3;
    foreach ($mantenimientos_agrupados as $id => $data) {
        $info = $data['info'];
        // Fila principal del mantenimiento
        $sheet->setCellValue('A'.$row, 'Mantenimiento ID: ' . $id)->mergeCells('A'.$row.':F'.$row)->getStyle('A'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('4E73DF');
        $sheet->getStyle('A'.$row)->getFont()->getColor()->setARGB('FFFFFF');
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Fecha:')->getStyle('A'.$row)->getFont()->setBold(true);
        $sheet->setCellValue('B'.$row, date('d/m/Y H:i', strtotime($info['fecha_realizo'])));
        $sheet->setCellValue('C'.$row, 'Placa:')->getStyle('C'.$row)->getFont()->setBold(true);
        $sheet->setCellValue('D'.$row, $info['id_placa']);
        $sheet->setCellValue('E'.$row, 'Cliente:')->getStyle('E'.$row)->getFont()->setBold(true);
        $sheet->setCellValue('F'.$row, $info['nombre_cliente']);
        $row++;

        // Fila de detalles de trabajos
        $sheet->setCellValue('B'.$row, 'Trabajo Realizado')->getStyle('B'.$row)->getFont()->setBold(true);
        $sheet->setCellValue('C'.$row, 'Rango CC')->getStyle('C'.$row)->getFont()->setBold(true);
        $sheet->setCellValue('D'.$row, 'Cantidad')->getStyle('D'.$row)->getFont()->setBold(true);
        $sheet->setCellValue('E'.$row, 'Subtotal')->getStyle('E'.$row)->getFont()->setBold(true);
        $row++;

        foreach ($data['detalles'] as $detalle) {
            $sheet->setCellValue('B'.$row, $detalle['detalle']);
            $sheet->setCellValue('C'.$row, $detalle['cc_inicial'] . '-' . $detalle['cc_final'] . 'cc');
            $sheet->setCellValue('D'.$row, $detalle['cantidad']);
            
            // --- ¡CORRECCIÓN 1! ---
            // Primero, establecemos el valor en la celda
            $sheet->setCellValue('E'.$row, $detalle['subtotal']);
            // Después, aplicamos el formato de número a la celda
            $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            $row++;
        }

        // Fila del Total
        $sheet->setCellValue('D'.$row, 'Total Mantenimiento:')->getStyle('D'.$row)->getFont()->setBold(true);
        $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // --- ¡CORRECCIÓN 2! ---
        // Primero, establecemos el valor y ponemos la fuente en negrita
        $sheet->setCellValue('E'.$row, $info['total'])->getStyle('E'.$row)->getFont()->setBold(true);
        // Después, en una línea separada, aplicamos el formato de número
        $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $row += 2; // Espacio entre mantenimientos
    }

    foreach(range('A','F') as $columnID) { $sheet->getColumnDimension($columnID)->setAutoSize(true); }

    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_mantenimientos_'.$fecha_inicio.'_al_'.$fecha_fin.'.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else { // ==========================================================
         // GENERACIÓN DE REPORTE PDF
         // ==========================================================
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, utf8_decode($titulo_reporte), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);

    foreach ($mantenimientos_agrupados as $id => $data) {
        $info = $data['info'];
        $pdf->SetFillColor(78, 115, 223);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Mantenimiento ID: ' . $id . ' | Placa: ' . $info['id_placa'] . ' | Cliente: ' . utf8_decode($info['nombre_cliente']), 1, 1, 'L', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y H:i', strtotime($info['fecha_realizo'])), 'LR', 1);

        // Tabla de detalles
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(100, 7, 'Trabajo Realizado', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Rango CC', 1, 0, 'C');
        $pdf->Cell(20, 7, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(40, 7, 'Subtotal', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        foreach ($data['detalles'] as $detalle) {
            $pdf->Cell(100, 6, utf8_decode($detalle['detalle']), 1);
            $pdf->Cell(30, 6, $detalle['cc_inicial'] . '-' . $detalle['cc_final'] . 'cc', 1, 0, 'C');
            $pdf->Cell(20, 6, $detalle['cantidad'], 1, 0, 'C');
            $pdf->Cell(40, 6, '$ ' . number_format($detalle['subtotal'], 2, ',', '.'), 1, 1, 'R');
        }

        // Total
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(150, 8, 'Total Mantenimiento:', 1, 0, 'R');
        $pdf->Cell(40, 8, '$ ' . number_format($info['total'], 2, ',', '.'), 1, 1, 'R');
        $pdf->Ln(10); // Espacio entre mantenimientos
    }

    ob_end_clean();
    $pdf->Output('D', 'reporte_mantenimientos_'.$fecha_inicio.'_al_'.$fecha_fin.'.pdf');
    exit;
}
?>