<?php 
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }


// Recoger todos los posibles filtros
$busqueda = $_GET['busqueda'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Construir la consulta base y la cláusula WHERE
$sql = "SELECT * FROM clientes";
$where_clauses = [];
$params = [];
$types = '';

// Añadir filtro por búsqueda (nombre o documento)
if (!empty($busqueda)) {
    $where_clauses[] = "(nombre LIKE ? OR id_documento_cli LIKE ?)";
    $like_busqueda = "%" . $busqueda . "%";
    $params[] = $like_busqueda;
    $params[] = $like_busqueda;
    $types .= 'ss';
}

// Añadir filtro por rango de fechas
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    // Validar que la fecha de inicio no sea posterior a la fecha de fin
    if ($fecha_inicio <= $fecha_fin) {
        $where_clauses[] = "fecha_creacion BETWEEN ? AND ?";
        $fecha_fin_ajustada = date('Y-m-d 23:59:59', strtotime($fecha_fin));
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin_ajustada;
        $types .= 'ss';
    }
}

// Unir todas las cláusulas WHERE si existen
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY fecha_creacion DESC";

// Ejecutar la consulta con sentencias preparadas para seguridad
$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Clientes</h1>

    <div class="card-body">
        <form id="formFiltros" method="GET" action="clientes.php">
            <div class="row">

                <div class="col-md-4 mb-3">
                    <label for="busqueda">Buscar por Nombre o Documento:</label>
                    <input type="text" id="busqueda" name="busqueda" class="form-control" placeholder="Escribe aquí..." value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($_GET['fecha_inicio'] ?? ''); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($_GET['fecha_fin'] ?? ''); ?>">
                </div>

                <div class="col-md-2 d-flex align-items-end mb-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="clientes.php" class="btn btn-secondary ml-2" title="Limpiar filtros"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tarjeta de la Tabla de Clientes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Clientes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableClientes" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Fecha ingreso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_documento_cli']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['direccion']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha_ingreso']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_documento_cli']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_documento_cli']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron clientes con los filtros aplicados.</div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCliente">
                        <i class="fas fa-user-plus"></i> Agregar Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES (Agregar, Editar, Eliminar) === -->
<!-- Modal para Agregar Cliente -->
<div class="modal fade" id="modalAgregarCliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Agregar Nuevo Cliente</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formAgregarCliente" novalidate>
                <div class="modal-body">
                    <div class="form-group"><label>Documento</label><input type="number" class="form-control" name="documento" required><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Nombre Completo</label><input type="text" class="form-control" name="nombre" required><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Teléfono</label><input type="number" class="form-control" name="telefono" required><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Email (opcional)</label><input type="email" class="form-control" name="email"><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Dirección (opcional)</label><input type="text" class="form-control" name="direccion"><div class="invalid-feedback"></div></div>
                    <div class="form-group">
                        <label>Fecha Ingreso Cliente</label>
                        <input type="datetime-local" class="form-control" name="fecha_ingreso" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Cliente</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formEditarCliente" novalidate>
                <input type="hidden" name="id_documento_cli">
                <div class="modal-body">
                    <div class="form-group"><label>Documento</label><input type="text" class="form-control" id="edit_documento_display" readonly></div>
                    <div class="form-group"><label>Nombre Completo</label><input type="text" class="form-control" name="nombre" required><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Teléfono</label><input type="number" class="form-control" name="telefono" required><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Email (opcional)</label><input type="email" class="form-control" name="email"><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Dirección (opcional)</label><input type="text" class="form-control" name="direccion"><div class="invalid-feedback"></div></div>
                    <div class="form-group"><label>Fecha Ingreso Cliente</label><input type="datetime-local" class="form-control" name="fecha_ingreso"><div class="invalid-feedback"></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="modalEliminarCliente" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar a este cliente? Se eliminarán también todas sus motos y mantenimientos asociados. Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" id="btnConfirmarEliminarCliente" class="btn btn-danger">Eliminar</button></div>
        </div>
    </div>
</div>

<?php
// Incluimos el pie de página y los scripts
include '../scripts.php';


$conexion->close();
?>

<script>
$(document).ready(function() {
    var table = $('#dataTableClientes').DataTable({
        "processing": true,
        "language": {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron resultados",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        "pageLength": 10,
        "order": [[5, 'desc']], // Ordenar por fecha de ingreso
        "columnDefs": [
            {
                "targets": -1, // Última columna (acciones)
                "orderable": false,
                "searchable": false
            }
        ],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
    });

    // Manejar filtros de fecha
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var min = $('#fecha_inicio').val();
        var max = $('#fecha_fin').val();
        var fecha = data[5]; // Índice de la columna fecha

        if (!min && !max) return true;
        
        var fechaCompara = moment(fecha, 'YYYY-MM-DD HH:mm:ss');
        
        if (min && !max && fechaCompara.isSameOrAfter(moment(min))) return true;
        if (!min && max && fechaCompara.isSameOrBefore(moment(max))) return true;
        if (min && max && fechaCompara.isBetween(moment(min), moment(max), 'day', '[]')) return true;
        
        return false;
    });

    // Aplicar filtros al hacer clic en el botón
    $('#filtrar').on('click', function(e) {
        e.preventDefault();
        table.draw();
    });

    // Limpiar filtros
    $('#limpiarFiltros').on('click', function(e) {
        e.preventDefault();
        $('#fecha_inicio').val('');
        $('#fecha_fin').val('');
        table.draw();
    });
});
</script>

<script src="../js/clientes.js"></script>