<?php
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión"); }

$id_mantenimiento = $_GET['id'] ?? 0;
if ($id_mantenimiento == 0) { die("ID de mantenimiento no válido."); }

// Obtener datos del mantenimiento
$sql_main = "SELECT m.*, cli.nombre, cli.id_documento_cli, cli.telefono, mo.id_placa 
             FROM mantenimientos m 
             JOIN motos mo ON m.id_placa = mo.id_placa 
             JOIN clientes cli ON mo.id_documento_cli = cli.id_documento_cli
             WHERE m.id_mantenimientos = ?";
$stmt_main = $conexion->prepare($sql_main);
$stmt_main->bind_param("i", $id_mantenimiento);
$stmt_main->execute();
$mantenimiento = $stmt_main->get_result()->fetch_assoc();

if (!$mantenimiento) { die("No se encontró el mantenimiento."); }

// Obtener detalles del mantenimiento
$sql_details = "SELECT dm.*, tt.detalle FROM detalle_mantenimientos dm
                JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo
                WHERE dm.id_mantenimiento = ?";
$stmt_details = $conexion->prepare($sql_details);
$stmt_details->bind_param("i", $id_mantenimiento);
$stmt_details->execute();
$detalles = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);


$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$nombre_dominio = $_SERVER['HTTP_HOST'];
$ruta_script = $_SERVER['REQUEST_URI'];
$url_publica = $protocolo . $nombre_dominio . $ruta_script;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Mantenimiento #<?php echo $id_mantenimiento; ?></title>
    <link rel="shortcut icon" href="../../img/logo.png" type="image/x-icon">
    <!-- Bootstrap y Font Awesome para los iconos y estilos -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .invoice-container { margin-top: 80px; } /* Espacio para el header fijo */
        .invoice-box { max-width: 800px; margin: 20px auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); background-color: white; }
        .invoice-header { position: fixed; /* Lo hacemos fijo */ top: 0; left: 0; width: 100%; background-color: #343a40; /* Color oscuro para destacar */ padding: 10px; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .observaciones { background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; margin-top: 20px; border-radius: 5px; }
        
        /* Reglas para la impresión */
        @media print { 
            body { background-color: white; margin-top: 0; }
            .invoice-header { display: none !important; } /* Ocultar botones al imprimir */
            .invoice-container { margin-top: 0; }
            .invoice-box { box-shadow: none; border: none; margin: 0; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    
    <!-- Encabezado con botones de acción -->
    <div class="invoice-header text-center">
        <button class="btn btn-light" onclick="window.print();"><i class="fas fa-print"></i> Imprimir</button>
        <button class="btn btn-danger" id="btnDescargarPdf"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
    </div>

    <!-- Contenedor principal para dejar espacio al header fijo -->
    <div class="invoice-container">
        <!-- Contenido de la factura -->
        <div class="invoice-box" id="contenidoFactura">
        <h2 class="text-center">Factura de Mantenimiento #<?php echo $id_mantenimiento; ?></h2>
        <p class="text-center"><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($mantenimiento['fecha_realizo'])); ?></p>
        <hr>
        <div class="row">
            <div class="col-6">
                <h4>Cliente</h4>
                <p>
                    <strong>Nombre:</strong> <?php echo htmlspecialchars($mantenimiento['nombre']); ?><br>
                    <strong>Documento:</strong> <?php echo htmlspecialchars($mantenimiento['id_documento_cli']); ?><br>
                    <strong>Teléfono:</strong> <?php echo htmlspecialchars($mantenimiento['telefono']); ?>
                </p>
            </div>
            <div class="col-6">
                <h4>Moto</h4>
                <p>
                    <strong>Placa:</strong> <?php echo htmlspecialchars($mantenimiento['id_placa']); ?><br>
                    <strong>Kilometraje:</strong> <?php echo number_format($mantenimiento['kilometraje'], 0, ',', '.'); ?> km
                </p>
            </div>
        </div>
        <hr>

        <?php if (!empty($mantenimiento['observaciones_entrada'])): ?>
        <div class="observaciones">
            <h5>Observaciones de Entrada</h5>
            <p><?php echo nl2br(htmlspecialchars($mantenimiento['observaciones_entrada'])); ?></p>
        </div>
        <?php endif; ?>

        <h4 class="mt-4">Detalles del Servicio</h4>
        <table class="table table-bordered">
            <thead class="thead-light"><tr><th>Trabajo Realizado</th><th>Cantidad</th><th class="text-right">Subtotal</th></tr></thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['detalle']); ?></td>
                    <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                    <td class="text-right">$ <?php echo number_format($detalle['subtotal'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($mantenimiento['observaciones_salida'])): ?>
        <div class="observaciones">
            <h5>Observaciones de Salida / Recomendaciones</h5>
            <p><?php echo nl2br(htmlspecialchars($mantenimiento['observaciones_salida'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="text-right mt-4">
            <h3>Total: $ <?php echo number_format($mantenimiento['total'], 2, ',', '.'); ?></h3>
        </div>
    </div>

     <!-- Librerías de JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Descargar como PDF
            document.getElementById('btnDescargarPdf').addEventListener('click', function () {
                const { jsPDF } = window.jspdf;
                const factura = document.getElementById('contenidoFactura');
                const nombreArchivo = `Factura_Mantenimiento_#<?php echo $id_mantenimiento; ?>.pdf`;

                html2canvas(factura, { scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const imgWidth = 210;
                    const pageHeight = 295;
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    let heightLeft = imgHeight;
                    let position = 0;

                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft >= 0) {
                        position = heightLeft - imgHeight;
                        pdf.addPage();
                        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }
                    pdf.save(nombreArchivo);
                });
            });

        });
    </script>
</body>
</html>