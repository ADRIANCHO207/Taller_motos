<?php

include 'C:/xampp/htdocs/Taller_motos/includes/validarsession.php';
?>

    <!-- Botón para volver arriba -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

     <!-- === MODAL PARA PERFIL DE ADMINISTRADOR === -->
    <div class="modal fade" id="modalPerfilAdmin" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mi Perfil</h5>
                    <button type="button" class="close" data-dismiss="modal">×</button>
                </div>
                <form id="formPerfilAdmin" novalidate>
                    <div class="modal-body">
                        <!-- Campos no editables -->
                        <div class="form-group">
                            <label>Documento</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($documento_administrador); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($nombre_administrador); ?>" readonly>
                        </div>
                        <hr>
                        <!-- Campos editables -->
                        <div class="form-group">
                            <label for="perfil_telefono">Teléfono</label>
                            <input type="number" class="form-control" id="perfil_telefono" name="telefono" value="<?php echo htmlspecialchars($telefono_administrador); ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group">
                            <label for="perfil_email">Email</label>
                            <input type="email" class="form-control" id="perfil_email" name="email" value="<?php echo htmlspecialchars($email_administrador); ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <hr>
                        <h6>Cambiar Contraseña (opcional)</h6>
                        <div class="form-group">
                            <label for="perfil_password_actual">Contraseña Actual</label>
                            <input type="password" class="form-control" id="perfil_password_actual" name="password_actual" placeholder="Ingresa tu contraseña actual para cambiarla">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group">
                            <label for="perfil_password_nueva">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="perfil_password_nueva" name="password_nueva">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group">
                            <label for="perfil_confirmar_password">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="perfil_confirmar_password" name="confirmar_password">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de cierre de sesión -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">¿Listo para salir?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Selecciona "Cerrar sesión" abajo si estás listo para finalizar tu sesión actual.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" id="btnCerrarSesion">Cerrar sesión</button>
                </div>
            </div>
        </div>
    </div>



     <!-- 1. LIBRERÍAS PRINCIPALES (jQuery siempre primero) -->
    <script src="<?php echo VENDOR_URL; ?>/jquery/jquery.min.js"></script>
    <script src="<?php echo VENDOR_URL; ?>/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- 2. PLUGINS ESENCIALES -->
    <script src="<?php echo VENDOR_URL; ?>/jquery-easing/jquery.easing.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->

    <!-- 3. SCRIPT PRINCIPAL DE LA PLANTILLA -->
    <script src="<?php echo JS_URL; ?>/sb-admin-2.min.js"></script>

    <!-- 4. PLUGINS DE PÁGINAS ESPECÍFICAS (DataTables, Chart.js) -->
    <?php
    $pagina_actual = basename($_SERVER['PHP_SELF']);

    // Cargar DataTables solo en páginas que lo necesiten
    $paginas_con_tabla = ['administradores.php', 'clientes.php', 'motos.php', 'tipos_trabajos.php', 'cilindraje.php', 'marcas.php', 'modelos.php', 'colores.php', 'mantenimientos.php', 'referencias_marcas.php', 'reportes.php'];
    if (in_array($pagina_actual, $paginas_con_tabla)) {
        echo '<script src="' . VENDOR_URL . '/datatables/jquery.dataTables.min.js"></script>';
        echo '<script src="' . VENDOR_URL . '/datatables/dataTables.bootstrap4.min.js"></script>';
    }

    // Cargar Gráficas solo en el dashboard
    if ($pagina_actual == 'index.php') {
        echo '<script src="' . VENDOR_URL . '/chart.js/Chart.min.js"></script>';
        echo '<script src="' . VENDOR_URL . '/datatables/jquery.dataTables.min.js"></script>';
        echo '<script src="' . VENDOR_URL . '/datatables/dataTables.bootstrap4.min.js"></script>';
    }
    ?>
    

    <script>
        const AJAX_URL = "<?php echo AJAX_URL; ?>";
        const ADMIN_URL = "<?php echo ADMIN_URL; ?>";
    </script>


    <!-- Estos scripts dependen de jQuery, por lo que deben cargarse después -->
    <script src="<?php echo JS_URL; ?>/perfil.js"></script> <!-- Este script ahora funcionará -->


    <script>
    document.getElementById('btnCerrarSesion').addEventListener('click', function() {
        fetch('<?php echo BASE_URL; ?>/includes/salir.php')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    $('#logoutModal').modal('hide');
                    Swal.fire({
                        title: '¡Sesión cerrada!',
                        text: 'Redirigiendo...',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '<?php echo BASE_URL; ?>/index.php.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo cerrar la sesión',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al cerrar la sesión',
                    icon: 'error'
                });
            });
    });
    </script>
</body>
</html>