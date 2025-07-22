<?php 
// --- control/mantenimientos.php ---
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

// --- Obtener datos para los menús desplegables ---
$motos_result = $conexion->query("SELECT mo.id_placa, cli.nombre FROM motos mo JOIN clientes cli ON mo.id_documento_cli = cli.id_documento_cli ORDER BY mo.id_placa ASC");
$tipos_trabajo = $conexion->query("SELECT id_tipo, detalle, precio_unitario FROM tipo_trabajo ORDER BY detalle ASC")->fetch_all(MYSQLI_ASSOC);

// --- Lógica de filtrado ---
$filtro_placa = $_GET['filtro_placa'] ?? '';
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fin = $_GET['filtro_fin'] ?? '';

$sql = "SELECT m.*, c.nombre AS nombre_cliente FROM mantenimientos m 
        JOIN motos mo ON m.id_placa = mo.id_placa
        JOIN clientes c ON mo.id_documento_cli = c.id_documento_cli";

$where = []; $params = []; $types = '';
if (!empty($filtro_placa)) { $where[] = "m.id_placa LIKE ?"; $params[] = "%" . $filtro_placa . "%"; $types .= 's'; }
if (!empty($filtro_inicio) && !empty($filtro_fin)) { $where[] = "m.fecha_realizo BETWEEN ? AND ?"; $params[] = $filtro_inicio; $params[] = date('Y-m-d 23:59:59', strtotime($filtro_fin)); $types .= 'ss'; }
if (!empty($where)) { $sql .= " WHERE " . implode(' AND ', $where); }
$sql .= " ORDER BY m.fecha_realizo DESC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Mantenimientos</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Filtrar Mantenimientos</h6></div>
        <div class="card-body">
            <form id="formFiltros" method="GET" action="mantenimientos.php">
                <div class="row">
                    <div class="col-md-4"><label>Placa:</label><input type="text" name="filtro_placa" class="form-control" placeholder="ABC12D" value="<?php echo htmlspecialchars($filtro_placa); ?>"></div>
                    <div class="col-md-3"><label>Fecha Inicio:</label><input type="date" name="filtro_inicio" class="form-control" value="<?php echo htmlspecialchars($filtro_inicio); ?>"></div>
                    <div class="col-md-3"><label>Fecha Fin:</label><input type="date" name="filtro_fin" class="form-control" value="<?php echo htmlspecialchars($filtro_fin); ?>"></div>
                    <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button><a href="mantenimientos.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i></a></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Mantenimientos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Historial de Mantenimientos</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTableMantenimientos" width="100%" cellspacing="0">
                    <thead><tr><th>#</th><th>Placa</th><th>Cliente</th><th>Fecha</th><th>Kilometraje</th><th>Total</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr data-obs-entrada="<?php echo htmlspecialchars($row['observaciones_entrada']); ?>" data-obs-salida="<?php echo htmlspecialchars($row['observaciones_salida']); ?>">
                            <td><?php echo $row['id_mantenimientos']; ?></td>
                            <td><?php echo htmlspecialchars($row['id_placa']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_cliente']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_realizo'])); ?></td>
                            <td><?php echo number_format($row['kilometraje'], 0, ',', '.'); ?> km</td>
                            <td>$ <?php echo number_format($row['total'], 2, ',', '.'); ?></td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm btn-ver-detalles" data-id="<?php echo $row['id_mantenimientos']; ?>" title="Ver Detalles"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_mantenimientos']; ?>" title="Editar Mantenimiento"><i class="fas fa-edit"></i></button>
                                <a href="../facturas/factura.php?id=<?php echo $row['id_mantenimientos']; ?>" class="btn btn-primary btn-sm" title="Ver Factura" target="_blank"><i class="fas fa-file-invoice"></i></a>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_mantenimientos']; ?>" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="text-center mt-4"><button type="button" class="btn btn-success" data-toggle="modal" data-target="#modalAgregarMantenimiento"><i class="fas fa-plus-circle"></i> Registrar Nuevo Mantenimiento</button></div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES === -->
<!-- Modal para Agregar Mantenimiento -->
<div class="modal fade" id="modalMantenimiento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalMantenimientoLabel">Registrar Mantenimiento</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formMantenimiento" novalidate>
                <input type="hidden" name="id_mantenimientos" value="0">
                <input type="hidden" name="accion" value="agregar">
                <div class="modal-body">
                    <div class="row">
                        <!-- Columna Izquierda: Datos y Detalles -->
                        <div class="col-lg-8">
                            <h6>1. Datos Generales de la Moto</h6>
                            <div class="card p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-5 form-group"><label>Seleccione Placa y Dueño</label>
                                        <select class="form-control" name="id_placa" required>
                                            <option value="">-- Seleccione una moto --</option>
                                            <?php while($moto = $motos_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($moto['id_placa']); ?>"><?php echo htmlspecialchars($moto['id_placa']) . ' - ' . htmlspecialchars($moto['nombre']); ?></option>
                                            <?php endwhile; ?>
                                        </select><div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-4 form-group"><label>Fecha Realizado</label><input type="datetime-local" class="form-control" name="fecha_realizo" required><div class="invalid-feedback"></div></div>
                                    <div class="col-md-3 form-group"><label>Kilometraje</label><input type="number" class="form-control" name="kilometraje" placeholder="Ej: 25000" required><div class="invalid-feedback"></div></div>
                                </div>
                            </div>
                            <h6>2. Trabajos Realizados</h6>
                            <div class="card p-3">
                                <div class="row align-items-end">
                                    <!-- ¡NUEVO BUSCADOR! -->
                                    <div class="col-md-7 form-group">
                                        <label>Buscar tipo de trabajo (mín. 2 letras)</label>
                                        <input type="text" class="form-control" id="inputBuscarTrabajo" placeholder="Ej: Cambio de...">
                                        <div id="resultadosBusqueda" class="list-group mt-1 position-absolute w-100" style="z-index: 1050;"></div>
                                    </div>
                                    <div class="col-md-3 form-group"><label>Cantidad</label><input type="number" class="form-control" id="inputCantidad" value="1" min="1"></div>
                                    <div class="col-md-2 form-group"><button type="button" class="btn btn-info btn-block" id="btnAnadirTrabajo" disabled>Añadir</button></div>
                                </div>
                                <table class="table table-sm mt-3">
                                    <thead><tr><th>Trabajo</th><th>Cant.</th><th>P. Unitario</th><th>Subtotal</th><th></th></tr></thead>
                                    <tbody id="tablaDetallesAgregados">
                                        <!-- Filas de trabajos se añadirán aquí con JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Columna Derecha: Observaciones y Totales -->
                        <div class="col-lg-4">
                            <h6>3. Observaciones</h6>
                            <div class="card p-3 mb-3">
                                <div class="form-group"><label>Observaciones Iniciales</label><textarea class="form-control" name="observaciones_entrada" rows="4"></textarea></div>
                                <div class="form-group"><label>Observaciones Finales</label><textarea class="form-control" name="observaciones_salida" rows="4"></textarea></div>
                            </div>
                            <h6>4. Resumen de Pago</h6>
                            <div class="card p-3 bg-light">
                                <h3 class="text-right">Total: <span id="textoTotal">$ 0.00</span></h3>
                                <input type="hidden" name="total" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar Mantenimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="modalVerDetalles" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Detalles del Mantenimiento</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body">
                <h6>Información General:</h6>
                <p id="infoGeneralMantenimiento"></p>
                <h6>Observaciones:</h6>
                <div class="card p-3 mb-3">
                    <strong>Entrada:</strong> <p id="obsEntrada" class="ml-2"></p>
                    <strong>Salida:</strong> <p id="obsSalida" class="ml-2"></p>
                </div>
                <h6>Trabajos Realizados:</h6>
                <table class="table table-sm">
                    <thead><tr><th>Detalle</th><th>Cant.</th><th>Subtotal</th></tr></thead>
                    <tbody id="detallesBody"></tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<!-- Modal para Eliminar -->
<div class="modal fade" id="modalEliminarMantenimiento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este Mantenimiento? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>


<?php include '../scripts.php'; $stmt->close(); $conexion->close(); ?>
<script>
$(document).ready(function() {
    $('#dataTableMantenimientos').DataTable({
        // --- CONFIGURACIÓN DE IDIOMA Y OTRAS OPCIONES (sin cambios) ---
        "language": { "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ mantenimientos",
            "sZeroRecords":    "No se encontraron mantenimientos",
            "sEmptyTable":     "No hay mantenimientos registrados",
            "sInfo":           "Mostrando mantenimientos del _START_ al _END_ de un total de _TOTAL_",
            "sInfoEmpty":      "Mostrando 0 mantenimientos",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ mantenimientos)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar mantenimiento:",
            "sUrl":           "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            },
            "buttons": {
                "copy": "Copiar",
                "colvis": "Visibilidad",
                "collection": "Colección",
                "colvisRestore": "Restaurar visibilidad",
                "copyKeys": "Presione ctrl o u2318 + C para copiar los datos de la tabla al portapapeles. <br><br>Para cancelar, haga clic en este mensaje o presione escape.",
                "copySuccess": {
                    "1": "Copiado 1 mantenimiento al portapapeles",
                    "_": "Copiados %d mantenimientos al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todos los mantenimientos",
                    "_": "Mostrar %d mantenimientos"
                },
                "pdf": "PDF",
                "print": "Imprimir"
            }
        },
        "pageLength": 10,
        "order": [[3, 'desc']], // Ordenar por fecha descendente

        // --- ¡AQUÍ ESTÁ LA SOLUCIÓN! ---
        "columnDefs": [
            {
                // Columna de Acciones (índice -1 desde el final)
                "targets": -1, 
                "orderable": false,
                "searchable": false
            },
            {
                // Columna del Total (índice 5)
                "targets": 5,
                // Le decimos a la tabla que no ajuste automáticamente el ancho de esta columna
                "width": "15%", 
                // Renderizamos el formato de moneda correctamente
                "render": function(data, type, row) {
                    // Solo renderizamos para la vista ('display'), para ordenar usa el número crudo
                    if (type === 'display') {
                        // El 'data' ya viene del PHP con el formato correcto, no es necesario re-formatear
                        return data; 
                    }
                    return data;
                }
            },
            {
                // Columna del Kilometraje (índice 4)
                "targets": 4,
                // Le damos un ancho fijo para evitar que se corte
                "width": "15%",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        return data;
                    }
                    return data;
                }
            },
            {
                // Columna ID (índice 0)
                "targets": 0,
                // La hacemos más angosta
                "width": "5%"
            }
        ],

        // --- OTRAS CONFIGURACIONES (sin cambios) ---
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "responsive": true,
        "autoWidth": false // Deshabilitamos el ajuste automático de ancho global
    });
});
</script>
<script src="../js/mantenimientos.js"></script>