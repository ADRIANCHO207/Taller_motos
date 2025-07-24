// --- js/perfil.js (VERSIÓN COMPLETA Y FINAL) ---
$(document).ready(function() {
    const modalPerfil = $('#modalPerfilAdmin');
    const formPerfil = $('#formPerfilAdmin');

    const reglas = {
        telefono: { regex: /^\d{10}$/, mensaje: 'Debe tener exactamente 10 números.' },
        email: { regex: /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/, mensaje: 'Formato de email incorrecto.' },
        password: { regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,16}$/, mensaje: '8-16 chars, 1 mayúscula, 1 minúscula y 1 número.' }
    };
    
    // --- ¡FUNCIÓN CLAVE QUE FALTABA! ---
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

    // --- LÓGICA DEL MODAL DE PERFIL ---

    // 1. Cargar datos al abrir el modal
    modalPerfil.on('show.bs.modal', function() {
        formPerfil[0].reset();
        formPerfil.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        
        $.ajax({
            url: 'ajax/procesar_perfil.php', type: 'GET', data: { accion: 'obtener' }, dataType: 'json',
            success: response => {
                if (response.status === 'success') {
                    $('#perfil_telefono').val(response.data.telefono);
                    $('#perfil_email').val(response.data.email);
                }
            }
        });
    });

    // 2. Validaciones en tiempo real
    $('#perfil_telefono').on('input', e => validarCampo($(e.target), reglas.telefono));
    $('#perfil_email').on('input', e => validarCampo($(e.target), reglas.email));
    
    // Validación de contraseñas
    $('#perfil_password_nueva, #perfil_confirmar_password').on('input', function() {
        const passNueva = $('#perfil_password_nueva');
        const passConfirm = $('#perfil_confirmar_password');
        
        if (passNueva.val() || passConfirm.val()) {
            // Validar complejidad de la nueva contraseña
            validarCampo(passNueva, reglas.password, true);
            
            // Validar que coincidan
            if (passNueva.val() === passConfirm.val() && passNueva.val() !== '') {
                passConfirm.removeClass('is-invalid').addClass('is-valid');
            } else {
                passConfirm.addClass('is-invalid').next('.invalid-feedback').text('Las contraseñas no coinciden.');
            }
        } else {
            // Si ambos están vacíos, limpiar estados
            passNueva.removeClass('is-valid is-invalid');
            passConfirm.removeClass('is-valid is-invalid');
        }
    });

    // 3. Envío del formulario
    formPerfil.on('submit', function(e) {
        e.preventDefault();

        // Ejecutar validaciones finales
        let esTelefonoValido = validarCampo($('#perfil_telefono'), reglas.telefono);
        let esEmailValido = validarCampo($('#perfil_email'), reglas.email);
        
        let esPasswordValido = true;
        const passNuevaInput = $('#perfil_password_nueva');
        const passActualInput = $('#perfil_password_actual');
        const passConfirmInput = $('#perfil_confirmar_password');

        // Solo validamos las contraseñas si el usuario intentó cambiarlas
        if (passNuevaInput.val() || passActualInput.val() || passConfirmInput.val()) {
            const esPassNuevaValida = validarCampo(passNuevaInput, reglas.password);
            const sonIguales = passNuevaInput.val() === passConfirmInput.val();
            const esPassActualValida = validarCampo(passActualInput, {regex: /.+/}, true); // Solo verificamos que no esté vacía

            if (!sonIguales) {
                passConfirmInput.addClass('is-invalid').next('.invalid-feedback').text('Las contraseñas no coinciden.');
            } else {
                 passConfirmInput.removeClass('is-invalid');
            }
            esPasswordValido = esPassNuevaValida && sonIguales && esPassActualValida;
        }

        if (esTelefonoValido && esEmailValido && esPasswordValido) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: 'ajax/procesar_perfil.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    if (response.status === 'success') {
                        modalPerfil.modal('hide');
                        Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                            // No es necesario recargar a menos que la contraseña cambie
                            if (passNuevaInput.val()) {
                                location.reload();
                            }
                        });
                    } else {
                        // Si el backend devuelve un error (ej. contraseña actual incorrecta)
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: () => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
            });
        } else {
            // Si la validación del frontend falla, avisamos al usuario.
             Swal.fire('Formulario inválido', 'Por favor, corrige los campos marcados en rojo.', 'warning');
        }
    });
});