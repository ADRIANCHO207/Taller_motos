<?php
// --- footer.php ---
include 'C:/xampp/htdocs/Taller_motos/includes/validarsession.php';
?>

    <!-- Botón para volver arriba -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

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

    <!-- JavaScript principal de Bootstrap -->
    <script src="<?php echo VENDOR_URL; ?>/jquery/jquery.min.js"></script>
    <script src="<?php echo VENDOR_URL; ?>/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Plugin principal de JavaScript -->
    <script src="<?php echo VENDOR_URL; ?>/jquery-easing/jquery.easing.min.js"></script>

    <!-- Scripts personalizados para todas las páginas -->
    <script src="<?php echo JS_URL; ?>/sb-admin-2.min.js"></script>

    <!-- Plugins de la página -->
    <script src="<?php echo VENDOR_URL; ?>/chart.js/Chart.min.js"></script>
    <script src="<?php echo JS_URL; ?>/demo/chart-area-demo.js"></script>
    <script src="<?php echo JS_URL; ?>/demo/chart-pie-demo.js"></script>
    <!-- Plugins de DataTables -->
    <script src="<?php echo VENDOR_URL; ?>/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo VENDOR_URL; ?>/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Scripts personalizados de la página -->
    <script src="<?php echo JS_URL; ?>/demo/chart-area-demo.js"></script>
    <script src="<?php echo JS_URL; ?>/demo/chart-pie-demo.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                        window.location.href = '<?php echo BASE_URL; ?>/login.php';
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