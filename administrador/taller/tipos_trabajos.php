<?php 
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

// --- filtrado ---
$filtro_detalle = $_GET['filtro_detalle'] ?? '';
$filtro_cc_min = $_GET['filtro_cc_min'] ?? '';
$filtro_cc_max = $_GET['filtro_cc_max'] ?? '';

$sql = "SELECT * FROM tipo_trabajo";
$where_clauses = [];
$params = [];
$types = '';

if (!empty($filtro_detalle)) {
    $where_clauses[] = "LOWER(detalle) LIKE LOWER(?)";
    $params[] = "%" . $filtro_detalle . "%";
    $types .= 's';
}

if (!empty($filtro_cc_min) && !empty($filtro_cc_max)) {
    // Busca trabajos cuyo rango [cc_inicial, cc_final] se solape con el rango del filtro.
    $where_clauses[] = "(cc_inicial <= ? AND cc_final >= ?)";
    $params[] = $filtro_cc_max;
    $params[] = $filtro_cc_min;
    $types .= 'ii';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY detalle ASC, cc_inicial ASC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Tipos de Trabajo</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Filtrar Trabajos</h6></div>
        <div class="card-body">
            <form id="formFiltros" method="GET" action="tipos_trabajos.php">
                <div class="row">
                    <div class="col-md-5"><label>Buscar por detalle:</label><input type="text" name="filtro_detalle" class="form-control" placeholder="Ej: Cambio de aceite" value="<?php echo htmlspecialchars($filtro_detalle); ?>"></div>
                    <div class="col-md-3"><label>Para CC Desde:</label><input type="number" name="filtro_cc_min" class="form-control" placeholder="Ej: 100" value="<?php echo htmlspecialchars($filtro_cc_min); ?>"></div>
                    <div class="col-md-3"><label>Para CC Hasta:</label><input type="number" name="filtro_cc_max" class="form-control" placeholder="Ej: 250" value="<?php echo htmlspecialchars($filtro_cc_max); ?>"></div>
                    <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-success"><i class="fas fa-filter"></i></button><a href="tipos_trabajos.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i></a></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de la Tabla -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Lista de Trabajos</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableTiposTrabajo" width="100%" cellspacing="0">
                    <thead><tr><th>ID</th><th>Detalle</th><th>Rango Aplicable (CC)</th><th>Precio Unitario</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_tipo']); ?></td>
                            <td><?php echo htmlspecialchars($row['detalle']); ?></td>
                            <td><?php echo htmlspecialchars($row['cc_inicial']) . ' - ' . htmlspecialchars($row['cc_final']); ?> cc</td>
                            <td><?php echo htmlspecialchars($row['precio_unitario']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_tipo']; ?>"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_tipo']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron tipos de trabajo.</div>
                <?php endif; ?>
                <div class="text-center mt-4"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarTipoTrabajo"><i class="fas fa-plus-circle"></i> Agregar Nuevo Trabajo</button></div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES === -->
<!-- Modal para Agregar/Editar Tipo de Trabajo -->
<div class="modal fade" id="modalTipoTrabajo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalTipoTrabajoLabel">Agregar Nuevo Trabajo</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formTipoTrabajo" novalidate>
                <input type="hidden" name="id_tipo" value="0">
                <input type="hidden" name="accion" value="agregar">
                <div class="modal-body">
                    <div class="form-group"><label>Detalle del Trabajo</label><input type="text" class="form-control" name="detalle" required placeholder="Ej: Sincronización Completa"><div class="invalid-feedback"></div></div>
                    <div class="row">
                        <div class="col-md-6 form-group"><label>CC Inicial</label><input type="number" class="form-control" name="cc_inicial" required min="50"><div class="invalid-feedback"></div></div>
                        <div class="col-md-6 form-group"><label>CC Final</label><input type="number" class="form-control" name="cc_final" required min="50"><div class="invalid-feedback"></div></div>
                    </div>
                    <div class="form-group"><label>Precio Unitario</label><input type="number" class="form-control" name="precio_unitario" required step="0.01" min="0"><div class="invalid-feedback"></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Eliminar -->
<div class="modal fade" id="modalEliminarTipoTrabajo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar este tipo de trabajo? Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button></div>
        </div>
    </div>
</div>

<?php include '../scripts.php'; $stmt->close(); $conexion->close(); ?>
<script>
$(document).ready(function() {
    $('#dataTableTiposTrabajo').DataTable({
        "processing": true,
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ tipos de trabajo",
            "sZeroRecords":    "No se encontraron tipos de trabajo",
            "sEmptyTable":     "No hay tipos de trabajo registrados",
            "sInfo":           "Mostrando tipos de trabajo del _START_ al _END_ de un total de _TOTAL_",
            "sInfoEmpty":      "Mostrando 0 tipos de trabajo",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ tipos de trabajo)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar trabajo:",
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
                    "1": "Copiado 1 tipo de trabajo al portapapeles",
                    "_": "Copiados %d tipos de trabajo al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todos los tipos de trabajo",
                    "_": "Mostrar %d tipos de trabajo"
                },
                "pdf": "PDF",
                "print": "Imprimir"
            }
        },
        "pageLength": 10,
        "order": [[1, 'asc']], // Ordenar por detalle del trabajo
        "columnDefs": [
            {
                "targets": -1, // Última columna (acciones)
                "orderable": false,
                "searchable": false
            },
            {
                "targets": 3, // Columna de precio
                "render": function(data, type, row) {
                    return '$ ' + parseFloat(data).toLocaleString('es-CO', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        ],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "responsive": true,
        "stateSave": true,
        "autoWidth": false,
        "initComplete": function(settings, json) {
            // Personalización adicional después de la inicialización
            $('.dataTables_filter input').attr('placeholder', 'Buscar por detalle o rango...');
        }
    });
});
</script>
<script src="../js/tipos_trabajos.js"></script>