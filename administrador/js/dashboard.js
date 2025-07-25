$(document).ready(function() {
    
    // Función para formatear a moneda colombiana
    const formatCurrency = (number) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(number || 0);

    // Variables para guardar las instancias de las gráficas y la tabla
    let areaChartInstance;
    let pieChartInstance;
    let dataTableAuditoria;

    // --- GRÁFICA DE ÁREA CON ESTILOS ---
    function initAreaChart(data) {
        if (areaChartInstance) { areaChartInstance.destroy(); }
        const ctx = document.getElementById("myAreaChart");
        if (!ctx) return;
        
        const labels = data.map(item => item.mes);
        const valores = data.map(item => item.total_mes);
        
        areaChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: "Ingresos",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    data: valores,
                }],
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { ticks: { callback: (value) => formatCurrency(value) } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => 'Ingresos: ' + formatCurrency(context.parsed.y) } }
                }
            }
        });
    }

    // --- GRÁFICA DE PASTEL CON ESTILOS ---
    function initPieChart(data) {
        if (pieChartInstance) { pieChartInstance.destroy(); }
        const ctx = document.getElementById("myPieChart");
        if (!ctx) return;
        
        const labels = data.map(item => item.nombre);
        const valores = data.map(item => item.total_motos);

        pieChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: valores,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: true, position: 'bottom' } }, cutout: '80%' }
        });
    }
    
    // --- FUNCIÓN PRINCIPAL PARA CARGAR Y MOSTRAR DATOS ---
    function cargarDatosDashboard() {
        $.ajax({
            url: 'ajax/obtener_datos_dashboard.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    
                    // Llenar tarjetas
                    $('#ganancias-mes').text(formatCurrency(data.ganancias_mes));
                    $('#ganancias-anio').text(formatCurrency(data.ganancias_anio));
                    $('#mantenimientos-mes').text(data.mantenimientos_mes);
                    $('#clientes-mes').text(data.clientes_mes); 
                    $('#motos-mes').text(data.motos_mes);       

                    // Llenar tabla de auditoría y LUEGO inicializar DataTable
                    const tablaAuditoriaBody = $('#tablaAuditoria tbody');
                    tablaAuditoriaBody.empty();
                    data.auditoria.forEach(item => {
                        const fecha = new Date(item.fecha_hora).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
                        tablaAuditoriaBody.append(`<tr><td>${fecha}</td><td>${item.tabla_afectada}</td><td>${item.accion_realizada}</td><td>${item.descripcion}</td><td>${item.id_admin} - ${item.nombre}</td></tr>`);
                    });

                    if ($.fn.DataTable.isDataTable('#tablaAuditoria')) {
                        $('#tablaAuditoria').DataTable().destroy();
                    }
                    dataTableAuditoria = $('#tablaAuditoria').DataTable({
                        "language": {
                        "sProcessing":     "Procesando...",
                        "sLengthMenu":     "Mostrar _MENU_ registros",
                        "sZeroRecords":    "No se encontraron resultados",
                        "sEmptyTable":     "No hay movimientos registrados",
                        "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                        "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                        "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                        "sInfoPostFix":    "",
                        "sSearch":         "Buscar:",
                        "sUrl":           "",
                        "sInfoThousands":  ",",
                        "sLoadingRecords": "Cargando...",
                        "oPaginate": {
                            "sFirst":    "Primero",
                            "sLast":     "Último",
                            "sNext":     "Siguiente",
                            "sPrevious": "Anterior"
                        },
                        "oAria": {
                            "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                            "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                        }
                    },
                    "pageLength": 5, // Mostrar 5 registros por página
                    "order": [[0, 'desc']], // Ordenar por fecha descendente
                    "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    "responsive": true,
                    "autoWidth": false,
                    "initComplete": function(settings, json) {
                        $('.dataTables_filter input').attr('placeholder', 'Buscar movimiento...');
                    }
                                    
                    });

                    // Inicializar gráficas
                    initAreaChart(data.grafica_area);
                    initPieChart(data.grafica_pie);
                }
            },
            error: () => console.error("No se pudieron cargar los datos del dashboard.")
        });
    }

    const modalReporte = $('#modalReporte');
    const reporteFechaInicioInput = $('#reporte_fecha_inicio');
    const reporteFechaFinInput = $('#reporte_fecha_fin');

    // Al abrir el modal, poner las fechas de hoy por defecto
    modalReporte.on('show.bs.modal', function() {
        const hoy = new Date().toISOString().split('T')[0];
        reporteFechaInicioInput.val(hoy);
        reporteFechaFinInput.val(hoy);
    });

    // Función para generar el reporte
    function generarReporte(formato) {
        const fechaInicio = reporteFechaInicioInput.val();
        const fechaFin = reporteFechaFinInput.val();
        const hoy = new Date().toISOString().split('T')[0];

        // Validaciones
        if (!fechaInicio || !fechaFin) {
            Swal.fire('Error', 'Debes seleccionar ambas fechas, "Inicio" y "Fin".', 'warning');
            return;
        }
        if (fechaInicio > hoy || fechaFin > hoy) {
            Swal.fire('Error', 'Las fechas no pueden ser futuras.', 'warning');
            return;
        }
        if (fechaFin < fechaInicio) {
            Swal.fire('Error', 'La "Fecha Fin" no puede ser anterior a la "Fecha Inicio".', 'warning');
            return;
        }

        // Construir la URL y abrirla
        const url = `reportes/reporte_dia.php?formato=${formato}&inicio=${fechaInicio}&fin=${fechaFin}`;
        
        if (formato === 'pdf') {
            window.open(url, '_blank'); // Abre PDF en nueva pestaña
        } else {
            window.location.href = url; // Descarga Excel
        }

        modalReporte.modal('hide');
    }

    // Asignar eventos a los botones del modal
    $('#btnGenerarExcel').on('click', () => generarReporte('excel'));
    $('#btnGenerarPdf').on('click', () => generarReporte('pdf'));


    // Cargar los datos al iniciar la página
    cargarDatosDashboard();
});