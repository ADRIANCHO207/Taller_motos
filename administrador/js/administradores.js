// --- js/administradores.js (VERSIÓN COMPLETA Y FINAL) ---

$(document).ready(function() {
    
    // ==========================================================
    // SECCIÓN 1: CONFIGURACIÓN Y FUNCIONES REUTILIZABLES
    // ==========================================================
    
    // --- Referencias a los elementos del DOM ---
    // Modal de AGREGAR
    const modalAgregar = $('#modalAgregarAdmin');
    const formAgregar = $('#formAgregarAdmin');
    const documentoInput = $('#documento');
    const nombreInput = $('#nombre');
    const emailInput = $('#email');
    const telefonoInput = $('#telefono');
    const passwordInput = $('#password');
    const confirmarPasswordInput = $('#confirmarPassword');

    // Modal de EDICIÓN
    const modalEditar = $('#modalEditarAdmin');
    const formEditar = $('#formEditarAdmin');
    const editNombreInput = $('#edit_nombre');
    const editEmailInput = $('#edit_email');
    const editTelefonoInput = $('#edit_telefono');
    const editPasswordInput = $('#edit_password');
    const editConfirmarPasswordInput = $('#edit_confirmarPassword');

    // --- Reglas de validación ---
    const reglas = {
        nombre: { regex: /^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/, mensaje: 'Solo se permiten letras y espacios.' },
        email: { regex: /^[a-zA-Z0-9._-]+@gmail\.com$/, mensaje: 'El correo debe ser una cuenta de @gmail.com' },
        telefono: { regex: /^\d{10}$/, mensaje: 'Debe contener exactamente 10 números.' },
        password: { regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,16}$/, mensaje: '8-16 chars, 1 mayúscula, 1 minúscula y 1 número.' },
        documento: { regex: /^\d{6,10}$/, mensaje: 'Debe contener entre 6 y 10 números.' }
    };

    // --- Función genérica para validar campos ---
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

    // ==========================================================
    // SECCIÓN 2: LÓGICA PARA AGREGAR NUEVO ADMINISTRADOR
    // ==========================================================
    
    const validarConfirmacionPasswordAgregar = () => {
        const pass1 = passwordInput.val();
        const pass2 = confirmarPasswordInput.val();
        if (pass1 === pass2) {
            confirmarPasswordInput.removeClass('is-invalid').addClass('is-valid');
            return true;
        } else {
            confirmarPasswordInput.removeClass('is-valid').addClass('is-invalid');
            confirmarPasswordInput.next('.invalid-feedback').text('Las contraseñas no coinciden.');
            return false;
        }
    };
    
    // Validaciones en tiempo real para AGREGAR
    documentoInput.on('input', () => validarCampo(documentoInput, reglas.documento));
    nombreInput.on('input', () => validarCampo(nombreInput, reglas.nombre));
    emailInput.on('input', () => validarCampo(emailInput, reglas.email));
    telefonoInput.on('input', () => validarCampo(telefonoInput, reglas.telefono));
    passwordInput.on('input', () => {
        validarCampo(passwordInput, reglas.password);
        validarConfirmacionPasswordAgregar();
    });
    confirmarPasswordInput.on('input', validarConfirmacionPasswordAgregar);

    // Envío del formulario de AGREGAR
    formAgregar.on('submit', function(e) {
        e.preventDefault();
        
        const esValido = [
            validarCampo(documentoInput, reglas.documento),
            validarCampo(nombreInput, reglas.nombre),
            validarCampo(emailInput, reglas.email),
            validarCampo(telefonoInput, reglas.telefono),
            validarCampo(passwordInput, reglas.password),
            validarConfirmacionPasswordAgregar()
        ].every(Boolean); // Devuelve true solo si todos los elementos del array son true

        if (esValido) {
            const formData = $(this).serialize() + '&accion=agregar';
            $.ajax({
                url: '../ajax/procesar_administrador.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    modalAgregar.modal('hide');
                    if (response.status === 'success') {
                        Swal.fire('¡Éxito!', response.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        } else {
            Swal.fire('Formulario inválido', 'Por favor, corrige los campos marcados en rojo.', 'warning');
        }
    });

    // ==========================================================
    // SECCIÓN 3: LÓGICA PARA EDITAR ADMINISTRADOR
    // ==========================================================

    // --- FUNCIÓN DE VALIDACIÓN DE CONTRASEÑAS DE EDICIÓN (CORREGIDA Y MEJORADA) ---
    const validarPasswordsEditar = () => {
        const pass1Input = $('#edit_password');
        const pass2Input = $('#edit_confirmarPassword');
        const pass1 = pass1Input.val();
        const pass2 = pass2Input.val();

        // CASO 1: Ambos campos están vacíos. Es válido, no se quiere cambiar la contraseña.
        if (pass1 === '' && pass2 === '') {
            pass1Input.removeClass('is-valid is-invalid');
            pass2Input.removeClass('is-valid is-invalid');
            return true;
        }

        // A partir de aquí, al menos un campo tiene texto.

        // CASO 2: Validar el campo "Nueva Contraseña" contra la regla de complejidad.
        // Se valida como si fuera obligatorio porque si se empieza a escribir, debe ser válido.
        const esPass1Valido = validarCampo(pass1Input, reglas.password, true);

        // CASO 3: Validar el campo "Confirmar Contraseña" contra el primero.
        let sonIguales = false;
        if (pass1 === pass2 && esPass1Valido) {
            // Solo es válido si la primera contraseña también lo es.
            pass2Input.removeClass('is-invalid').addClass('is-valid');
            pass2Input.next('.invalid-feedback').text('');
            sonIguales = true;
        } else {
            pass2Input.removeClass('is-valid').addClass('is-invalid');
            pass2Input.next('.invalid-feedback').text('Las contraseñas no coinciden o la primera es inválida.');
            sonIguales = false;
        }

        return esPass1Valido && sonIguales;
    };


    // Validaciones en tiempo real para EDICIÓN
    editNombreInput.on('input', () => validarCampo(editNombreInput, reglas.nombre));
    editEmailInput.on('input', () => validarCampo(editEmailInput, reglas.email));
    editTelefonoInput.on('input', () => validarCampo(editTelefonoInput, reglas.telefono));
    editPasswordInput.on('input', validarPasswordsEditar);
    editConfirmarPasswordInput.on('input', validarPasswordsEditar);
    
    // Abrir modal de edición y cargar datos
    $('#dataTable tbody').on('click', '.btn-editar', function() {
        const adminId = $(this).data('id');
        $.ajax({
            url: '../ajax/procesar_administrador.php', type: 'GET', data: { accion: 'obtener', id: adminId }, dataType: 'json',
            success: function(data) {
                if (data) {
                    formEditar[0].reset();
                    formEditar.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    $('#edit_id_documento').val(data.id_documento);
                    $('#edit_documento_display').val(data.id_documento);
                    editNombreInput.val(data.nombre);
                    editEmailInput.val(data.email);
                    editTelefonoInput.val(data.telefono);
                    modalEditar.modal('show');
                }
            },
            error: () => Swal.fire('Error', 'No se pudieron cargar los datos.', 'error')
        });
    });

    // Envío del formulario de EDICIÓN
    formEditar.on('submit', function(e) {
        e.preventDefault();
        
        const esValido = [
            validarCampo(editNombreInput, reglas.nombre),
            validarCampo(editEmailInput, reglas.email),
            validarCampo(editTelefonoInput, reglas.telefono),
            validarPasswordsEditar()
        ].every(Boolean);

        if (esValido) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: '../ajax/procesar_administrador.php', type: 'POST', data: formData, dataType: 'json',
                success: function(response) {
                    modalEditar.modal('hide');
                    if (response.status === 'success') {
                        Swal.fire('¡Actualizado!', response.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
            });
        } else {
            Swal.fire('Formulario inválido', 'Por favor, corrige los campos marcados en rojo.', 'warning');
        }
    });

    // ==========================================================
    // SECCIÓN 4: LÓGICA PARA ELIMINAR ADMINISTRADOR
    // ==========================================================
    
    let adminIdParaEliminar;

    // Abrir modal de confirmación de eliminación
    $('#dataTable tbody').on('click', '.btn-eliminar', function() {
        adminIdParaEliminar = $(this).data('id');
        $('#modalEliminarAdmin').modal('show');
    });

    // Confirmar la eliminación
    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_administrador.php', type: 'POST', data: { accion: 'eliminar', id: adminIdParaEliminar }, dataType: 'json',
            success: function(response) {
                $('#modalEliminarAdmin').modal('hide');
                if(response.status === 'success') {
                    Swal.fire('¡Eliminado!', response.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error')
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