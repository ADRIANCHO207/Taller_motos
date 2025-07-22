<?php
// --- control/motos.php ---
include '../header.php'; 
$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) { die("Error de conexión: " . $conexion->connect_error); }

// --- Obtener datos para los menús desplegables de filtros y modales ---
$clientes = $conexion->query("SELECT id_documento_cli, nombre FROM clientes ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
$cilindrajes = $conexion->query("SELECT id_cc, cilindraje FROM cilindraje ORDER BY cilindraje ASC")->fetch_all(MYSQLI_ASSOC);
$referencias = $conexion->query("SELECT rm.id_referencia, CONCAT(m.marcas, ' - ', rm.referencia_marca) AS nombre_completo FROM referencia_marca rm JOIN marcas m ON rm.id_marcas = m.id_marca ORDER BY nombre_completo ASC")->fetch_all(MYSQLI_ASSOC);
$modelos = $conexion->query("SELECT id_modelo, anio FROM modelos ORDER BY anio DESC")->fetch_all(MYSQLI_ASSOC);
$colores = $conexion->query("SELECT id_color, color FROM color ORDER BY color ASC")->fetch_all(MYSQLI_ASSOC);

// --- Lógica de filtrado ---
$sql = "SELECT 
            mo.id_placa, 
            cli.nombre AS nombre_cliente, cli.id_documento_cli,
            ci.cilindraje,
            CONCAT(ma.marcas, ' ', rm.referencia_marca) AS referencia_completa,
            md.anio AS modelo,
            co.color
        FROM motos mo
        JOIN clientes cli ON mo.id_documento_cli = cli.id_documento_cli
        JOIN cilindraje ci ON mo.id_cilindraje = ci.id_cc
        JOIN referencia_marca rm ON mo.id_referencia_marca = rm.id_referencia
        JOIN marcas ma ON rm.id_marcas = ma.id_marca
        JOIN modelos md ON mo.id_modelo = md.id_modelo
        JOIN color co ON mo.id_color = co.id_color";

$where_clauses = [];
$params = [];
$types = '';

// Recoger filtros
$filtros = [
    'id_placa' => $_GET['filtro_placa'] ?? '',
    'id_documento_cli' => $_GET['filtro_cliente'] ?? '',
    'id_cilindraje' => $_GET['filtro_cilindraje'] ?? '',
    'id_referencia_marca' => $_GET['filtro_referencia'] ?? '',
    'id_modelo' => $_GET['filtro_modelo'] ?? '',
    'id_color' => $_GET['filtro_color'] ?? ''
];

foreach ($filtros as $columna => $valor) {
    if (!empty($valor)) {
        // Para la placa, usamos LIKE para búsqueda parcial
        if ($columna == 'id_placa') {
            $where_clauses[] = "mo.{$columna} LIKE ?";
            $params[] = "%" . $valor . "%";
            $types .= 's';
        } else {
            $where_clauses[] = "mo.{$columna} = ?";
            $params[] = $valor;
            $types .= 'i';
        }
    }
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY mo.id_placa ASC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestión de Motos</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Filtrar Motos</h6></div>
        <div class="card-body">
            <form method="GET" action="motos.php">
                <div class="row">
                    <div class="col-md-4 mb-3"><label>Placa:</label><input type="text" name="filtro_placa" class="form-control" value="<?php echo htmlspecialchars($filtros['id_placa']); ?>"></div>
                    <div class="col-md-4 mb-3"><label>Cliente (Dueño):</label>
                        <select name="filtro_cliente" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($clientes as $c): echo "<option value='{$c['id_documento_cli']}' ".($filtros['id_documento_cli'] == $c['id_documento_cli'] ? 'selected' : '').">".htmlspecialchars($c['nombre'])."</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3"><label>Cilindraje:</label>
                        <select name="filtro_cilindraje" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($cilindrajes as $c): echo "<option value='{$c['id_cc']}' ".($filtros['id_cilindraje'] == $c['id_cc'] ? 'selected' : '').">".htmlspecialchars($c['cilindraje'])." cc</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3"><label>Referencia:</label>
                        <select name="filtro_referencia" class="form-control">
                            <option value="">Todas</option>
                             <?php foreach ($referencias as $r): echo "<option value='{$r['id_referencia']}' ".($filtros['id_referencia_marca'] == $r['id_referencia'] ? 'selected' : '').">".htmlspecialchars($r['nombre_completo'])."</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3"><label>Modelo (Año):</label>
                        <select name="filtro_modelo" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($modelos as $m): echo "<option value='{$m['id_modelo']}' ".($filtros['id_modelo'] == $m['id_modelo'] ? 'selected' : '').">".htmlspecialchars($m['anio'])."</option>"; endforeach; ?>
                        </select>
                    </div>
                     <div class="col-md-2 mb-3"><label>Color:</label>
                        <select name="filtro_color" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($colores as $c): echo "<option value='{$c['id_color']}' ".($filtros['id_color'] == $c['id_color'] ? 'selected' : '').">".htmlspecialchars($c['color'])."</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end mb-3">
                        <button type="submit" class="btn btn-success"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="motos.php" class="btn btn-secondary ml-2"><i class="fas fa-times"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Motos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Lista de Motos</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                <table class="table table-bordered" id="dataTableMotos" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Dueño</th>
                            <th>Referencia</th>
                            <th>Cilindraje</th>
                            <th>Modelo</th>
                            <th>Color</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id_placa']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_cliente']); ?> (<?php echo htmlspecialchars($row['id_documento_cli']); ?>)</td>
                            <td><?php echo htmlspecialchars($row['referencia_completa']); ?></td>
                            <td><?php echo htmlspecialchars($row['cilindraje']); ?> cc</td>
                            <td><?php echo htmlspecialchars($row['modelo']); ?></td>
                            <td><?php echo htmlspecialchars($row['color']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo htmlspecialchars($row['id_placa']); ?>"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo htmlspecialchars($row['id_placa']); ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No se encontraron motos.</div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarMoto">
                        <i class="fas fa-motorcycle"></i> Registrar Nueva Moto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODALES === -->
<!-- Modal para Agregar/Editar Moto -->
<div class="modal fade" id="modalMoto" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalMotoLabel">Registrar Nueva Moto</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formMoto" novalidate>
                <!-- Campo oculto para la acción (agregar o editar) y la placa original para edición -->
                <input type="hidden" name="accion" value="agregar">
                <input type="hidden" name="placa_original" value="">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group"><label>Placa</label><input type="text" class="form-control" name="id_placa" required><div class="invalid-feedback"></div></div>
                        <div class="col-md-6 form-group"><label>Dueño (Cliente)</label>
                            <select name="id_documento_cli" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($clientes as $c): echo "<option value='{$c['id_documento_cli']}'>".htmlspecialchars($c['nombre'])."</option>"; endforeach; ?>
                            </select><div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 form-group"><label>Referencia</label>
                            <select name="id_referencia_marca" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($referencias as $r): echo "<option value='{$r['id_referencia']}'>".htmlspecialchars($r['nombre_completo'])."</option>"; endforeach; ?>
                            </select><div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 form-group"><label>Cilindraje</label>
                            <select name="id_cilindraje" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($cilindrajes as $c): echo "<option value='{$c['id_cc']}'>".htmlspecialchars($c['cilindraje'])." cc</option>"; endforeach; ?>
                            </select><div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 form-group"><label>Modelo (Año)</label>
                            <select name="id_modelo" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($modelos as $m): echo "<option value='{$m['id_modelo']}'>".htmlspecialchars($m['anio'])."</option>"; endforeach; ?>
                            </select><div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 form-group"><label>Color</label>
                             <select name="id_color" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($colores as $c): echo "<option value='{$c['id_color']}'>".htmlspecialchars($c['color'])."</option>"; endforeach; ?>
                            </select><div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Eliminar -->
 <!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="modalEliminarMoto" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar esta moto? Esta acción solo se puede realizar si la moto no tiene mantenimientos registrados.</p>
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
    $('#dataTableMotos').DataTable({
        "processing": true,
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ motos",
            "sZeroRecords":    "No se encontraron motos",
            "sEmptyTable":     "No hay motos registradas",
            "sInfo":           "Mostrando motos del _START_ al _END_ de un total de _TOTAL_ motos",
            "sInfoEmpty":      "Mostrando 0 motos",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ motos)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar moto:",
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
                    "1": "Copiada 1 moto al portapapeles",
                    "_": "Copiadas %d motos al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todas las motos",
                    "_": "Mostrar %d motos"
                },
                "pdf": "PDF",
                "print": "Imprimir"
            }
        },
        "pageLength": 10,
        "order": [[0, 'asc']], // Ordenar por placa ascendente
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
        "autoWidth": false,
        "initComplete": function(settings, json) {
            // Personalización adicional después de la inicialización
            $('.dataTables_filter input').attr('placeholder', 'Buscar por placa, dueño...');
        }
    });
});
</script>
<script src="../js/motos.js"></script>