document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const documentoInput = document.getElementById('documento');
    const passwordInput = document.getElementById('password');
    const formMessage = document.getElementById('form-message'); 

    // (La función validateField y las regex no cambian)
    const validateField = (input, regex, errorMessageText) => {
        const inputGroup = input.parentElement;
        const icon = inputGroup.querySelector('.validation-icon');
        const errorMessage = inputGroup.querySelector('.error-message');
        const value = input.value;
        const isValid = regex.test(value);
        if (value === '') {
            inputGroup.classList.remove('valid', 'invalid');
            errorMessage.textContent = '';
            return false;
        }
        if (isValid) {
            inputGroup.classList.add('valid');
            inputGroup.classList.remove('invalid');
            icon.className = 'fas fa-check-circle validation-icon';
            errorMessage.textContent = '';
        } else {
            inputGroup.classList.add('invalid');
            inputGroup.classList.remove('valid');
            icon.className = 'fas fa-times-circle validation-icon';
            errorMessage.textContent = errorMessageText;
        }
        return isValid;
    };
    const documentoRegex = /^\d{6,10}$/;
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,16}$/;
    documentoInput.addEventListener('input', () => validateField(documentoInput, documentoRegex, 'Debe tener entre 6 y 10 números.'));
    passwordInput.addEventListener('input', () => validateField(passwordInput, passwordRegex, 'Debe tener entre 8 y 16 caracteres.'));

    loginForm.addEventListener('submit', (e) => {
        e.preventDefault(); 
        formMessage.textContent = '';
        formMessage.className = '';

        if (documentoInput.value === '' || passwordInput.value === '') {
            formMessage.textContent = 'Error: Todos los campos son obligatorios.';
            formMessage.className = 'error';
            return; 
        }

        const isDocumentoValid = validateField(documentoInput, documentoRegex, 'Debe tener entre 6 y 10 números.');
        const isPasswordValid = validateField(passwordInput, passwordRegex, 'Debe tener entre 8 y 16 caracteres.');
        
        if (isDocumentoValid && isPasswordValid) {
            const formData = new FormData(loginForm);
            
            fetch('includes/inicio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Hacemos una copia de la respuesta para poder leerla dos veces
                const clonedResponse = response.clone();
                
                // Intentamos parsearla como JSON
                return response.json().catch(() => {
                    // Si falla el parseo, leemos la respuesta como texto
                    return clonedResponse.text().then(text => {
                        // Y lanzamos un error que incluye el texto del error de PHP
                        throw new Error(`La respuesta del servidor no es un JSON válido. Respuesta recibida:\n${text}`);
                    });
                });
            })
            .then(data => {
                formMessage.textContent = data.message;
                formMessage.className = data.status;

                if (data.status === 'success') {
                    loginForm.reset();
                    setTimeout(() => {
                        window.location.href = 'administrador/index.php'; 
                    }, 1500);
                     
                }
            })
            .catch(error => {
                // Ahora el mensaje de error será mucho más útil
                console.error('Error en la petición AJAX:', error);
                formMessage.textContent = 'Error de conexión o del servidor. Revisa la consola para más detalles.';
                formMessage.className = 'error';
            });

        } else {
            formMessage.textContent = 'Por favor, corrige los errores en el formulario.';
            formMessage.className = 'error';
        }
    });
});