
$(document).ready(function() {
    
    // --- Referencias a los elementos del DOM ---
    const modalAgregar = $('#modalAgregarCliente'), formAgregar = $('#formAgregarCliente');
    const modalEditar = $('#modalEditarCliente'), formEditar = $('#formEditarCliente');

    // --- Reglas de validación ---
    const reglas = {
        documento: { regex: /^\d{6,10}$/, mensaje: 'Debe tener entre 6 y 10 números.' },
        nombre: { regex: /^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/, mensaje: 'Solo se permiten letras y espacios.' },
        telefono: { regex: /^\d{10}$/, mensaje: 'Debe tener exactamente 10 números.' },
        email: { regex: /^[^\s@]+@[^\s@]+\.[com]+$/, mensaje: 'Formato de email incorrecto.' }
    };

    // --- Función genérica para validar campos de texto y número ---
    const validarCampo = (input, regla, esObligatorio = true) => {
        const valor = input.val().trim();
        const feedback = input.next('.invalid-feedback');
        if (valor === '') {
            if (esObligatorio) {
                input.removeClass('is-valid').addClass('is-invalid');
                feedback.text('Este campo es obligatorio.');
                return false;
            }
            input.removeClass('is-valid is-invalid');
            feedback.text('');
            return true;
        }
        if (regla.regex.test(valor)) {
            input.removeClass('is-invalid').addClass('is-valid');
            feedback.text('');
            return true;
        } else {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.text(regla.mensaje);
            return false;
        }
    };
    
    // --- Función para validar que una fecha no sea futura ---
    const validarFechaNoFutura = (input) => {
        const feedback = input.next('.invalid-feedback');
        const fechaSeleccionadaStr = input.val();
        if (!fechaSeleccionadaStr) {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.text('La fecha y hora son obligatorias.');
            return false;
        }
        if (new Date(fechaSeleccionadaStr) > new Date()) {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.text('La fecha y hora no pueden ser futuras.');
            return false;
        } else {
            input.removeClass('is-invalid').addClass('is-valid');
            feedback.text('');
            return true;
        }
    };

    // Validaciones en tiempo real para AGREGAR
    formAgregar.find('[name="documento"]').on('input', e => validarCampo($(e.target), reglas.documento));
    formAgregar.find('[name="nombre"]').on('input', e => validarCampo($(e.target), reglas.nombre));
    formAgregar.find('[name="telefono"]').on('input', e => validarCampo($(e.target), reglas.telefono));
    formAgregar.find('[name="email"]').on('input', e => validarCampo($(e.target), reglas.email, false));
    formAgregar.find('[name="fecha_ingreso"]').on('input', e => validarFechaNoFutura($(e.target)));

    formAgregar.on('submit', function(e) {
        e.preventDefault();
        const esValido = [
            validarCampo(formAgregar.find('[name="documento"]'), reglas.documento),
            validarCampo(formAgregar.find('[name="nombre"]'), reglas.nombre),
            validarCampo(formAgregar.find('[name="telefono"]'), reglas.telefono),
            validarCampo(formAgregar.find('[name="email"]'), reglas.email, false),
            validarFechaNoFutura(formAgregar.find('[name="fecha_ingreso"]'))
        ].every(Boolean);

        if (esValido) {
            const formData = $(this).serialize() + '&accion=agregar';
            $.ajax({
                url: '../ajax/procesar_cliente.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalAgregar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => location.reload());
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        } else {
            Swal.fire('Formulario inválido', 'Por favor, corrige los campos marcados.', 'warning');
        }
    });


    // Validaciones en tiempo real para EDITAR
    formEditar.find('[name="nombre"]').on('input', e => validarCampo($(e.target), reglas.nombre));
    formEditar.find('[name="telefono"]').on('input', e => validarCampo($(e.target), reglas.telefono));
    formEditar.find('[name="email"]').on('input', e => validarCampo($(e.target), reglas.email, false));
    formEditar.find('[name="fecha_ingreso"]').on('input', e => validarFechaNoFutura($(e.target)));

    // Abrir modal y cargar datos para EDITAR
    $('#dataTableClientes tbody').on('click', '.btn-editar', function() {
        const clienteId = $(this).data('id');
        $.ajax({
            url: '../ajax/procesar_cliente.php', type: 'GET', data: { accion: 'obtener', id: clienteId }, dataType: 'json',
            success: data => {
                if (data) {
                    formEditar[0].reset();
                    formEditar.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    formEditar.find('[name="id_documento_cli"]').val(data.id_documento_cli);
                    $('#edit_documento_display').val(data.id_documento_cli);
                    formEditar.find('[name="nombre"]').val(data.nombre);
                    formEditar.find('[name="telefono"]').val(data.telefono);
                    formEditar.find('[name="email"]').val(data.email);
                    formEditar.find('[name="direccion"]').val(data.direccion);
                    // Formatear la fecha para el input datetime-local
                    const fechaIngreso = data.fecha_ingreso ? data.fecha_ingreso.replace(' ', 'T') : '';
                    formEditar.find('[name="fecha_ingreso"]').val(fechaIngreso);
                    modalEditar.modal('show');
                }
            }
        });
    });

    // Envío del formulario de EDICIÓN
    formEditar.on('submit', function(e) {
        e.preventDefault();
        const esValido = [
            validarCampo(formEditar.find('[name="nombre"]'), reglas.nombre),
            validarCampo(formEditar.find('[name="telefono"]'), reglas.telefono),
            validarCampo(formEditar.find('[name="email"]'), reglas.email, false),
            validarFechaNoFutura(formEditar.find('[name="fecha_ingreso"]'))
        ].every(Boolean);

        if (esValido) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: '../ajax/procesar_cliente.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalEditar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Actualizado!' : 'Error', response.message, response.status).then(() => location.reload());
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        } else {
            Swal.fire('Formulario inválido', 'Por favor, corrige los campos marcados.', 'warning');
        }
    });

    
    let clienteIdParaEliminar;
    $('#dataTableClientes tbody').on('click', '.btn-eliminar', function() {
        clienteIdParaEliminar = $(this).data('id');
        $('#modalEliminarCliente').modal('show');
    });
    $('#btnConfirmarEliminarCliente').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_cliente.php', type: 'POST', data: { accion: 'eliminar', id: clienteIdParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarCliente').modal('hide');
                Swal.fire(response.status === 'success' ? '¡Eliminado!' : 'Error', response.message, response.status).then(() => location.reload());
            }
        });
    });


    const formFiltros = $('#formFiltros');
    const fechaInicioInput = $('#fecha_inicio');
    const fechaFinInput = $('#fecha_fin');
    
    // --- VALIDACIÓN DE FECHAS EN FILTROS ---
    const validarFiltroFechas = () => {
        const fechaInicio = fechaInicioInput.val();
        const fechaFin = fechaFinInput.val();
        
        // Obtener fecha actual en Colombia
        const ahoraEnColombia = new Date(new Date().toLocaleString("en-US", {timeZone: "America/Bogota"}));
        const hoy = ahoraEnColombia.toISOString().split('T')[0]; // Formato YYYY-MM-DD

        if (fechaInicio > hoy || fechaFin > hoy) {
            Swal.fire({ icon: 'error', title: 'Fechas incorrectas', text: 'Las fechas del filtro no pueden ser futuras al día de hoy.' });
            return false;
        }
        
        if (fechaInicio && fechaFin && fechaFin < fechaInicio) {
            Swal.fire({ icon: 'error', title: 'Fechas incorrectas', text: 'La "Fecha Fin" no puede ser anterior a la "Fecha Inicio".' });
            return false;
        }
        return true;
    };

    formFiltros.on('submit', function(e) {
        if (!validarFiltroFechas()) {
            e.preventDefault(); // Detener el envío si la validación falla
        }
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

    $('#modalAgregarCliente .btn-secondary').on('click', function() {
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
