// --- js/motos.js ---
$(document).ready(function() {
    const modalMoto = $('#modalMoto');
    const formMoto = $('#formMoto');
    const modalLabel = $('#modalMotoLabel');
    const placaInput = formMoto.find('[name="id_placa"]');
    const filtroPlacaInput = $('[name="filtro_placa"]');

    const regla_placa = { 
        regex: /^[A-Z]{3}\d{2}[A-Z]$/, 
        mensaje: 'Formato inválido. Debe ser: 3 letras, 2 números, 1 letra (ej: ABC12D).' 
    };

    // --- FUNCIÓN PARA CONVERTIR A MAYÚSCULAS EN TIEMPO REAL ---
    const toUpperCaseHandler = function() {
        const start = this.selectionStart;
        const end = this.selectionEnd;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(start, end);
    };

    // Aplicar a los campos de placa
    placaInput.on('input', toUpperCaseHandler);
    filtroPlacaInput.on('input', toUpperCaseHandler);

    // --- Lógica para abrir el modal en modo AGREGAR ---
    $('[data-target="#modalAgregarMoto"]').on('click', function() {
        formMoto[0].reset();
        formMoto.find('.is-invalid').removeClass('is-invalid');
        modalLabel.text('Registrar Nueva Moto');
        formMoto.find('[name="accion"]').val('agregar');
        placaInput.prop('readonly', false); // La placa se puede editar
        modalMoto.modal('show');
    });

    // --- Lógica para abrir el modal en modo EDITAR ---
    $('#dataTableMotos tbody').on('click', '.btn-editar', function() {
        const placaId = $(this).data('id');
        $.ajax({
            url: '../ajax/procesar_moto.php', type: 'GET', data: { accion: 'obtener', id: placaId }, dataType: 'json',
            success: data => {
                if (data) {
                    formMoto[0].reset();
                    formMoto.find('.is-invalid').removeClass('is-invalid');
                    modalLabel.text('Editar Moto: ' + data.id_placa);
                    formMoto.find('[name="accion"]').val('actualizar');
                    formMoto.find('[name="placa_original"]').val(data.id_placa);
                    
                    placaInput.val(data.id_placa).prop('readonly', true); // La placa no se puede editar
                    formMoto.find('[name="id_documento_cli"]').val(data.id_documento_cli);
                    formMoto.find('[name="id_cilindraje"]').val(data.id_cilindraje);
                    formMoto.find('[name="id_referencia_marca"]').val(data.id_referencia_marca);
                    formMoto.find('[name="id_modelo"]').val(data.id_modelo);
                    formMoto.find('[name="id_color"]').val(data.id_color);

                    modalMoto.modal('show');
                }
            }
        });
    });

      const validarFormularioMoto = () => {
        let esValido = true;
        
        // 1. Validación de la placa
        const placaVal = placaInput.val();
        if (!regla_placa.regex.test(placaVal)) {
            placaInput.addClass('is-invalid').next('.invalid-feedback').text(regla_placa.mensaje);
            esValido = false;
        } else {
            placaInput.removeClass('is-invalid');
        }
        
        // 2. Validación de selects
        formMoto.find('select[required]').each(function() {
            const select = $(this);
            if (!select.val()) {
                select.addClass('is-invalid').next('.invalid-feedback').text('Debe seleccionar una opción.');
                esValido = false;
            } else {
                select.removeClass('is-invalid');
            }
        });

        return esValido;
    };

    // --- VALIDACIÓN Y ENVÍO DEL FORMULARIO ---
    formMoto.on('submit', function(e) {
        e.preventDefault();
        
        if (validarFormularioMoto()) {
            const formData = $(this).serialize();
            $.ajax({
                url: '../ajax/procesar_moto.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalMoto.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        } else {
             Swal.fire('Formulario inválido', 'Por favor, corrige los campos marcados en rojo.', 'warning');
        }
    });
    
   // ==========================================================
    // SECCIÓN DE LÓGICA PARA ELIMINAR (COMPLETADA)
    // ==========================================================
    
    let placaParaEliminar;

    // 1. Cuando se hace clic en un botón de eliminar de la tabla
    $('#dataTableMotos tbody').on('click', '.btn-eliminar', function() {
        // Guardamos la placa de la moto que se quiere eliminar
        placaParaEliminar = $(this).data('id');
        // Abrimos el modal de confirmación
        $('#modalEliminarMoto').modal('show');
    });

    // 2. Cuando el usuario hace clic en el botón "Eliminar" del modal
    $('#btnConfirmarEliminar').on('click', function() {
        // Deshabilitamos el botón para evitar clics múltiples
        $(this).prop('disabled', true).text('Eliminando...');

        $.ajax({
            url: '../ajax/procesar_moto.php',
            type: 'POST',
            data: { 
                accion: 'eliminar', 
                id_placa: placaParaEliminar // Enviamos la placa guardada
            },
            dataType: 'json',
            success: function(response) {
                // Cerramos el modal
                $('#modalEliminarMoto').modal('hide');
                
                // Mostramos la respuesta del servidor con SweetAlert
                Swal.fire({
                    icon: response.status, // 'success' o 'error'
                    title: response.status === 'success' ? '¡Eliminado!' : '¡Error!',
                    text: response.message
                }).then(() => {
                    // Si la eliminación fue exitosa, recargamos la página
                    if (response.status === 'success') {
                        location.reload();
                    }
                });
            },
            error: function() {
                $('#modalEliminarMoto').modal('hide');
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            },
            complete: function() {
                // Volvemos a habilitar el botón, sin importar el resultado
                $('#btnConfirmarEliminar').prop('disabled', false).text('Eliminar');
            }
        });
    });
});