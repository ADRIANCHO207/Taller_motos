<?php 
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

// ---filtrado ---
$filtro_busqueda = $_GET['filtro_busqueda'] ?? '';
$sql = "SELECT rm.id_referencia, rm.referencia_marca, m.marcas 
        FROM referencia_marca rm
        JOIN marcas m ON rm.id_marcas = m.id_marca";

if (!empty($filtro_busqueda)) {
    $sql .= " WHERE rm.referencia_marca LIKE ? OR m.marcas LIKE ?";
}
$sql .= " ORDER BY m.marcas ASC, rm.referencia_marca ASC";

$stmt = $conexion->prepare($sql);
if (!empty($filtro_busqueda)) {
    $like_busqueda = "%" . $filtro_busqueda . "%";
    $stmt->bind_param("ss", $like_busqueda, $like_busqueda);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Obtener todas las marcas para los menús desplegables
$marcas_result = $conexion->query("SELECT id_marca, marcas FROM marcas ORDER BY marcas ASC");
$marcas = [];
while ($fila = $marcas_result->fetch_assoc()) {
    $marcas[] = $fila;
}
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Referencias de Marcas de Motos</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Filtrar Referencias</h6></div>
        <div class="card-body">
            <form method="GET" action="referencias_marcas.php">
                <div class="row">
                    <div class="col-md-8">
                        <label>Buscar por nombre de referencia o marca:</label>
                        <input type="text" name="filtro_busqueda" class="form-control" placeholder="Ej: NMAX o Yamaha" value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="referencias_marcas.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de la Tabla de Referencias -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Lista de Referencias</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableReferencias" width="100%" cellspacing="0">
                    <thead><tr><th>#</th><th>Marca</th><th>Referencia</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_referencia']); ?></td>
                            <td><?php echo htmlspecialchars($row['marcas']); ?></td>
                            <td><?php echo htmlspecialchars($row['referencia_marca']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_referencia']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_referencia']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron referencias.</div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarReferencia">
                        <i class="fas fa-plus-circle"></i> Agregar Nueva Referencia
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES (Agregar, Editar, Eliminar) === -->
<!-- Modal para Agregar Referencia -->
<div class="modal fade" id="modalAgregarReferencia" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Agregar Nueva Referencia</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formAgregarReferencia" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add_id_marcas">Marca</label>
                        <select class="form-control" name="id_marcas" required>
                            <option value="">-- Seleccione una marca --</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?php echo $marca['id_marca']; ?>"><?php echo htmlspecialchars($marca['marcas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar una marca.</div>
                    </div>
                    <div class="form-group">
                        <label for="add_referencia_marca">Nombre de la Referencia</label>
                        <input type="text" class="form-control" name="referencia_marca" required placeholder="Ej: FZ-16">
                        <div class="invalid-feedback">El nombre de la referencia es obligatorio.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Referencia -->
<div class="modal fade" id="modalEditarReferencia" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Referencia</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formEditarReferencia" novalidate>
                <input type="hidden" name="id_referencia">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_id_marcas">Marca</label>
                        <select class="form-control" name="id_marcas" required>
                            <option value="">-- Seleccione una marca --</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?php echo $marca['id_marca']; ?>"><?php echo htmlspecialchars($marca['marcas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar una marca.</div>
                    </div>
                    <div class="form-group">
                        <label for="edit_referencia_marca">Nombre de la Referencia</label>
                        <input type="text" class="form-control" name="referencia_marca" required>
                        <div class="invalid-feedback">El nombre de la referencia es obligatorio.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="modalEliminarReferencia" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar esta referencia? Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button></div>
        </div>
    </div>
</div>

<?php include '../scripts.php'; $stmt->close(); $conexion->close(); ?>
<script>
$(document).ready(function() {
    $('#dataTableReferencias').DataTable({
        "processing": true,
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ referencias",
            "sZeroRecords":    "No se encontraron referencias",
            "sEmptyTable":     "No hay referencias registradas",
            "sInfo":           "Mostrando referencias del _START_ al _END_ de un total de _TOTAL_ referencias",
            "sInfoEmpty":      "Mostrando 0 referencias",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ referencias)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar referencia:",
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
                    "1": "Copiada 1 referencia al portapapeles",
                    "_": "Copiadas %d referencias al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todas las referencias",
                    "_": "Mostrar %d referencias"
                },
                "pdf": "PDF",
                "print": "Imprimir"
            }
        },
        "pageLength": 10,
        "order": [[1, 'asc'], [2, 'asc']], // Ordenar por marca y luego por referencia
        "columnDefs": [{
            "targets": -1, // Última columna (acciones)
            "orderable": false,
            "searchable": false
        }],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "responsive": true,
        "stateSave": true
    });
});
</script>
<script src="../js/referencias.js"></script>