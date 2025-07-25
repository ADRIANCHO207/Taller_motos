<?php 
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

// --- filtrado por rango de cilindraje ---
$filtro_min = $_GET['filtro_min'] ?? '';
$filtro_max = $_GET['filtro_max'] ?? '';

$sql = "SELECT * FROM cilindraje";
$params = [];
$types = '';

// Validar y construir la cláusula WHERE solo si los filtros son válidos
if (!empty($filtro_min) && !empty($filtro_max)) {
    // Convertimos a enteros para una validación segura
    $min = intval($filtro_min);
    $max = intval($filtro_max);

    // Verificación de seguridad en el backend
    if ($min >= 50 && $max <= 2000 && $min <= $max) {
        $sql .= " WHERE cilindraje BETWEEN ? AND ?";
        $params[] = $min;
        $params[] = $max;
        $types = "ii";
    }
}

$sql .= " ORDER BY cilindraje ASC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Cilindrajes de Motos</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrar Cilindrajes</h6>
        </div>
        <div class="card-body">
            <form id="formFiltros" method="GET" action="cilindraje.php">
                <div class="row">
                    <div class="col-md-4">
                        <label for="filtro_min">Desde (cc):</label>
                        <input type="number" id="filtro_min" name="filtro_min" class="form-control" placeholder="Ej: 100" value="<?php echo htmlspecialchars($filtro_min); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="filtro_max">Hasta (cc):</label>
                        <input type="number" id="filtro_max" name="filtro_max" class="form-control" placeholder="Ej: 200" value="<?php echo htmlspecialchars($filtro_max); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="cilindraje.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de la Tabla de Cilindrajes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Cilindrajes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableCilindraje" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cilindraje (cc)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_cc']); ?></td>
                            <td><?php echo htmlspecialchars($row['cilindraje']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_cc']; ?>" data-valor="<?php echo $row['cilindraje']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_cc']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron cilindrajes.</div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCilindraje">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Cilindraje
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES (Agregar, Editar, Eliminar) === -->
<!-- Modal para Agregar Cilindraje -->
<div class="modal fade" id="modalAgregarCilindraje" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Agregar Nuevo Cilindraje</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formAgregarCilindraje" novalidate>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="cilindraje">Cilindraje (cc)</label>
                        <input type="number" class="form-control" name="cilindraje" required min="50" max="2000" placeholder="Ej: 150">
                        <div class="invalid-feedback">Debe ser un número entre 50 y 2000.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Cilindraje -->
<div class="modal fade" id="modalEditarCilindraje" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Cilindraje</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formEditarCilindraje" novalidate>
                <input type="hidden" name="id_cc">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_cilindraje">Cilindraje (cc)</label>
                        <input type="number" class="form-control" name="cilindraje" required min="50" max="2000">
                        <div class="invalid-feedback">Debe ser un número entre 50 y 2000.</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="modalEliminarCilindraje" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar este cilindraje? Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button></div>
        </div>
    </div>
</div>

<?php include '../scripts.php'; $stmt->close(); $conexion->close(); ?>
<script>
$(document).ready(function() {
    $('#dataTableCilindraje').DataTable({
        "processing": true,
        "language": {
            "processing": "Procesando...",
            "search": "Buscar Cilindrajes:",
            "lengthMenu": "Mostrar _MENU_ registros",
            "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "infoPostFix": "",
            "loadingRecords": "Cargando...",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "No hay cilindrajes registrados",
            "paginate": {
                "first": "Primero",
                "previous": "Anterior",
                "next": "Siguiente",
                "last": "Último"
            },
            "aria": {
                "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sortDescending": ": Activar para ordenar la columna de manera descendente"
            },
            "buttons": {
                "copy": "Copiar",
                "colvis": "Visibilidad",
                "collection": "Colección",
                "colvisRestore": "Restaurar visibilidad",
                "copyKeys": "Presione ctrl o u2318 + C para copiar los datos de la tabla al portapapeles. <br><br>Para cancelar, haga clic en este mensaje o presione escape.",
                "copySuccess": {
                    "1": "Copiada 1 fila al portapapeles",
                    "_": "Copiadas %d filas al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todas las filas",
                    "_": "Mostrar %d filas"
                },
                "pdf": "PDF",
                "print": "Imprimir",
                "renameState": "Cambiar nombre",
                "updateState": "Actualizar"
            }
        },
        "pageLength": 10,
        "order": [[1, 'asc']], // Ordenar por cilindraje ascendente
        "columnDefs": [{
            "targets": -1, // Última columna (acciones)
            "orderable": false,
            "searchable": false
        }]
    });
});
</script>
<script src="../js/cilindraje.js"></script>