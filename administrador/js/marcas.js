// --- js/marcas.js (CORREGIDO) ---

$(document).ready(function() {

    const formAgregar = $('#formAgregarMarca');
    const formEditar = $('#formEditarMarca');
    const modalAgregar = $('#modalAgregarMarca');
    const modalEditar = $('#modalEditarMarca');

    // --- Función de validación mejorada ---
    const validarMarca = (input) => {
        const valor = input.val().trim();
        // ¡EXPRESIÓN REGULAR CORREGIDA! Solo letras, espacios, tildes y la ñ.
        const regex = /^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/; 
        const feedback = input.next('.invalid-feedback');
        
        if (valor === '') {
            input.addClass('is-invalid');
            feedback.text('El nombre de la marca es obligatorio.');
            return false;
        }

        if (!regex.test(valor)) {
            input.addClass('is-invalid');
            feedback.text('Solo se permiten letras y espacios.');
            return false;
        }
        
        input.removeClass('is-invalid');
        return true;
    };

    // --- LÓGICA PARA AGREGAR ---
    formAgregar.on('submit', function(e) {
        e.preventDefault();
        const input = formAgregar.find('[name="marca"]');
        if (validarMarca(input)) {
            const formData = $(this).serialize() + '&accion=agregar';
            $.ajax({
                url: '../ajax/procesar_marca.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalAgregar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => location.reload());
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
            });
        }
    });
    
    // Limpiar modal de agregar al abrir
    modalAgregar.on('show.bs.modal', () => {
        formAgregar[0].reset();
        formAgregar.find('.is-invalid').removeClass('is-invalid');
    });

    // --- LÓGICA PARA EDITAR ---
    $('#dataTableMarcas tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        const valor = $(this).data('valor');
        
        formEditar.find('[name="id_marca"]').val(id);
        formEditar.find('[name="marca"]').val(valor);
        modalEditar.modal('show');
    });

    formEditar.on('submit', function(e) {
        e.preventDefault();
        const input = formEditar.find('[name="marca"]');
        if (validarMarca(input)) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: '../ajax/procesar_marca.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalEditar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Actualizado!' : 'Error', response.message, response.status).then(() => location.reload());
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
            });
        }
    });

    // --- LÓGICA PARA ELIMINAR ---
    let idParaEliminar;
    $('#dataTableMarcas tbody').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        $('#modalEliminarMarca').modal('show');
    });

    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_marca.php', type: 'POST', data: { accion: 'eliminar', id: idParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarMarca').modal('hide');
                Swal.fire(response.status === 'success' ? '¡Eliminado!' : 'Error', response.message, response.status).then(() => location.reload());
            },
            error: () => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
        });
    });
    // ==========================================================
    // SECCIÓN 5: LÓGICA GENERAL DE LOS MODALES
    // ==========================================================
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