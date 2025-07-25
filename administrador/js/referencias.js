$(document).ready(function() {
    
    // --- Referencias a los formularios y modales ---
    const formAgregar = $('#formAgregarReferencia');
    const formEditar = $('#formEditarReferencia');
    const modalAgregar = $('#modalAgregarReferencia');
    const modalEditar = $('#modalEditarReferencia');

    // --- Reglas de validación ---
    const reglas = {
        // Expresión regular: Empieza con letra, seguido de letras, números, espacios o guiones. Longitud total de 2 a 20.
        referencia: { 
            regex: /^[A-Za-z][A-Za-z0-9\s-]{1,19}$/, 
            mensaje: 'Debe empezar con una letra y tener entre 2 y 20 caracteres (letras, números, espacios, guiones).' 
        }
    };

    // --- Función genérica para validar campos ---
    const validarCampo = (input, esObligatorio = true) => {
        const valor = input.val();
        if (!valor && esObligatorio) {
            input.addClass('is-invalid');
            return false;
        }
        input.removeClass('is-invalid');
        return true;
    };

    // --- Función específica para validar el campo de referencia ---
    const validarReferenciaInput = (input) => {
        const valor = input.val().trim();
        const feedback = input.next('.invalid-feedback');
        
        if (valor === '') {
            input.addClass('is-invalid');
            feedback.text('Este campo es obligatorio.');
            return false;
        }
        if (!reglas.referencia.regex.test(valor)) {
            input.addClass('is-invalid');
            feedback.text(reglas.referencia.mensaje);
            return false;
        }
        
        input.removeClass('is-invalid');
        return true;
    };

    // --- AGREGAR ---
    // Validaciones en tiempo real
    formAgregar.find('[name="id_marcas"]').on('change', e => validarCampo($(e.target)));
    formAgregar.find('[name="referencia_marca"]').on('input', e => validarReferenciaInput($(e.target)));

    formAgregar.on('submit', function(e) {
        e.preventDefault();
        const esMarcaValida = validarCampo(formAgregar.find('[name="id_marcas"]'));
        const esReferenciaValida = validarReferenciaInput(formAgregar.find('[name="referencia_marca"]'));

        if (esMarcaValida && esReferenciaValida) {
            const formData = $(this).serialize() + '&accion=agregar';
            $.ajax({
                url: '../ajax/procesar_referencia.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalAgregar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });
    
    // --- EDITAR ---
    // Validaciones en tiempo real
    formEditar.find('[name="id_marcas"]').on('change', e => validarCampo($(e.target)));
    formEditar.find('[name="referencia_marca"]').on('input', e => validarReferenciaInput($(e.target)));
    // --- EDITAR ---
    $('#dataTableReferencias tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        $.ajax({
            url: '../ajax/procesar_referencia.php', type: 'GET', data: { accion: 'obtener', id: id }, dataType: 'json',
            success: data => {
                if (data) {
                    formEditar.find('[name="id_referencia"]').val(data.id_referencia);
                    formEditar.find('[name="id_marcas"]').val(data.id_marcas);
                    formEditar.find('[name="referencia_marca"]').val(data.referencia_marca);
                    modalEditar.modal('show');
                }
            }
        });
    });

    formEditar.on('submit', function(e) {
        e.preventDefault();
        const esMarcaValida = validarCampo(formEditar.find('[name="id_marcas"]'));
        const esReferenciaValida = validarReferenciaInput(formEditar.find('[name="referencia_marca"]'));

        if (esMarcaValida && esReferenciaValida) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: '../ajax/procesar_referencia.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalEditar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Actualizado!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });

    // --- ELIMINAR ---
    let idParaEliminar;
    $('#dataTableReferencias tbody').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        $('#modalEliminarReferencia').modal('show');
    });

    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_referencia.php', type: 'POST', data: { accion: 'eliminar', id: idParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarReferencia').modal('hide');
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