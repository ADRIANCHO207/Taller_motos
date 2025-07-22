<?php 
// --- control/colores.php ---
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

// --- Lógica de filtrado por nombre de color ---
$filtro_nombre = $_GET['filtro_nombre'] ?? '';

$sql = "SELECT * FROM color"; // Asegúrate que tu tabla se llame 'color'
if (!empty($filtro_nombre)) {
    // Búsqueda case-insensitive
    $sql .= " WHERE LOWER(color) LIKE LOWER(?)"; 
}
$sql .= " ORDER BY color ASC";

$stmt = $conexion->prepare($sql);
if (!empty($filtro_nombre)) {
    $like_nombre = "%" . $filtro_nombre . "%";
    $stmt->bind_param("s", $like_nombre);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Colores</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Colores</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="colores.php">
                <div class="row">
                    <div class="col-md-8">
                        <label for="filtro_nombre">Buscar por nombre:</label>
                        <input type="text" id="filtro_nombre" name="filtro_nombre" class="form-control" placeholder="Ej: Rojo" value="<?php echo htmlspecialchars($filtro_nombre); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="colores.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de la Tabla de Colores -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Colores</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableColores" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre del Color</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_color']); ?></td>
                            <td><?php echo htmlspecialchars($row['color']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_color']; ?>" data-valor="<?php echo htmlspecialchars($row['color']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_color']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron colores.</div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarColor">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Color
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES (Agregar, Editar, Eliminar) === -->
<!-- Modal para Agregar Color -->
<div class="modal fade" id="modalAgregarColor" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Agregar Nuevo Color</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formAgregarColor" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="color">Nombre del Color</label>
                        <input type="text" class="form-control" name="color" required placeholder="Ej: Azul Eléctrico">
                        <div class="invalid-feedback">El nombre del color es obligatorio y solo puede contener letras y espacios.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Color -->
<div class="modal fade" id="modalEditarColor" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Color</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formEditarColor" novalidate>
                <input type="hidden" name="id_color">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_color">Nombre del Color</label>
                        <input type="text" class="form-control" name="color" required>
                        <div class="invalid-feedback">El nombre del color es obligatorio y solo puede contener letras y espacios.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="modalEliminarColor" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar este color? Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button></div>
        </div>
    </div>
</div>

<?php include '../scripts.php'; $stmt->close(); $conexion->close(); ?>
<script>
    $(document).ready(function() {
        $('#dataTableColores').DataTable({
            "processing": true,
            "language": {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ colores",
                "sZeroRecords":    "No se encontraron colores",
                "sEmptyTable":     "No hay colores registrados",
                "sInfo":           "Mostrando colores del _START_ al _END_ de un total de _TOTAL_ colores",
                "sInfoEmpty":      "Mostrando 0 colores",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ colores)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar color:",
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
                        "1": "Copiado 1 color al portapapeles",
                        "_": "Copiados %d colores al portapapeles"
                    },
                    "copyTitle": "Copiar al portapapeles",
                    "csv": "CSV",
                    "excel": "Excel",
                    "pageLength": {
                        "-1": "Mostrar todos los colores",
                        "_": "Mostrar %d colores"
                    },
                    "pdf": "PDF",
                    "print": "Imprimir"
                }
            },
            "pageLength": 10,
            "order": [[1, 'asc']], // Ordenar por nombre del color
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
<script src="../js/colores.js"></script>