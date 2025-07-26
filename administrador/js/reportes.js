$(document).ready(function() {
    
    const tipoReporteSelect = $('#tipo_reporte');
    const contenedorFiltros = $('#contenedor-filtros');
    const cardResultados = $('#cardResultados');
    let dataTableInstance = null;
    let parametrosActuales = '';

    // ¡CONFIGURACIÓN!
    const configFiltros = {
        'actividad': ['fechas', 'texto'],
        'mantenimiento_de_hoy': [],
        'mantenimientos': ['fechas', 'texto'],
        'clientes': ['fechas', 'texto'],
        'motos': ['texto'],
        'marcas': ['texto'],
        'referencias': ['texto'],
        'modelos': ['number'], // Usará el nuevo filtro numérico
        'cilindrajes': ['number'], // Usará el nuevo filtro numérico
        'colores': ['texto'],
        'tipos_trabajos': ['texto']
    };

    tipoReporteSelect.on('change', function() {
        const tipo = $(this).val();
        contenedorFiltros.find('.filtro-dinamico').hide();
        cardResultados.fadeOut();
        parametrosActuales = ''; // Limpiar parámetros al cambiar de reporte

        if (tipo && configFiltros[tipo]) {
            if (configFiltros[tipo].length === 0) {
                $('#placeholder-filtros').text('Este reporte no requiere filtros.').show();
            } else {
                $('#placeholder-filtros').hide();
                configFiltros[tipo].forEach(filtro => $('#filtro_' + filtro).fadeIn());
            }
        } else {
            $('#placeholder-filtros').text('Seleccione un tipo de reporte.').show();
        }
    });

    function validarYConstruirParametros() {
    const tipo = tipoReporteSelect.val();
    if (!tipo) {
        Swal.fire('Error', 'Debes seleccionar un tipo de reporte.', 'warning');
        return null;
    }

    let params = { tipo: tipo };

    // --- VALIDACIÓN DE FECHAS ---
   if ($('#filtro_fechas').is(':visible')) {
            const inicio = $('[name="inicio"]').val();
            const fin = $('[name="fin"]').val();
        
        // Obtener la fecha de hoy en formato YYYY-MM-DD
        const hoy = new Date().toISOString().split('T')[0];

        if (!inicio || !fin) {
            Swal.fire('Error', 'Debes seleccionar una fecha de inicio y una de fin.', 'warning');
            return null; // Detener la ejecución
        }
        
        // Comprobar que las fechas no sean futuras
        if (inicio > hoy || fin > hoy) {
            Swal.fire('Error', 'Las fechas no pueden ser futuras al día de hoy.', 'warning');
            return null;
        }

        if (fin < inicio) {
            Swal.fire('Error', 'La fecha de fin no puede ser anterior a la de inicio.', 'warning');
            return null;
        }

        // Si todas las validaciones pasan, añadimos las fechas
        params.inicio = inicio;
        params.fin = fin;
    }
    
    if ($('#filtro_texto').is(':visible')) {
        params.busqueda = $('[name="busqueda"]').val();
    }

     //  VALIDACIÓN PARA FILTROS NUMÉRICOS
    if ($('#filtro_number').is(':visible')) {
        const inicio = $('[name="rango_inicio"]').val();
        const fin = $('[name="rango_fin"]').val();
        
        // Validar que ambos estén llenos si uno lo está
        if ((inicio && !fin) || (!inicio && fin)) {
             Swal.fire('Error', 'Debes completar ambos campos del rango ("Desde" y "Hasta").', 'warning');
             return null;
        }
        
        if (inicio && fin) {
            const numInicio = parseInt(inicio);
            const numFin = parseInt(fin);
            if (isNaN(numInicio) || isNaN(numFin)) {
                Swal.fire('Error', 'Los valores del rango deben ser números.', 'warning');
                return null;
            }
            if (numFin < numInicio) {
                Swal.fire('Error', 'El valor "Hasta" no puede ser menor que "Desde".', 'warning');
                return null;
            }
            params.rango_inicio = numInicio;
            params.rango_fin = numFin;
        }
    }

    return $.param(params); // Siempre devuelve los parámetros si no hubo error
}

    // --- VISUALIZAR EL REPORTE  ---
    $('#btnVisualizarReporte').on('click', function() {
        const btn = $(this);
        parametrosActuales = validarYConstruirParametros();
        if (!parametrosActuales) return;

        $.ajax({
            url: '../reportes/generar_reporte.php', 
            type: 'GET',
            data: parametrosActuales,
            dataType: 'json',
            beforeSend: () => btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cargando...'),
            success: function(response) {
                if (response.status === 'success') {
                    if (dataTableInstance) { dataTableInstance.destroy(); }
                    const tablaResultados = $('#tablaResultados').empty();

                    let thead = '<thead><tr>';
                    response.encabezados.forEach(enc => thead += `<th>${enc}</th>`);
                    thead += '</tr></thead>';
                    
                    let tbody = '<tbody>';
                    response.data.forEach(fila => {
                        tbody += '<tr>';
                        Object.values(fila).forEach(valor => tbody += `<td>${valor || ''}</td>`);
                        tbody += '</tr>';
                    });
                    tbody += '</tbody>';
                    
                    tablaResultados.append(thead).append(tbody);
                    
                    dataTableInstance = $('#tablaResultados').DataTable({
                        "language": {
                            "sProcessing":     "Procesando...",
                            "sLengthMenu":     "Mostrar _MENU_ registros",
                            "sZeroRecords":    "No se encontraron resultados",
                            "sEmptyTable":     "No hay datos disponibles",
                            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                            "sInfoPostFix":    "",
                            "sInfoThousands":  ",",
                            "sLoadingRecords": "Cargando...",
                            "oPaginate": {
                                "sFirst":    "Primero",
                                "sLast":     "Último",
                                "sNext":     "Siguiente",
                                "sPrevious": "Anterior"
                            }
                        },
                        "responsive": true,
                        "pageLength": 10,
                        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'>>" + 
                               "<'row'<'col-sm-12'tr>>" +
                               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                        "order": [[0, 'desc']],
                        "autoWidth": false
                    });

                    $('#tituloResultado').text(response.titulo);
                    cardResultados.fadeIn();
                } else {
                    Swal.fire('Sin resultados', response.message, 'info');
                    cardResultados.fadeOut();
                }
            },
            error: () => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'),
            complete: () => btn.prop('disabled', false).html('<i class="fas fa-search"></i> Visualizar Reporte')
        });
    });

    // --- EXPORTAR ---
    function generarExportacion(formato) {
        let parametrosParaExportar = parametrosActuales;
        if (!parametrosParaExportar) {
            parametrosParaExportar = validarYConstruirParametros();
        }
        if (!parametrosParaExportar) {
            Swal.fire('Atención', 'Debes seleccionar los filtros antes de exportar.', 'info');
            return;
        }
        
        const url = `../reportes/generar_reporte.php?formato=${formato}&${parametrosParaExportar}`;
        if (formato === 'pdf') {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }

    $(document).on('click', '.btn-exportar', function() {
        generarExportacion($(this).data('formato'));
    });
});