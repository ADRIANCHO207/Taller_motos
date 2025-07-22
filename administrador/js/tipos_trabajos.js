// --- js/tipos_trabajos.js (VERSIÓN COMPLETA Y FINAL) ---
$(document).ready(function() {
    const modal = $('#modalTipoTrabajo');
    const form = $('#formTipoTrabajo');
    const modalLabel = $('#modalTipoTrabajoLabel');
    const formFiltros = $('#formFiltros');

    // --- Función de validación mejorada ---
    const validarFormulario = () => {
        let esValido = true;
        form.find('.is-invalid').removeClass('is-invalid');

        // Validar campos requeridos
        form.find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid').next('.invalid-feedback').text('Este campo es obligatorio.');
                esValido = false;
            }
        });

        // Validar rangos numéricos de CC
        const cc_inicial_input = form.find('[name="cc_inicial"]');
        const cc_final_input = form.find('[name="cc_final"]');
        const cc_inicial = parseInt(cc_inicial_input.val());
        const cc_final = parseInt(cc_final_input.val());

        if (!isNaN(cc_inicial) && (cc_inicial < 1 || cc_inicial > 2000)) {
            cc_inicial_input.addClass('is-invalid').next('.invalid-feedback').text('Debe ser un número entre 1 y 2000.');
            esValido = false;
        }
        if (!isNaN(cc_final) && (cc_final < 2 || cc_final > 2000)) {
            cc_final_input.addClass('is-invalid').next('.invalid-feedback').text('Debe ser un número entre 2 y 2000.');
            esValido = false;
        }
        
        // Validar que CC Inicial no sea mayor que CC Final
        if (!isNaN(cc_inicial) && !isNaN(cc_final) && cc_inicial > cc_final) {
            cc_inicial_input.addClass('is-invalid').next('.invalid-feedback').text('No puede ser mayor que CC Final.');
            esValido = false;
        }
        
        return esValido;
    };

    // --- Abrir modal para AGREGAR ---
    $('[data-target="#modalAgregarTipoTrabajo"]').on('click', function() {
        form[0].reset();
        form.find('.is-invalid').removeClass('is-invalid');
        modalLabel.text('Agregar Nuevo Trabajo');
        form.find('[name="accion"]').val('agregar');
        form.find('[name="id_tipo"]').val('0');
        modal.modal('show');
    });

    // --- Abrir modal para EDITAR ---
    $('#dataTableTiposTrabajo tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        $.ajax({
            url: '../ajax/procesar_tipo_trabajo.php', type: 'GET', data: { accion: 'obtener', id: id }, dataType: 'json',
            success: data => {
                form[0].reset();
                form.find('.is-invalid').removeClass('is-invalid');
                modalLabel.text('Editar Trabajo: ' + data.detalle);
                form.find('[name="accion"]').val('actualizar');
                form.find('[name="id_tipo"]').val(data.id_tipo);
                form.find('[name="detalle"]').val(data.detalle);
                form.find('[name="cc_inicial"]').val(data.cc_inicial);
                form.find('[name="cc_final"]').val(data.cc_final);
                form.find('[name="precio_unitario"]').val(data.precio_unitario);
                modal.modal('show');
            }
        });
    });

    // --- Envío del formulario (Agregar y Editar) ---
    form.on('submit', function(e) {
        e.preventDefault();
        if (validarFormulario()) {
            $.ajax({
                url: '../ajax/procesar_tipo_trabajo.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: response => {
                    modal.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status)
                    .then(() => { if (response.status === 'success') location.reload(); });
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        } else {
            // No mostramos alerta aquí porque los campos ya están marcados en rojo.
        }
    });

    // --- Lógica para ELIMINAR ---
    let idParaEliminar;
    $('#dataTableTiposTrabajo tbody').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        $('#modalEliminarTipoTrabajo').modal('show');
    });

    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_tipo_trabajo.php', type: 'POST', data: { accion: 'eliminar', id: idParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarTipoTrabajo').modal('hide');
                Swal.fire(response.status === 'success' ? '¡Eliminado!' : 'Error', response.message, response.status)
                .then(() => { if (response.status === 'success') location.reload(); });
            },
            error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
        });
    });

    // --- NUEVA LÓGICA PARA VALIDACIÓN DE FILTROS ---
    formFiltros.on('submit', function(e) {
        const ccMinInput = $(this).find('[name="filtro_cc_min"]');
        const ccMaxInput = $(this).find('[name="filtro_cc_max"]');
        const ccMin = parseInt(ccMinInput.val());
        const ccMax = parseInt(ccMaxInput.val());

        if (!isNaN(ccMin) && !isNaN(ccMax) && ccMin > ccMax) {
            e.preventDefault();
            Swal.fire('Rango Incorrecto', 'El filtro "Para CC Desde" no puede ser mayor que "Para CC Hasta".', 'error');
        }
    });

});