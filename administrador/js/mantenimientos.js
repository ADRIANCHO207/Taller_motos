$(document).ready(function() {

    // ==========================================================
    // SECCIÓN 1: REFERENCIAS Y ESTADO GLOBAL
    // ==========================================================
    const modal = $('#modalMantenimiento');
    const form = $('#formMantenimiento');
    const modalLabel = $('#modalMantenimientoLabel');
    const tablaDetalles = $('#tablaDetallesAgregados');
    const textoTotal = $('#textoTotal');
    const inputBuscarMoto = $('#inputBuscarMoto');
    const resultadosBusquedaMoto = $('#resultadosBusquedaMoto');
    const inputBuscarTrabajo = $('#inputBuscarTrabajo');
    const resultadosBusquedaTrabajo = $('#resultadosBusqueda');
    const btnAnadirTrabajo = $('#btnAnadirTrabajo');
    

    let detallesAgregados = [];
    let trabajoSeleccionado = null;
    let motoSeleccionada = null; // Guardará { placa: 'ABC12D', cilindraje: 150 }

    // ==========================================================
    // SECCIÓN 2: FUNCIONES REUTILIZABLES (VALIDACIÓN Y RENDERIZADO)
    // ==========================================================
    
     const calcularTotal = () => {
        const total = detallesAgregados.reduce((sum, item) => sum + item.subtotal, 0);
        textoTotal.text(`$ ${total.toLocaleString('es-CO', {minimumFractionDigits: 2})}`);
        form.find('[name="total"]').val(total);
    };

    const renderizarTablaDetalles = () => {
        tablaDetalles.empty();
        detallesAgregados.forEach((item, index) => {
            const fila = `
                <tr>
                    <td>${item.detalle}</td>
                    <td>${item.cantidad}</td>
                    <td>$ ${parseFloat(item.precio_unitario).toLocaleString('es-CO')}</td>
                    <td>$ ${parseFloat(item.subtotal).toLocaleString('es-CO')}</td>
                    <td><button type="button" class="btn btn-danger btn-sm btn-quitar-detalle" data-index="${index}">×</button></td>
                </tr>`;
            tablaDetalles.append(fila);
        });
        calcularTotal();
    };

    const validarFechaNoFutura = (input) => {
        const feedback = input.next('.invalid-feedback');
        const fechaSeleccionadaStr = input.val();
        
        if (!fechaSeleccionadaStr) {
            input.addClass('is-invalid');
            feedback.text('La fecha y hora son obligatorias.');
            return false;
        }
        
        if (new Date(fechaSeleccionadaStr) > new Date()) {
            input.addClass('is-invalid');
            feedback.text('La fecha y hora no pueden ser futuras.');
            return false;
        }
        
        input.removeClass('is-invalid').addClass('is-valid');
        return true;
    };

    // --- FUNCIÓN PARA VALIDAR EL KILOMETRAJE ---
    const validarKilometraje = (input) => {
        const feedback = input.next('.invalid-feedback');
        const valorStr = input.val();
        
        if (!valorStr) {
            input.addClass('is-invalid');
            feedback.text('El kilometraje es obligatorio.');
            return false;
        }

        const valor = parseInt(valorStr, 10);
        // Expresión regular para verificar que solo sean números y de 1 a 6 dígitos
        const regex = /^\d{1,6}$/; 
        
        if (isNaN(valor) || !regex.test(valorStr) || valor < 0) {
            input.addClass('is-invalid');
            feedback.text('Debe ser un número de hasta 6 dígitos.');
            return false;
        }

        input.removeClass('is-invalid').addClass('is-valid');
        return true;
    };
    
    const validarMotoSeleccionada = () => {
        const inputGroup = inputBuscarMoto.closest('.form-group');
        if (motoSeleccionada && form.find('[name="id_placa"]').val()) {
            inputBuscarMoto.removeClass('is-invalid').addClass('is-valid');
            inputGroup.find('.invalid-feedback').text('');
            return true;
        }
        inputBuscarMoto.removeClass('is-valid').addClass('is-invalid');
        inputGroup.find('.invalid-feedback').text('Debe seleccionar una moto de la lista.');
        return false;
    };

    const validarCampo = (input, validador) => {
        const feedback = input.next('.invalid-feedback');
        const valor = input.val().trim();
        
        if (!valor) {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.text('Este campo es obligatorio');
            return false;
        }
        
        const resultado = validador(valor);
        if (resultado === true) {
            input.removeClass('is-invalid').addClass('is-valid');
            feedback.text('');
            return true;
        } else {
            input.removeClass('is-valid').addClass('is-invalid');
            feedback.text(resultado);
            return false;
        }
    };

    const validarObservaciones = (valor) => {
        if (valor.length > 500) {
            return 'Las observaciones no pueden exceder los 500 caracteres';
        }
        return true;
    };

    // ==========================================================
    // SECCIÓN 3: LÓGICA DE LOS BUSCADORES Y DETALLES
    // ==========================================================

    // --- Buscador de Motos ---
    inputBuscarMoto.on('keyup', function() {
        motoSeleccionada = null;
        form.find('[name="id_placa"]').val('');
        detallesAgregados = [];
        renderizarTablaDetalles();
        inputBuscarTrabajo.val('');
        inputBuscarMoto.removeClass('is-valid is-invalid');

        const searchTerm = $(this).val();
        if (searchTerm.length < 2) {
            resultadosBusquedaMoto.empty().hide();
            return;
        }
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php',
            type: 'GET',
            data: { accion: 'buscar_motos', term: searchTerm },
            dataType: 'json',
            success: data => {
                resultadosBusquedaMoto.empty().show();
                data.forEach(moto => {
                    const texto = `${moto.id_placa} - ${moto.nombre}`;
                    resultadosBusquedaMoto.append(`<a href="#" class="list-group-item list-group-item-action resultado-moto" 
                        data-placa="${moto.id_placa}" data-cilindraje="${moto.cilindraje}" data-texto="${texto}">
                        ${texto}
                    </a>`);
                });
            }
        });
    });

    // Evento al seleccionar una moto de la lista
    $(document).on('click', '.resultado-moto', function(e) {
        e.preventDefault();
        motoSeleccionada = { placa: $(this).data('placa'), cilindraje: $(this).data('cilindraje') };
        inputBuscarMoto.val($(this).data('texto'));
        form.find('[name="id_placa"]').val(motoSeleccionada.placa);
        resultadosBusquedaMoto.hide();
        validarMotoSeleccionada(); // Validar inmediatamente
    });
    
    // --- Buscador de Trabajos (con validación de CC) ---
    inputBuscarTrabajo.on('keyup', function() {
        if (!motoSeleccionada) {
            Swal.fire('Atención', 'Primero debes seleccionar una moto.', 'warning');
            $(this).val('');
            return;
        }
        const searchTerm = $(this).val();
        trabajoSeleccionado = null;
        btnAnadirTrabajo.prop('disabled', true);
        if (searchTerm.length < 2) {
            resultadosBusquedaTrabajo.empty().hide();
            return;
        }
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php',
            type: 'GET',
            data: { 
                accion: 'buscar_trabajos', 
                term: searchTerm,
                cilindraje: motoSeleccionada.cilindraje // ¡Enviamos el CC!
            },
            dataType: 'json',
            success: data => {
                resultadosBusquedaTrabajo.empty().show();
                if (data.length > 0) {
                    data.forEach(trabajo => {
                        resultadosBusquedaTrabajo.append(`<a href="#" class="list-group-item list-group-item-action resultado-trabajo" data-id="${trabajo.id_tipo}" data-precio="${trabajo.precio_unitario}" data-detalle="${trabajo.detalle}">${trabajo.detalle}</a>`);
                    });
                } else {
                     resultadosBusquedaTrabajo.html('<div class="list-group-item text-muted">No se encontraron trabajos para este cilindraje.</div>');
                }
            }
        });
    });

    // Evento al seleccionar un trabajo de la lista
   $(document).on('click', '.resultado-trabajo', function(e) {
        e.preventDefault();
        trabajoSeleccionado = {
            id: $(this).data('id'),
            precio: $(this).data('precio'),
            detalle: $(this).data('detalle')
        };
       inputBuscarTrabajo.val($(this).data('detalle'));
        btnAnadirTrabajo.prop('disabled', false);
        resultadosBusquedaTrabajo.empty().hide(); // Ocultamos y vaciamos la lista
    });


    btnAnadirTrabajo.on('click', function() {
        if (!trabajoSeleccionado) return;
        const cantidad = parseInt($('#inputCantidad').val());
        if (isNaN(cantidad) || cantidad < 1) {
            Swal.fire('Error', 'La cantidad debe ser un número válido mayor a cero.', 'warning');
            return;
        }

        detallesAgregados.push({
            id_tipo: trabajoSeleccionado.id,
            detalle: trabajoSeleccionado.detalle,
            cantidad: cantidad,
            precio_unitario: trabajoSeleccionado.precio,
            subtotal: trabajoSeleccionado.precio * cantidad
        });

        renderizarTablaDetalles();
        trabajoSeleccionado = null;
        inputBuscarTrabajo.val(''); // Limpiamos el input de búsqueda
        $('#inputCantidad').val(1);
        $(this).prop('disabled', true); // Deshabilitamos el botón de añadir
    });

    tablaDetalles.on('click', '.btn-quitar-detalle', function() {
        detallesAgregados.splice($(this).data('index'), 1);
        renderizarTablaDetalles();
    });


    // ==========================================================
    // SECCIÓN 4: MANEJO DE MODALES (ABRIR, ENVIAR)
    // ==========================================================

    // --- Abrir modal para AGREGAR ---
   $('[data-target="#modalAgregarMantenimiento"]').on('click', function() {
        form[0].reset();
        form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        detallesAgregados = [];
        renderizarTablaDetalles();
        motoSeleccionada = null;
        modalLabel.text('Registrar Nuevo Mantenimiento');
        form.find('[name="accion"]').val('agregar');
        form.find('[name="id_mantenimientos"]').val('0');
        inputBuscarMoto.prop('disabled', false); // El buscador de moto está HABILITADO
        modal.modal('show');
    });

    // --- Abrir modal para EDITAR ---
    $('#dataTableMantenimientos tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php', type: 'GET', data: { accion: 'obtener_mantenimiento', id: id }, dataType: 'json',
            success: data => {
                if(data && data.main) {
                    form[0].reset();
                    form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    
                    modalLabel.text('Editar Mantenimiento #' + data.main.id_mantenimientos);
                    form.find('[name="accion"]').val('actualizar');
                    form.find('[name="id_mantenimientos"]').val(data.main.id_mantenimientos);

                    // --- ¡LÓGICA MEJORADA PARA MODO EDICIÓN! ---
                    const textoMoto = `${data.main.id_placa} - ${data.main.nombre_cliente}`;
                    motoSeleccionada = { placa: data.main.id_placa, cilindraje: data.main.cilindraje };
                    
                    // Llenamos el input de búsqueda y lo deshabilitamos
                    inputBuscarMoto.val(textoMoto).prop('disabled', true); 
                    // Llenamos el campo oculto con la placa
                    form.find('[name="id_placa"]').val(data.main.id_placa);
                    form.find('[name="fecha_realizo"]').val(data.main.fecha_realizo.replace(' ', 'T'));
                    form.find('[name="kilometraje"]').val(data.main.kilometraje);
                    form.find('[name="observaciones_entrada"]').val(data.main.observaciones_entrada);
                    form.find('[name="observaciones_salida"]').val(data.main.observaciones_salida);
               
                    detallesAgregados = data.details.map(d => ({
                        id_tipo: d.id_tipo, detalle: d.detalle, cantidad: d.cantidad,
                        precio_unitario: parseFloat(d.precio_unitario),
                        subtotal: d.cantidad * parseFloat(d.precio_unitario)
                    }));
                    renderizarTablaDetalles();
                
                    modal.modal('show');
                }
            }
        });
    });

    // --- ENVÍO DEL FORMULARIO (Agregar y Editar) ---
    form.on('submit', function(e) {
        e.preventDefault();

        const esMotoValida = validarMotoSeleccionada();
        const esFechaValida = validarFechaNoFutura(form.find('[name="fecha_realizo"]'));
        const esKmValido = validarKilometraje(form.find('[name="kilometraje"]'));
        
        const hayDetalles = detallesAgregados.length > 0;
        if (!hayDetalles) Swal.fire('Error', 'Debe añadir al menos un trabajo realizado.', 'warning');

        if (esMotoValida && esFechaValida && esKmValido && hayDetalles) {
            const formData = new FormData(this);
            formData.append('detalles', JSON.stringify(detallesAgregados));
            
            if (inputBuscarMoto.is(':disabled')) {
                formData.set('id_placa', motoSeleccionada.placa);
            }

            $.ajax({
                url: '../ajax/procesar_mantenimiento.php', type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
                success: response => {
                    modal.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status)
                    .then(() => { if(response.status === 'success') location.reload(); });
                }
            });
        }
    });
    
    // --- VER DETALLES Y ELIMINAR ---

    $('#dataTableMantenimientos tbody').on('click', '.btn-ver-detalles', function() {
        
        const id = $(this).data('id');
        const fila = $(this).closest('tr');
        const info = `<strong>Placa:</strong> ${fila.find('td:eq(1)').text()} | <strong>Cliente:</strong> ${fila.find('td:eq(2)').text()} | <strong>Fecha:</strong> ${fila.find('td:eq(3)').text()}`;
        $('#obsEntrada').text(fila.data('obs-entrada') || 'N/A');
        $('#obsSalida').text(fila.data('obs-salida') || 'N/A');

        $('#infoGeneralMantenimiento').html(info);
        $('#detallesBody').html('<tr><td colspan="3" class="text-center">Cargando...</td></tr>');
        
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php', type: 'GET', data: { accion: 'obtener_detalles', id: id }, dataType: 'json',
            success: data => {
                $('#detallesBody').empty();
                if(data.length > 0) {
                    data.forEach(item => {
                        const filaDetalle = `<tr><td>${item.detalle}</td><td>${item.cantidad}</td><td>$ ${parseFloat(item.subtotal).toLocaleString('es-CO')}</td></tr>`;
                        $('#detallesBody').append(filaDetalle);
                    });
                } else {
                    $('#detallesBody').html('<tr><td colspan="3" class="text-center">No se encontraron detalles.</td></tr>');
                }
            }
        });

        $('#modalVerDetalles').modal('show');
    });


    let idParaEliminar;
    $('#dataTableMantenimientos tbody').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        $('#modalEliminarMantenimiento').modal('show');
    });

    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php', type: 'POST', data: { accion: 'eliminar', id: idParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarMantenimiento').modal('hide');
                Swal.fire(response.status === 'success' ? '¡Eliminado!' : 'Error', response.message, response.status).then(() => {
                    if(response.status === 'success') location.reload();
                });
            }
        });
    });

    // --- VALIDACIONES DE FILTROS ---
    const formFiltros = $('#formFiltros');
    const fechaInicioInput = formFiltros.find('[name="filtro_inicio"]');
    const fechaFinInput = formFiltros.find('[name="filtro_fin"]');

    formFiltros.on('submit', function(e) {
        const fechaInicio = fechaInicioInput.val();
        const fechaFin = fechaFinInput.val();
        
        // Obtenemos la fecha de hoy en formato YYYY-MM-DD para una comparación fácil
        const hoy = new Date().toISOString().split('T')[0];

        // 1. Validar que la fecha de inicio no sea futura
        if (fechaInicio && fechaInicio > hoy) {
            e.preventDefault(); // Detenemos el envío del formulario
            Swal.fire({
                icon: 'error',
                title: 'Fecha incorrecta',
                text: 'La "Fecha Inicio" no puede ser una fecha futura.'
            });
            return; // Salimos para no mostrar más alertas
        }

        // 2. Validar que la fecha de fin no sea futura
        if (fechaFin && fechaFin > hoy) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Fecha incorrecta',
                text: 'La "Fecha Fin" no puede ser una fecha futura.'
            });
            return;
        }

        // 3. Validar que la fecha de fin no sea menor que la de inicio (solo si ambas están puestas)
        if (fechaInicio && fechaFin && fechaFin < fechaInicio) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Rango incorrecto',
                text: 'La "Fecha Fin" no puede ser anterior a la "Fecha Inicio".'
            });
        }
    });
});

// Validación en tiempo real para la fecha
$(document).ready(function() {
    form.find('[name="fecha_realizo"]').on('input change', function() {
        validarFechaNoFutura($(this));
    });

    // Validación en tiempo real para kilometraje
    form.find('[name="kilometraje"]').on('input', function() {
        validarKilometraje($(this));
    });

    // Validación en tiempo real para moto seleccionada
    inputBuscarMoto.on('input change', function() {
        validarMotoSeleccionada();
    });

    // Validación en tiempo real para observaciones de entrada
    form.find('[name="observaciones_entrada"]').on('input', function() {
        validarCampo($(this), validarObservaciones);
    });

    // Validación en tiempo real para observaciones de salida
    form.find('[name="observaciones_salida"]').on('input', function() {
        validarCampo($(this), validarObservaciones);
    });

    // Validación en tiempo real para cantidad de trabajos
    $('#inputCantidad').on('input', function() {
        const valor = parseInt($(this).val());
        const feedback = $(this).next('.invalid-feedback');
        
        if (isNaN(valor) || valor < 1) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            feedback.text('La cantidad debe ser mayor a 0');
            btnAnadirTrabajo.prop('disabled', true);
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            feedback.text('');
            if (trabajoSeleccionado) {
                btnAnadirTrabajo.prop('disabled', false);
            }
        }
    });

    // Modificar el evento submit para validar antes de enviar
    form.on('submit', function(e) {
        e.preventDefault();
        
        // Validar todos los campos
        const validaciones = {
            moto: validarMotoSeleccionada(),
            fecha: validarFechaNoFutura(form.find('[name="fecha_realizo"]')),
            kilometraje: validarKilometraje(form.find('[name="kilometraje"]')),
            obsEntrada: validarCampo(form.find('[name="observaciones_entrada"]'), validarObservaciones),
            obsSalida: validarCampo(form.find('[name="observaciones_salida"]'), validarObservaciones)
        };

        // Verificar si hay trabajos agregados
        const hayDetalles = detallesAgregados.length > 0;
        if (!hayDetalles) {
            Swal.fire({
                icon: 'warning',
                title: 'Faltan trabajos',
                text: 'Debe añadir al menos un trabajo realizado.'
            });
            return;
        }

        // Verificar todas las validaciones
        const todosValidos = Object.values(validaciones).every(v => v === true);
        
        if (!todosValidos) {
            Swal.fire({
                icon: 'warning',
                title: 'Formulario incompleto',
                text: 'Por favor, revise los campos marcados en rojo.'
            });
            return;
        }

        // Si todo está válido, proceder con el envío
        const formData = new FormData(this);
        formData.append('detalles', JSON.stringify(detallesAgregados));
        
        if (inputBuscarMoto.is(':disabled')) {
            formData.set('id_placa', motoSeleccionada.placa);
        }

        // Envío del formulario
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: response => {
                modal.modal('hide');
                Swal.fire({
                    icon: response.status === 'success' ? 'success' : 'error',
                    title: response.status === 'success' ? '¡Éxito!' : 'Error',
                    text: response.message
                }).then(() => {
                    if(response.status === 'success') location.reload();
                });
            },
            error: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al procesar la solicitud.'
                });
            }
        });
    });

    // Validaciones en tiempo real
    const setupValidacionesEnTiempoReal = () => {
        // Validación de fecha
        form.find('[name="fecha_realizo"]').on('input blur', function() {
            validarFechaNoFutura($(this));
        });

        // Validación de kilometraje
        form.find('[name="kilometraje"]').on('input blur', function() {
            validarKilometraje($(this));
        });

        // Validación de moto
        inputBuscarMoto.on('input blur', function() {
            validarMotoSeleccionada();
        });

        // Validación de observaciones entrada
        form.find('[name="observaciones_entrada"]').on('input blur', function() {
            validarCampo($(this), validarObservaciones);
        });

        // Validación de observaciones salida
        form.find('[name="observaciones_salida"]').on('input blur', function() {
            validarCampo($(this), validarObservaciones);
        });

        // Validación de cantidad
        $('#inputCantidad').on('input blur', function() {
            const valor = parseInt($(this).val());
            const feedback = $(this).next('.invalid-feedback');
            
            if (isNaN(valor) || valor < 1) {
                $(this).removeClass('is-valid').addClass('is-invalid');
                feedback.text('La cantidad debe ser mayor a 0');
                btnAnadirTrabajo.prop('disabled', true);
            } else {
                $(this).removeClass('is-invalid').addClass('is-valid');
                feedback.text('');
                if (trabajoSeleccionado) {
                    btnAnadirTrabajo.prop('disabled', false);
                }
            }
        });
    };

    // Inicializar validaciones cuando se abre el modal
    modal.on('shown.bs.modal', function() {
        setupValidacionesEnTiempoReal();
        
        // Validar campos si es edición
        if (form.find('[name="accion"]').val() === 'actualizar') {
            validarFechaNoFutura(form.find('[name="fecha_realizo"]'));
            validarKilometraje(form.find('[name="kilometraje"]'));
            validarMotoSeleccionada();
            validarCampo(form.find('[name="observaciones_entrada"]'), validarObservaciones);
            validarCampo(form.find('[name="observaciones_salida"]'), validarObservaciones);
        }
    });
});