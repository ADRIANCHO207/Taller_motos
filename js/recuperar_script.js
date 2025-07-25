$(document).ready(function() {
    
    // --- LÓGICA PARA LA PÁGINA DE SOLICITUD ---
    const formSolicitud = $('#formRecuperar');
    if (formSolicitud.length) {
        const emailInput = $('#email');
        const emailRegex = /^[a-zA-Z0-9._-]+@gmail\.com$/;

        const validarEmail = () => {
            const email = emailInput.val().trim();
            const inputGroup = emailInput.closest('.input-group');
            const errorMessage = inputGroup.find('.error-message');
            
            if (email === '') {
                inputGroup.removeClass('is-valid is-invalid');
                errorMessage.text('');
                return false;
            }
            if (emailRegex.test(email)) {
                inputGroup.removeClass('is-invalid').addClass('is-valid');
                errorMessage.text('');
                return true;
            } else {
                inputGroup.removeClass('is-valid').addClass('is-invalid');
                errorMessage.text('Solo se permiten correos de Gmail (@gmail.com)');
                return false;
            }
        };
        
        emailInput.on('input', validarEmail);

        formSolicitud.on('submit', function(e) {
            e.preventDefault();
            if (!validarEmail()) {
                Swal.fire('Error', 'Por favor, ingresa un correo Gmail válido.', 'warning');
                return;
            }
            
            const btn = $(this).find('button[type="submit"]');
            const formData = $(this).serialize() + '&accion=solicitar';

            $.ajax({
                url: 'ajax/procesar_recuperacion.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    btn.prop('disabled', true).text('Enviando...');
                },
                success: function(response) {
                    if(response.status === 'success') {
                        Swal.fire('¡Éxito!', response.message, 'success');
                        formSolicitud[0].reset();
                        emailInput.closest('.input-group').removeClass('is-valid is-invalid');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                        formSolicitud[0].reset();
                        emailInput.closest('.input-group').removeClass('is-valid is-invalid');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Enviar Enlace');
                }
            });
        });
    }

    // --- LÓGICA PARA LA PÁGINA DE RESETEO ---
    const formReset = $('#formReset');
    if (formReset.length) {
        const passNuevaInput = $('#password_nueva');
        const passConfirmInput = $('#confirmar_password');
        const reglaPassword = { 
            regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,16}$/, 
            mensaje: '8-16 caracteres, al menos 1 mayúscula, 1 minúscula y 1 número' 
        };

        const validarPasswords = () => {
            const pass1 = passNuevaInput.val();
            const pass2 = passConfirmInput.val();
            const pass1Group = passNuevaInput.closest('.input-group');
            const pass2Group = passConfirmInput.closest('.input-group');
            const pass1Feedback = pass1Group.find('.error-message');
            const pass2Feedback = pass2Group.find('.error-message');

            let esPass1Valido = false;
            let sonIguales = false;

            // Validar complejidad
            if (pass1 === '') {
                pass1Group.removeClass('is-valid is-invalid');
                pass1Feedback.text('');
            } else if (reglaPassword.regex.test(pass1)) {
                pass1Group.removeClass('is-invalid').addClass('is-valid');
                pass1Feedback.text('');
                esPass1Valido = true;
                pass1Feedback.css('border', 'solid 3px green');
            } else {
                pass1Group.removeClass('is-valid').addClass('is-invalid');
                pass1Feedback.text(reglaPassword.mensaje);
            }

            // Validar coincidencia
            if (pass2 === '') {
                pass2Group.removeClass('is-valid is-invalid');
                pass2Feedback.text('');
            } else if (pass1 === pass2 && esPass1Valido) {
                pass2Group.removeClass('is-invalid').addClass('is-valid');
                pass2Feedback.text('');
                pass2Feedback.css('border', 'solid 3px green');
                sonIguales = true;
            } else {
                pass2Group.removeClass('is-valid').addClass('is-invalid');
                pass2Feedback.text('Las contraseñas no coinciden');
                pass2Feedback.css('border', 'solid 3px red');
            }
            
            return esPass1Valido && sonIguales;
        };
        
        passNuevaInput.on('input', validarPasswords);
        passConfirmInput.on('input', validarPasswords);
        
        formReset.on('submit', function(e) {
            e.preventDefault();
            
            if (validarPasswords()) {
                const btn = $(this).find('button[type="submit"]');
                const formData = $(this).serialize() + '&accion=resetear';
                
                $.ajax({
                    url: 'ajax/procesar_recuperacion.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    beforeSend: function() {
                        btn.prop('disabled', true).text('Procesando...');
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000
                            }).then(() => {
                                window.location.href = 'login.php';
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Cambiar Contraseña');
                    }
                });
            } else {
                Swal.fire('Error', 'Por favor, verifica los campos del formulario.', 'warning');
            }
        });
    }
});
