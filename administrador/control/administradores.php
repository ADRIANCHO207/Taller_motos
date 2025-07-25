<?php 
include '../header.php'; 

$conexion = new mysqli('localhost', 'root', '', 'taller_motos');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}


$id_admin_actual = $_SESSION['id_documento'] ?? 0;


// Se prepara la consulta SQL para obtener todos los administradores EXCEPTO el actual.
// El '?' es un marcador de posición para la sentencia preparada.
$sql = "SELECT * FROM administradores WHERE id_documento != ?";
$stmt = $conexion->prepare($sql);
// Se vincula la variable $id_admin_actual al marcador '?'. 's' indica que es de tipo string.
$stmt->bind_param("s", $id_admin_actual);
// Se ejecuta la consulta.
$stmt->execute();
// Se obtiene el conjunto de resultados.
$resultado = $stmt->get_result();
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Administradores</h1>
    <!-- Inicio del contenido de la página -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de otros administradores</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <!-- Si la consulta devolvió al menos una fila, se muestra la tabla -->
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Fecha de creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Se recorre cada fila del resultado de la consulta. -->
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                             <!-- Se utiliza htmlspecialchars() como medida de seguridad para prevenir ataques XSS -->
                            <td><?php echo htmlspecialchars($row['id_documento']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha_creacion']); ?></td>
                            <td>
                                <!-- Botones de acción. Usan atributos 'data-*' para pasar el ID al JavaScript -->

                                <button type="button" class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $row['id_documento']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $row['id_documento']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                     <!-- Si la consulta no devolvió filas, se muestra un mensaje informativo -->
                    <div class="alert alert-info text-center">
                        No hay otros registros de administradores.
                    </div>
                <?php endif; ?>

               
                 <!-- Botón para abrir el modal de "Agregar Nuevo Administrador" -->
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarAdmin">
                        <i class="fas fa-user-plus"></i> Agregar Nuevo Administrador
                    </button>
                </div>

                 <!-- === SECCIÓN DE MODALES === -->

                <!-- Modal para agregar administrador -->
                <div class="modal fade" id="modalAgregarAdmin" tabindex="-1" role="dialog" aria-labelledby="modalAgregarAdminLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalAgregarAdminLabel">Agregar Nuevo Administrador</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                             <!-- El 'novalidate' previene la validación HTML5 por defecto, para usar la de JS -->
                            <form id="formAgregarAdmin" novalidate>
                                <div class="modal-body">
                                     <!-- Campos del formulario. La clase 'invalid-feedback' es de Bootstrap
                                         y se usa para mostrar los mensajes de error de la validación JS. -->
                                    <div class="form-group">
                                        <label for="documento">Documento</label>
                                        <input type="number" class="form-control" id="documento" name="documento" required>
                                        <div class="invalid-feedback">Por favor ingrese un documento válido.</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="nombre">Nombre Completo</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                        <div class="invalid-feedback">Por favor ingrese un nombre válido.</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="invalid-feedback">Por favor ingrese un email válido.</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input type="number" class="form-control" id="telefono" name="telefono" required>
                                        <div class="invalid-feedback">Por favor ingrese un teléfono válido.</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="password">Contraseña</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres.</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirmarPassword">Confirmar Contraseña</label>
                                        <input type="password" class="form-control" id="confirmarPassword" name="confirmarPassword" required>
                                        <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                                    </div>
                                </div>
                                <!-- Contenedor para mostrar alertas generales del formulario (éxito/error) -->
                                <div id="form-alert-container" class="mt-3"></div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
<!-- Modal para editar administrador -->
<div class="modal fade" id="modalEditarAdmin" tabindex="-1" role="dialog" aria-labelledby="modalEditarAdminLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Administrador</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <form id="formEditarAdmin" novalidate>
                <div class="modal-body">
                    <!-- Campo oculto para el ID y campo visible pero no editable para el documento -->
                    <input type="hidden" id="edit_id_documento" name="id_documento">
                    <div class="form-group">
                        <label for="edit_documento_display">Documento</label>
                        <input type="text" class="form-control" id="edit_documento_display" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_nombre">Nombre Completo</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit_telefono">Teléfono</label>
                        <input type="number" class="form-control" id="edit_telefono" name="telefono" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">Nueva Contraseña (opcional)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <small class="form-text text-muted">Dejar en blanco para no cambiar la contraseña actual.</small>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit_confirmarPassword">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="edit_confirmarPassword" name="confirmarPassword">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div id="form-alert-container-editar" class="mx-3"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Actualizar</button></div>
            </form>
        </div>
    </div>
</div>


 <!-- Modal para confirmar la eliminación -->
<div class="modal fade" id="modalEliminarAdmin" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmar Eliminación</h5><button type="button" class="close" data-dismiss="modal">×</button></div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar a este administrador? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

</div>


<?php
include '../scripts.php';
// Incluir el archivo de scripts del footer. Este archivo carga librerías como jQuery, Bootstrap JS, etc.

// Se cierra la conexión a la base de datos que se abrió en este archivo.
$conexion->close();
?>
<!-- Script específico para esta página: inicialización de la librería DataTable -->
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "language": {
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
            "order": [[4, 'desc']], // Ordenar por fecha de creación descendente
            "columns": [
                { "data": "documento" },
                { "data": "nombre" },
                { "data": "email" },
                { "data": "telefono" },
                { "data": "fecha_creacion" },
                { "data": "acciones", "orderable": false }
            ]
        });
    });
</script>
<!-- Cargar el archivo JavaScript que contiene la lógica para los modales (validación, AJAX, etc.) -->
<script src="../js/administradores.js"></script>