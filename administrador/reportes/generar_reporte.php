<?php
ob_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../conecct/conex.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Establecer la zona horaria para que la fecha sea precisa
date_default_timezone_set('America/Bogota');

$db = new Database();
$conexion = $db->conectar();

// Recibir y validar los parámetros
$tipo_reporte = $_GET['tipo'] ?? '';
$formato = $_GET['formato'] ?? 'json';
$fecha_inicio = $_GET['inicio'] ?? '';
$fecha_fin = $_GET['fin'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$rango_inicio = $_GET['rango_inicio'] ?? '';
$rango_fin = $_GET['rango_fin'] ?? '';

// Definir la estructura de cada reporte
$config_reportes = [
    'actividad' => [
        'titulo' => 'Reporte de Actividad',
        'sql' => "SELECT a.fecha_hora, a.tabla_afectada, a.accion_realizada, a.descripcion, adm.nombre as nombre_admin FROM auditoria a LEFT JOIN administradores adm ON a.id_admin = adm.id_documento",
        'encabezados' => ['Fecha y Hora', 'Módulo', 'Acción', 'Descripción', 'Realizado Por'],
        'columnas' => ['fecha_hora', 'tabla_afectada', 'accion_realizada', 'descripcion', 'nombre_admin'],
        'filtro_fecha' => 'a.fecha_hora',
        'filtro_texto' => ['a.tabla_afectada', 'a.accion_realizada', 'adm.nombre'],
        'pdf_widths' => [40, 30, 35, 132, 40] // Anchos para 5 columnas
    ],

   'mantenimientos' => [
        'titulo' => 'Reporte de Mantenimientos',
        'sql' => "
            SELECT 
                m.id_mantenimientos, 
                m.id_placa, 
                c.nombre AS cliente, 
                m.fecha_realizo, 
                m.kilometraje, 
                GROUP_CONCAT(CONCAT(tt.detalle, ' (Cant: ', dm.cantidad, ')') SEPARATOR '\n') AS detalles_trabajos,
                 m.observaciones_entrada,
                m.observaciones_salida,
                m.total
            FROM mantenimientos m 
            JOIN motos mo ON m.id_placa = mo.id_placa 
            JOIN clientes c ON mo.id_documento_cli = c.id_documento_cli
            LEFT JOIN detalle_mantenimientos dm ON m.id_mantenimientos = dm.id_mantenimiento
            LEFT JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo
        ",
        'group_by' => " GROUP BY m.id_mantenimientos", // Cláusula GROUP BY separada
        'encabezados' => ['ID', 'Placa', 'Cliente', 'Fecha', 'Kilometraje', 'Trabajos Realizados', 'Obs. Entrada', 'Obs. Salida', 'Total'],
        'columnas' => ['id_mantenimientos', 'id_placa', 'cliente', 'fecha_realizo', 'kilometraje', 'detalles_trabajos', 'observaciones_entrada', 'observaciones_salida', 'total'],
        'filtro_fecha' => 'm.fecha_realizo',
        'filtro_texto' => ['m.id_placa', 'c.nombre'],
        'pdf_widths' => [12, 20, 45, 35, 25, 60, 30, 30, 20] // Anchos para 9 columnas
    ],

    'clientes' => [
        'titulo' => 'Reporte de Clientes',
        'sql' => "SELECT id_documento_cli, nombre, telefono, email, direccion, fecha_creacion FROM clientes",
        'encabezados' => ['Documento', 'Nombre', 'Teléfono', 'Email', 'Dirección', 'Fecha Creación'],
        'columnas' => ['id_documento_cli', 'nombre', 'telefono', 'email', 'direccion', 'fecha_creacion'],
        'filtro_fecha' => 'fecha_creacion',
        'filtro_texto' => ['nombre', 'id_documento_cli'],
        'pdf_widths' => [30, 30, 30, 60, 80, 30] // Anchos para 6 columnas
    ],
   'motos' => [
        'titulo' => 'Reporte de Motos',
                    'sql' => "SELECT 
                mo.id_placa,
                c.nombre AS cliente,
                CONCAT(ma.marcas, ' ', rm.referencia_marca) AS referencia,
                c1.cilindraje,
                md.anio AS modelo,
                co.color
            FROM motos mo
            JOIN clientes c ON mo.id_documento_cli = c.id_documento_cli
            JOIN referencia_marca rm ON mo.id_referencia_marca = rm.id_referencia
            JOIN marcas ma ON rm.id_marcas = ma.id_marca
            JOIN modelos md ON mo.id_modelo = md.id_modelo
            JOIN cilindraje c1 ON mo.id_cilindraje = c1.id_cc
            JOIN color co ON mo.id_color = co.id_color",
        'encabezados' => ['Placa', 'Cliente', 'Referencia', 'Cilindraje', 'Modelo', 'Color'],
        'columnas' => ['id_placa', 'cliente', 'referencia', 'cilindraje', 'modelo', 'color'],
        'filtro_texto' => ['mo.id_placa', 'c.nombre', 'ma.marcas', 'rm.referencia_marca'],
        'pdf_widths' => [40, 80, 35, 35, 40, 30]
    ],

    'marcas' => [
        'titulo' => 'Reporte de Marcas',
        'sql' => "SELECT id_marca, marcas FROM marcas",
        'encabezados' => ['ID', 'Marca'],
        'columnas' => ['id_marca', 'marcas'],
        'filtro_texto' => ['marcas'],
        'pdf_widths' => [20, 80] // Anchos para 2 columnas
    ],

    'referencias' => [
        'titulo' => 'Reporte de Referencias de Marca',
        'sql' => "SELECT rm.id_referencia, rm.referencia_marca, ma.marcas FROM referencia_marca rm JOIN marcas ma ON rm.id_marcas = ma.id_marca",
        'encabezados' => ['ID', 'Referencia', 'Marca'],
        'columnas' => ['id_referencia', 'referencia_marca', 'marcas'],
        'filtro_texto' => ['rm.referencia_marca', 'ma.marcas'],
        'pdf_widths' => [20, 80, 40] // Anchos para 3 columnas
    ],

    'cilindrajes' => [
    'titulo' => 'Reporte de Cilindrajes',
    'sql' => "SELECT id_cc, cilindraje FROM cilindraje",
    'encabezados' => ['ID', 'Cilindraje'],
    'columnas' => ['id_cc', 'cilindraje'],
    'filtro_rango' => 'cilindraje', 
    'pdf_widths' => [20, 80]
    ],

    'modelos' => [
        'titulo' => 'Reporte de Modelos',
        'sql' => "SELECT id_modelo, anio FROM modelos",
        'encabezados' => ['ID', 'Año'],
        'columnas' => ['id_modelo', 'anio'],
        'filtro_rango' => 'anio', 
        'pdf_widths' => [20, 80]
    ],

    'colores' => [
        'titulo' => 'Reporte de Colores',
        'sql' => "SELECT id_color, color FROM color",
        'encabezados' => ['ID', 'Color'],
        'columnas' => ['id_color', 'color'],
        'filtro_texto' => ['color'],
        'pdf_widths' => [20, 80] // Anchos para 2 columnas
    ],

    'tipos_trabajos' => [
        'titulo' => 'Reporte de Tipos de Trabajo',
        'sql' => "SELECT id_tipo, detalle, cc_inicial, cc_final, precio_unitario FROM tipo_trabajo",
        'encabezados' => ['ID', 'Detalle', 'CC Inicial', 'CC Final', 'Precio Unitario'],
        'columnas' => ['id_tipo', 'detalle', 'cc_inicial', 'cc_final', 'precio_unitario'],
        'filtro_texto' => ['detalle'],
        'pdf_widths' => [40, 80, 40, 40, 40] // Anchos para 5 columnas
    ],
   
];

if (!isset($config_reportes[$tipo_reporte])) { 
    die("Tipo de reporte no válido."); 
}
$config = $config_reportes[$tipo_reporte];
$sql = $config['sql'];
$params = [];
$where = [];

$titulo_completo = $config['titulo'];
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $titulo_completo .= ' del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin));
}

// Filtro de fecha
if (!empty($fecha_inicio) && !empty($fecha_fin) && isset($config['filtro_fecha'])) {
    $where[] = "DATE({$config['filtro_fecha']}) BETWEEN :inicio AND :fin";
    $params[':inicio'] = $fecha_inicio;
    $params[':fin'] = $fecha_fin;
}

// Filtro de texto
if (!empty($busqueda) && isset($config['filtro_texto'])) {
    $clausulas_like = [];
    $i = 0;
    foreach ($config['filtro_texto'] as $campo) {
        $placeholder = ":busqueda" . $i;
        $clausulas_like[] = "$campo LIKE $placeholder";
        $params[$placeholder] = "%" . $busqueda . "%";
        $i++;
    }
    $where[] = "(" . implode(' OR ', $clausulas_like) . ")";
}

if (!empty($rango_inicio) && !empty($rango_fin) && isset($config['filtro_rango'])) {
    $where[] = "{$config['filtro_rango']} BETWEEN :rango_inicio AND :rango_fin";
    $params[':rango_inicio'] = $rango_inicio;
    $params[':rango_fin'] = $rango_fin;
}

// --- CONSULTA ---
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
if (isset($config['group_by'])) {
    $sql .= $config['group_by'];
}
// Siempre puedes añadir un ORDER BY al final si lo tienes en la config
if (isset($config['order_by'])) {
    $sql .= $config['order_by'];
}


$stmt = $conexion->prepare($sql);
$stmt->execute($params); 
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt->execute($params);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$config = $config_reportes[$tipo_reporte];

if ($formato === 'json') {
    header('Content-Type: application/json');
    if (empty($datos)) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron datos con los filtros seleccionados.']);
    } else {
        echo json_encode(['status' => 'success', 'data' => $datos, 'encabezados' => $config['encabezados'], 'titulo' => $config['titulo']]);
    }
    exit;
}

if (empty($datos)) {
    echo "<h1>No hay datos para los filtros seleccionados.</h1><button onclick='window.close();'>Cerrar</button>";
    exit;
}

$logoPath = realpath(__DIR__ . '/../../img/logo.jpg'); 

if ($formato === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr(ucfirst($tipo_reporte), 0, 30));

    //  Añadir el logo si la ruta es válida
    if ($logoPath && file_exists($logoPath)) {
        $drawing = new Drawing();
        $drawing->setName('Logo Taller');
        $drawing->setDescription('Logo Taller de Motos');
        $drawing->setPath($logoPath);
        $drawing->setHeight(80);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);
        $sheet->getRowDimension('1')->setRowHeight(65);
    }

    //  Título y subtítulo al lado del logo
    $lastColumn = Coordinate::stringFromColumnIndex(count($config['encabezados']));
    $sheet->mergeCells('B2:' . $lastColumn . '2');
    $sheet->setCellValue('B2', $titulo_completo);
    $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(18);
    $sheet->getStyle('B2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->mergeCells('B3:' . $lastColumn . '3');
    $sheet->setCellValue('B3', 'Generado el ' . date('d/m/Y H:i:s'));
    $sheet->getStyle('B3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    
    //  Encabezados de la tabla
    $headerRow = 6;
    $sheet->fromArray($config['encabezados'], NULL, 'A' . $headerRow);
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4E73DF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];
    $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $headerRow)->applyFromArray($headerStyle);
    
    //  Preparar y llenar datos
    $datos_para_excel = [];
    foreach ($datos as $fila_dato) {
        $fila_ordenada = [];
        foreach ($config['columnas'] as $nombre_columna) {
            $valor = $fila_dato[$nombre_columna] ?? '';
            // Formatear columnas especiales si es necesario
            if (in_array($nombre_columna, ['fecha_hora', 'fecha_creacion', 'fecha_realizo'])) $valor = date('d/m/Y H:i', strtotime($valor));
            $fila_ordenada[] = $valor;
        }
        $datos_para_excel[] = $fila_ordenada;
    }
    $sheet->fromArray($datos_para_excel, NULL, 'A' . ($headerRow + 1));
    
    //  Añadir fila de totales si es un reporte de mantenimientos
    if ($tipo_reporte === 'mantenimientos') {
        $total_mantenimientos = array_sum(array_column($datos, 'total'));
        $ultima_fila = $headerRow + count($datos) + 1;
        $sheet->setCellValue('A' . $ultima_fila, 'Total Ingresos:');
        $sheet->mergeCells('A' . $ultima_fila . ':' . Coordinate::stringFromColumnIndex(count($config['encabezados']) - 1) . $ultima_fila);
        $sheet->getStyle('A' . $ultima_fila)->getFont()->setBold(true);
        $sheet->getStyle('A' . $ultima_fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue($lastColumn . $ultima_fila, $total_mantenimientos);
        $sheet->getStyle($lastColumn . $ultima_fila)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    }
    
    //  Autoajustar ancho de columnas
    foreach(range('A', $lastColumn) as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_'.$tipo_reporte.'_'.date('Y-m-d').'.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else { // pdf
      class PDF extends FPDF {
        public $titulo;
        public $subtitulo;
        public $encabezados;
        public $widths;
        public $logoPath;

        // Encabezado de página
        function Header() {
            // Logo (solo si la ruta es válida)
            if ($this->logoPath && file_exists($this->logoPath)) {
                // Obtenemos la extensión del archivo para pasarla a Image()
                $imageType = strtoupper(pathinfo($this->logoPath, PATHINFO_EXTENSION));
                $this->Image($this->logoPath, 10, 8, 33, 0, $imageType);
            }

            // Título principal
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(80); // Mover a la derecha para dejar espacio al logo
            $this->Cell(110, 10, utf8_decode($this->titulo), 0, 1, 'C');
            
            // Subtítulo (fecha de generación)
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(80);
            $this->Cell(110, 10, 'Generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
            $this->Ln(10); // Salto de línea
            
            // Encabezados de tabla
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(230, 230, 230);
            $this->SetTextColor(0);
            $this->SetDrawColor(128, 128, 128);
            foreach($this->encabezados as $i => $header) {
                $this->Cell($this->widths[$i], 7, utf8_decode($header), 1, 0, 'C', true);
            }
            $this->Ln();
        }

        // Pie de página
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
        function RowWithWrap($data) { $nb=0; for($i=0;$i<count($data);$i++){$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));} $h=5*$nb; $this->CheckPageBreak($h); for($i=0;$i<count($data);$i++){$w=$this->widths[$i];$x=$this->GetX();$y=$this->GetY();$this->Rect($x,$y,$w,$h);$this->MultiCell($w,5,utf8_decode($data[$i]),0,'L');$this->SetXY($x+$w,$y);} $this->Ln($h); }
        function CheckPageBreak($h){if($this->GetY()+$h>$this->PageBreakTrigger){$this->AddPage($this->CurOrientation);}}
        function NbLines($w,$txt){$cw=&$this->CurrentFont['cw'];if($w==0)$w=$this->w-$this->rMargin-$this->x;$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;$s=str_replace("\r",'',$txt);$nb=strlen($s);if($nb>0 and $s[$nb-1]=="\n")$nb--;$sep=-1;$i=0;$j=0;$l=0;$nl=1;while($i<$nb){$c=$s[$i];if($c=="\n"){$i++;$sep=-1;$j=$i;$l=0;$nl++;continue;}if($c==' ')$sep=$i;$l+=$cw[$c];if($l>$wmax){if($sep==-1){if($i==$j)$i++;}else $i=$sep+1;$sep=-1;$j=$i;$l=0;$nl++;}else $i++;}return $nl;}
    }

    $pdf = new PDF('L', 'mm', 'A4');
    $pdf->logoPath = $logoPath; 
    $pdf->titulo = $titulo_completo;
    $pdf->encabezados = $config['encabezados'];
    $pdf->widths = $config['pdf_widths'];
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 9);

    foreach ($datos as $fila_dato) {
        $fila_para_pdf = [];
        foreach($config['columnas'] as $col) {
            // Formatear columnas especiales
            $valor = $fila_dato[$col] ?? '';
            if ($col === 'total') $valor = '$ ' . number_format($valor, 2, ',', '.');
            if ($col === 'kilometraje') $valor = number_format($valor, 0, ',', '.') . ' km';
            if (in_array($col, ['fecha_hora', 'fecha_creacion', 'fecha_realizo'])) $valor = date('d/m/Y H:i', strtotime($valor));
            $fila_para_pdf[] = $valor;
        }
        $pdf->RowWithWrap($fila_para_pdf);
    }

    // Añadir fila de totales si es un reporte de mantenimientos
    if ($tipo_reporte === 'mantenimientos') {
        $total_mantenimientos = array_sum(array_column($datos, 'total'));
        $pdf->SetFont('Arial', 'B', 10);
        $ancho_total_sin_ultima = array_sum(array_slice($pdf->widths, 0, -1));
        $pdf->Cell($ancho_total_sin_ultima, 8, 'Total Ingresos:', 1, 0, 'R');
        $pdf->Cell(end($pdf->widths), 8, '$ ' . number_format($total_mantenimientos, 2, ',', '.'), 1, 1, 'R');
    }
    
    ob_end_clean();
    $pdf->Output('D', 'reporte_'.$tipo_reporte.'_'.date('Y-m-d').'.pdf');
    exit;
}
