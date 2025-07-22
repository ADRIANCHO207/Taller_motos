$(document).ready(function() {

    // --- Referencias a los elementos del DOM ---
    const formAgregar = $('#formAgregarModelo');
    const formEditar = $('#formEditarModelo');
    const modalAgregar = $('#modalAgregarModelo');
    const modalEditar = $('#modalEditarModelo');
    const formFiltros = $('#formFiltros');
    const filtroInicioInput = $('#filtro_inicio');
    const filtroFinInput = $('#filtro_fin');

    // --- Función de validación para el año ---
    const validarAnio = (input) => {
        const valor = parseInt(input.val(), 10);
        const anioActual = new Date().getFullYear();
        
        // El año debe ser un número entre 2000 y dos años en el futuro
        if (isNaN(valor) || valor < 2000 || valor > (anioActual + 1)) {
            input.addClass('is-invalid');
            input.next('.invalid-feedback').text(`Debe ser un año entre 2000 y ${anioActual + 1}.`);
            return false;
        }
        input.removeClass('is-invalid');
        return true;
    };
    
    // ==========================================================
    // LÓGICA PARA LA VALIDACIÓN DE FILTROS (COMPLETADA)
    // ==========================================================
    
    formFiltros.on('submit', function(e) {
        const valorInicioStr = filtroInicioInput.val();
        const valorFinStr = filtroFinInput.val();

        // Validar el campo "Desde" si no está vacío
        if (valorInicioStr) {
            const valorInicio = parseInt(valorInicioStr, 10);
            if (isNaN(valorInicio) || valorInicio < 2000 || valorInicio > 2027) { // Rango amplio para filtros
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Filtro inválido',
                    text: 'El valor "Desde el año" debe ser un año válido (ej: 2000 - 2026).'
                });
                return;
            }
        }

        // Validar el campo "Hasta" si no está vacío
        if (valorFinStr) {
            const valorFin = parseInt(valorFinStr, 10);
            if (isNaN(valorFin) || valorFin < 2000 || valorFin > 2026) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Filtro inválido',
                    text: 'El valor "Hasta el año" debe ser un año válido (ej: 2026).'
                });
                return;
            }
        }

        // Validar que "Desde" no sea mayor que "Hasta" (solo si ambos están llenos)
        if (valorInicioStr && valorFinStr) {
            if (parseInt(valorInicioStr, 10) > parseInt(valorFinStr, 10)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Rango incorrecto',
                    text: 'El año "Desde" no puede ser mayor que el año "Hasta".'
                });
            }
        }
    });

    // ==========================================================
    // LÓGICA PARA AGREGAR
    // ==========================================================
    formAgregar.on('submit', function(e) {
        e.preventDefault();
        const input = formAgregar.find('[name="anio"]');
        if (validarAnio(input)) {
            const formData = $(this).serialize() + '&accion=agregar';
            $.ajax({
                url: '../ajax/procesar_modelo.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalAgregar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });
    
    modalAgregar.on('show.bs.modal', () => {
        formAgregar[0].reset();
        formAgregar.find('.is-invalid').removeClass('is-invalid');
    });

    // ==========================================================
    // LÓGICA PARA EDITAR
    // ==========================================================
    $('#dataTableAnios tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        const valor = $(this).data('valor');
        
        formEditar.find('[name="id_modelo"]').val(id);
        formEditar.find('[name="anio"]').val(valor);
        modalEditar.modal('show');
    });

    formEditar.on('submit', function(e) {
        e.preventDefault();
        const input = formEditar.find('[name="anio"]');
        if (validarAnio(input)) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: '../ajax/procesar_modelo.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalEditar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Actualizado!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });

    // ==========================================================
    // LÓGICA PARA ELIMINAR
    // ==========================================================
    let idParaEliminar;
    $('#dataTableAnios tbody').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        $('#modalEliminarModelo').modal('show');
    });

    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_modelo.php', type: 'POST', data: { accion: 'eliminar', id: idParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarModelo').modal('hide');
                Swal.fire(response.status === 'success' ? '¡Eliminado!' : 'Error', response.message, response.status).then(() => location.reload());
            }
        });
    });
});