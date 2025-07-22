// --- js/mantenimientos.js (VERSIÓN FINAL CORREGIDA Y ESTRUCTURADA) ---
$(document).ready(function() {
    
    // --- REFERENCIAS A ELEMENTOS DEL DOM ---
    const modal = $('#modalMantenimiento'); // Modal unificado
    const form = $('#formMantenimiento');
    const modalLabel = $('#modalMantenimientoLabel');
    const tablaDetalles = $('#tablaDetallesAgregados');
    const textoTotal = $('#textoTotal');
    const inputBuscarTrabajo = $('#inputBuscarTrabajo');
    const btnAnadirTrabajo = $('#btnAnadirTrabajo');
    const resultadosBusqueda = $('#resultadosBusqueda');
    const fechaRealizoInput = form.find('[name="fecha_realizo"]');
    const kilometrajeInput = form.find('[name="kilometraje"]');

    let detallesAgregados = [];
    let trabajoSeleccionado = null;

    // --- FUNCIONES REUTILIZABLES ---
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
    
    // --- LÓGICA DEL BUSCADOR DE TRABAJOS ---
    inputBuscarTrabajo.on('keyup', function() {
        const searchTerm = $(this).val();
        trabajoSeleccionado = null; // Reiniciar selección si se sigue escribiendo
        btnAnadirTrabajo.prop('disabled', true);
        if (searchTerm.length < 2) {
            resultadosBusqueda.empty().hide();
            return;
        }
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php', type: 'GET', data: { accion: 'buscar_trabajos', term: searchTerm }, dataType: 'json',
            success: data => {
                resultadosBusqueda.empty().show();
                data.forEach(trabajo => {
                    resultadosBusqueda.append(`<a href="#" class="list-group-item list-group-item-action resultado-trabajo" data-id="${trabajo.id_tipo}" data-precio="${trabajo.precio_unitario}" data-detalle="${trabajo.detalle}">${trabajo.detalle}</a>`);
                });
            }
        });
    });

    $(document).on('click', '.resultado-trabajo', function(e) {
        e.preventDefault();
        trabajoSeleccionado = {
            id: $(this).data('id'),
            precio: $(this).data('precio'),
            detalle: $(this).data('detalle')
        };
        inputBuscarTrabajo.val($(this).data('detalle'));
        btnAnadirTrabajo.prop('disabled', false);
        resultadosBusqueda.hide();
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
        inputBuscarTrabajo.val('');
        $('#inputCantidad').val(1);
        $(this).prop('disabled', true);
    });

    tablaDetalles.on('click', '.btn-quitar-detalle', function() {
        detallesAgregados.splice($(this).data('index'), 1);
        renderizarTablaDetalles();
    });

    // --- LÓGICA PARA ABRIR MODALES (AGREGAR Y EDITAR) ---
    $('[data-target="#modalAgregarMantenimiento"]').on('click', function() {
        form[0].reset();
        detallesAgregados = [];
        renderizarTablaDetalles();
        modalLabel.text('Registrar Nuevo Mantenimiento');
        form.find('[name="accion"]').val('agregar');
        form.find('[name="id_mantenimientos"]').val('0');
        form.find('[name="id_placa"]').prop('disabled', false); // La placa se puede seleccionar
        modal.modal('show');
    });

    $('#dataTableMantenimientos tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        $.ajax({
            url: '../ajax/procesar_mantenimiento.php', type: 'GET', data: { accion: 'obtener_mantenimiento', id: id }, dataType: 'json',
            success: data => {
                form[0].reset();
                modalLabel.text('Editar Mantenimiento #' + data.main.id_mantenimientos);
                form.find('[name="accion"]').val('actualizar');
                form.find('[name="id_mantenimientos"]').val(data.main.id_mantenimientos);

                form.find('[name="id_placa"]').val(data.main.id_placa).prop('disabled', true); // Placa no se puede cambiar
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
        });
    });

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

    // --- FUNCIÓN PARA VALIDAR SELECTS OBLIGATORIOS ---
    const validarSelect = (select) => {
        if (!select.val()) {
            select.addClass('is-invalid').next('.invalid-feedback').text('Debe seleccionar una opción.');
            return false;
        }
        select.removeClass('is-invalid').addClass('is-valid');
        return true;
    };


    // --- Asignar validaciones en tiempo real ---
    fechaRealizoInput.on('input', () => validarFechaNoFutura(fechaRealizoInput));
    kilometrajeInput.on('input', () => validarKilometraje(kilometrajeInput));
    form.find('[name="id_placa"]').on('change', (e) => validarSelect($(e.target)));

    // --- ENVÍO DEL FORMULARIO (AGREGAR Y EDITAR) MEJORADO ---
    form.on('submit', function(e) {
        e.preventDefault();

        // Ejecutar todas las validaciones
        const esPlacaValida = validarSelect(form.find('[name="id_placa"]'));
        const esFechaValida = validarFechaNoFutura(fechaRealizoInput);
        const esKmValido = validarKilometraje(kilometrajeInput);
        
        // Verificar si hay trabajos agregados
        const hayDetalles = detallesAgregados.length > 0;
        if (!hayDetalles) {
             Swal.fire('Error', 'Debe añadir al menos un trabajo realizado.', 'warning');
        }

        // Si todo es válido, proceder con AJAX
        if (esPlacaValida && esFechaValida && esKmValido && hayDetalles) {
            const formData = new FormData(this);
            formData.append('detalles', JSON.stringify(detallesAgregados));
            if (form.find('[name="id_placa"]').is(':disabled')) {
                formData.append('id_placa', form.find('[name="id_placa"]').val());
            }

            $.ajax({
                url: '../ajax/procesar_mantenimiento.php', type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
                success: response => {
                    modal.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => {
                        if(response.status === 'success') location.reload();
                    });
                }
            });
        }
    });
    
    // --- LÓGICA PARA VER DETALLES ---
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


    // --- LÓGICA PARA ELIMINAR ---
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

    // --- VALIDACIONES DE FECHAS EN EL FILTRO ---
    
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