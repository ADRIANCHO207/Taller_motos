<?php 
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

// --- filtrado por rango de años ---
$filtro_inicio = $_GET['filtro_inicio'] ?? '';
$filtro_fin = $_GET['filtro_fin'] ?? '';

$sql = "SELECT * FROM modelos"; // Asegúrate que tu tabla se llame 'modelos'
if (!empty($filtro_inicio) && !empty($filtro_fin) && is_numeric($filtro_inicio) && is_numeric($filtro_fin)) {
    $sql .= " WHERE anio BETWEEN ? AND ?";
}
$sql .= " ORDER BY anio DESC";

$stmt = $conexion->prepare($sql);
if (!empty($filtro_inicio) && !empty($filtro_fin)) {
    $stmt->bind_param("ii", $filtro_inicio, $filtro_fin);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Modelos (Años) de Motos</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Modelos por Año</h6>
        </div>
        <div class="card-body">
            <form id="formFiltros" method="GET" action="modelos.php">
                <div class="row">
                    <div class="col-md-4">
                        <label for="filtro_inicio">Desde el año:</label>
                        <input type="number" id="filtro_inicio" name="filtro_inicio" class="form-control" placeholder="Ej: 2000" value="<?php echo htmlspecialchars($filtro_inicio); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="filtro_fin">Hasta el año:</label>
                        <input type="number" id="filtro_fin" name="filtro_fin" class="form-control" placeholder="Ej: 2026" value="<?php echo htmlspecialchars($filtro_fin); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="modelos.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de la Tabla de Modelos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Modelos</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableAnios" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Año del Modelo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_modelo']); ?></td>
                            <td><?php echo htmlspecialchars($row['anio']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_modelo']; ?>" data-valor="<?php echo $row['anio']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_modelo']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron modelos.</div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarModelo">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Modelo (Año)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES (Agregar, Editar, Eliminar) === -->
<!-- Modal para Agregar Modelo -->
<div class="modal fade" id="modalAgregarModelo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Agregar Nuevo Modelo</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formAgregarModelo" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="anio">Año del Modelo</label>
                        <input type="number" class="form-control" name="anio" required placeholder="Ej: 2024">
                        <div class="invalid-feedback">Debe ser un año válido (ej: 2024).</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Modelo -->
<div class="modal fade" id="modalEditarModelo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Modelo</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formEditarModelo" novalidate>
                <input type="hidden" name="id_modelo">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_anio">Año del Modelo</label>
                        <input type="number" class="form-control" name="anio" required>
                        <div class="invalid-feedback">Debe ser un año válido (ej: 2024).</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="modalEliminarModelo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar este modelo? Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button></div>
        </div>
    </div>
</div>

<?php include '../scripts.php'; $stmt->close(); $conexion->close(); ?>
<script>
$(document).ready(function() {
    $('#dataTableAnios').DataTable({
        "processing": true,
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ modelos",
            "sZeroRecords":    "No se encontraron modelos",
            "sEmptyTable":     "No hay años/modelos registrados",
            "sInfo":           "Mostrando modelos del _START_ al _END_ de un total de _TOTAL_ modelos",
            "sInfoEmpty":      "Mostrando 0 modelos",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ modelos)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar año:",
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
                    "1": "Copiado 1 modelo al portapapeles",
                    "_": "Copiados %d modelos al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todos los modelos",
                    "_": "Mostrar %d modelos"
                },
                "pdf": "PDF",
                "print": "Imprimir"
            }
        },
        "pageLength": 10,
        "order": [[1, 'desc']], // Ordenar por año descendente
        "columnDefs": [
            {
                "targets": -1, // Última columna (acciones)
                "orderable": false,
                "searchable": false
            }
        ],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "responsive": true,
        "stateSave": true,
        "autoWidth": false
    });
});
</script>
<script src="../js/modelos.js"></script>