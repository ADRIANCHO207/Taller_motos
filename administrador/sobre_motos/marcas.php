<?php 
// --- control/marcas.php (FILTRO CORREGIDO) ---
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

$filtro_nombre = $_GET['filtro_nombre'] ?? '';

$sql = "SELECT * FROM marcas"; 
if (!empty($filtro_nombre)) {
    // ¡AQUÍ ESTÁ LA MAGIA! Usamos LOWER() en ambos lados de la comparación.
    $sql .= " WHERE LOWER(marcas) LIKE LOWER(?)"; 
}
$sql .= " ORDER BY marcas ASC";

$stmt = $conexion->prepare($sql);
if (!empty($filtro_nombre)) {
    // No hay cambios aquí, el valor sigue siendo el mismo.
    $like_nombre = "%" . $filtro_nombre . "%";
    $stmt->bind_param("s", $like_nombre);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>


<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Marcas de Motos</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Marcas</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="marcas.php">
                <div class="row">
                    <div class="col-md-8">
                        <label for="filtro_nombre">Buscar por nombre:</label>
                        <input type="text" id="filtro_nombre" name="filtro_nombre" class="form-control" placeholder="Ej: Yamaha" value="<?php echo htmlspecialchars($filtro_nombre); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="marcas.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de la Tabla de Marcas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Marcas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableMarcas" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre de la Marca</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_marca']); ?></td>
                            <td><?php echo htmlspecialchars($row['marcas']); // Cambiado de 'marcas' a 'marca' ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_marca']; ?>" data-valor="<?php echo htmlspecialchars($row['marcas']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_marca']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron marcas.</div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarMarca">
                        <i class="fas fa-plus-circle"></i> Agregar Nueva Marca
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES (Agregar, Editar, Eliminar) === -->
<!-- Modal para Agregar Marca -->
<div class="modal fade" id="modalAgregarMarca" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Agregar Nueva Marca</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formAgregarMarca" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="marca">Nombre de la Marca</label>
                        <input type="text" class="form-control" name="marca" required placeholder="Ej: Honda">
                        <div class="invalid-feedback">El nombre de la marca es obligatorio y solo puede contener letras.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Marca -->
<div class="modal fade" id="modalEditarMarca" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Marca</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formEditarMarca" novalidate>
                <input type="hidden" name="id_marca">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_marca">Nombre de la Marca</label>
                        <input type="text" class="form-control" name="marca" required>
                        <div class="invalid-feedback">El nombre de la marca es obligatorio y solo puede contener letras.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="modalEliminarMarca" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar esta marca? Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button></div>
        </div>
    </div>
</div>

<?php include '../scripts.php'; $stmt->close(); $conexion->close(); ?>
<script>
    $(document).ready(function() {
        $('#dataTableMarcas').DataTable({
            "processing": true,
            "language": {
                "sProcessing":     "Procesando...",
                "sLengthMenu":     "Mostrar _MENU_ marcas",
                "sZeroRecords":    "No se encontraron marcas",
                "sEmptyTable":     "No hay marcas registradas",
                "sInfo":           "Mostrando marcas del _START_ al _END_ de un total de _TOTAL_ marcas",
                "sInfoEmpty":      "Mostrando 0 marcas",
                "sInfoFiltered":   "(filtrado de un total de _MAX_ marcas)",
                "sInfoPostFix":    "",
                "sSearch":         "Buscar marca:",
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
                        "1": "Copiada 1 marca al portapapeles",
                        "_": "Copiadas %d marcas al portapapeles"
                    },
                    "copyTitle": "Copiar al portapapeles",
                    "csv": "CSV",
                    "excel": "Excel",
                    "pageLength": {
                        "-1": "Mostrar todas las marcas",
                        "_": "Mostrar %d marcas"
                    },
                    "pdf": "PDF",
                    "print": "Imprimir"
                }
            },
            "pageLength": 10,
            "order": [[1, 'asc']], // Ordenar por nombre de marca ascendente
            "columnDefs": [{
                "targets": -1, // Última columna (acciones)
                "orderable": false,
                "searchable": false
            }],
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });
    });
</script>
<script src="../js/marcas.js"></script>