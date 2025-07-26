$(document).ready(function() {
    
    const formatCurrency = (number) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(number || 0);
    let dataTableMantenimientosDia;
    
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

                    // Llenar tabla de mantenimientos del día
                    const tablaBody = $('#tablaMantenimientosDia tbody');
                    const totalDiaElement = $('#totalMantenimientosDia');
                    tablaBody.empty();
                    
                    let totalDelDia = 0; // Variable para sumar el total

                    if (data.mantenimientos_dia && data.mantenimientos_dia.length > 0) {
                        data.mantenimientos_dia.forEach(item => {
                            const hora = new Date(item.fecha_realizo).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
                            const fila = `
                                <tr>
                                    <td>${hora}</td>
                                    <td>${item.id_placa}</td>
                                    <td>${item.cliente}</td>
                                    <td>${item.detalles_trabajos || 'N/A'}</td>
                                    <td class="text-right">${formatCurrency(item.total)}</td>
                                </tr>`;
                            tablaBody.append(fila);
                            totalDelDia += parseFloat(item.total); // Sumar al total del día
                        });
                    }

                    // Mostrar el total del día en el pie de la tabla
                    totalDiaElement.text(formatCurrency(totalDelDia));

                    // Inicializar DataTable
                    if ($.fn.DataTable.isDataTable('#tablaMantenimientosDia')) {
                        $('#tablaMantenimientosDia').DataTable().destroy();
                    }
                    dataTableMantenimientosDia = $('#tablaMantenimientosDia').DataTable({
                        "language": {
                            "sEmptyTable": "No hay mantenimientos registrados hoy",
                            "sProcessing": "Procesando...",
                            "sLengthMenu": "Mostrar _MENU_ registros",
                            "sZeroRecords": "No se encontraron resultados",
                            "sEmptyTable": "No hay mantenimientos registrados para hoy",
                            "sInfo": "Mostrando _START_ al _END_ de _TOTAL_ registros",
                            "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
                            "sSearch": "Buscar:",
                            "oPaginate": {
                                "sFirst": "Primero", "sLast": "Último",
                                "sNext": "Siguiente", "sPrevious": "Anterior"
                            }
                        },
                        "pageLength": 5,
                        "order": [[1, 'desc']], // Ordenar por hora descendente
                        "responsive": true
                    });

                    // Inicializar gráficas
                    initAreaChart(data.grafica_area);
                    initPieChart(data.grafica_pie);
                }
            },
            error: (jqXHR, textStatus, errorThrown) => {
                // Mensaje de error más detallado
                console.error("No se pudieron cargar los datos del dashboard. Estado:", textStatus, "Error:", errorThrown);
                // También podemos ver la respuesta del servidor si no es un JSON válido
                console.log("Respuesta del servidor:", jqXHR.responseText);
            }
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