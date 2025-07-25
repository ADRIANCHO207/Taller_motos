$(document).ready(function() {

    
    const formFiltros = $('#formFiltros');
    const filtroMinInput = $('#filtro_min');
    const filtroMaxInput = $('#filtro_max');

    formFiltros.on('submit', function(e) {
        const valorMinStr = filtroMinInput.val();
        const valorMaxStr = filtroMaxInput.val();

        // Validar el campo "Desde" si no está vacío
        if (valorMinStr) {
            const valorMin = parseInt(valorMinStr, 10);
            if (isNaN(valorMin) || valorMin < 50 || valorMin > 2000) {
                e.preventDefault(); // Detener el envío
                Swal.fire({
                    icon: 'error',
                    title: 'Filtro inválido',
                    text: 'El valor "Desde" debe ser un número entre 50 y 2000.'
                });
                return; // Salir de la función para no mostrar más alertas
            }
        }

        // Validar el campo "Hasta" si no está vacío
        if (valorMaxStr) {
            const valorMax = parseInt(valorMaxStr, 10);
            if (isNaN(valorMax) || valorMax < 50 || valorMax > 2000) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Filtro inválido',
                    text: 'El valor "Hasta" debe ser un número entre 50 y 2000.'
                });
                return;
            }
        }

        // Validar que "Desde" no sea mayor que "Hasta" (solo si ambos están llenos)
        if (valorMinStr && valorMaxStr) {
            if (parseInt(valorMinStr, 10) > parseInt(valorMaxStr, 10)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Rango incorrecto',
                    text: 'El valor "Desde" no puede ser mayor que el valor "Hasta".'
                });
            }
        }
    });


    const formAgregar = $('#formAgregarCilindraje');
    const formEditar = $('#formEditarCilindraje');
    const modalAgregar = $('#modalAgregarCilindraje');
    const modalEditar = $('#modalEditarCilindraje');

    // --- Función de validación ---
    const validarCilindraje = (input) => {
        const valor = parseInt(input.val(), 10);
        if (isNaN(valor) || valor < 50 || valor > 2000) {
            input.addClass('is-invalid');
            return false;
        }
        input.removeClass('is-invalid');
        return true;
    };

    // --- AGREGAR ---
    formAgregar.on('submit', function(e) {
        e.preventDefault();
        const input = formAgregar.find('[name="cilindraje"]');
        if (validarCilindraje(input)) {
            const formData = $(this).serialize() + '&accion=agregar';
            $.ajax({
                url: '../ajax/procesar_cilindraje.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalAgregar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });
    
    // Limpiar modal de agregar al abrir
    modalAgregar.on('show.bs.modal', () => formAgregar[0].reset());

    // --- EDITAR ---
    $('#dataTableCilindraje tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        const valor = $(this).data('valor');
        
        formEditar.find('[name="id_cc"]').val(id);
        formEditar.find('[name="cilindraje"]').val(valor);
        modalEditar.modal('show');
    });

    formEditar.on('submit', function(e) {
        e.preventDefault();
        const input = formEditar.find('[name="cilindraje"]');
        if (validarCilindraje(input)) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: '../ajax/procesar_cilindraje.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalEditar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Actualizado!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });

    // --- ELIMINAR ---
    let idParaEliminar;
    $('#dataTableCilindraje tbody').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        $('#modalEliminarCilindraje').modal('show');
    });

    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_cilindraje.php', type: 'POST', data: { accion: 'eliminar', id: idParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarCilindraje').modal('hide');
                Swal.fire(response.status === 'success' ? '¡Eliminado!' : 'Error', response.message, response.status).then(() => location.reload());
            }
        });
    });


    // Función reutilizable para limpiar un formulario específico
    const limpiarFormulario = (formElement) => {
        if (formElement && formElement.length > 0) {
            formElement[0].reset(); // Resetea los valores de los campos
            formElement.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid'); // Quita clases de validación
            formElement.find('.invalid-feedback').text(''); // Limpia mensajes de error
            formElement.closest('.modal-content').find('.alert').remove(); // Limpia alertas generales
        }
    };

    // --- Manejo específico del modal de AGREGAR ---

    // 1. Al hacer clic en el botón "Cancelar" DENTRO del modal de AGREGAR
    //    Usamos el ID del modal para ser específicos.
    $('#modalAgregarAdmin .btn-secondary').on('click', function() {
        limpiarFormulario(formAgregar);
    });
    // Limpiar formulario de AGREGAR al abrir
    modalAgregar.on('hide.bs.modal', function (e) {
        // No hacemos nada aquí para que los datos se mantengan si se cierra con "X"
    });

    modalAgregar.on('show.bs.modal', function (e) {
        // Opcional: Descomenta estas líneas si quieres que el formulario
        // siempre se limpie al abrir el modal, sin importar cómo se cerró.
        // form[0].reset();
        // form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    });

});
