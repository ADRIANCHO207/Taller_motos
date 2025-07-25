<?php include 'header.php'; ?>

<div class="container-fluid">
    <!-- Encabezado de la página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <!-- Menú desplegable para generar reportes -->
        <div class="dropdown">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalReporte">
                <i class="fas fa-download fa-sm text-white-50"></i> Generar Reporte de Actividad
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="reportes/reporte_dia.php?formato=excel"><i class="fas fa-file-excel"></i> Exportar a Excel</a>
                <a class="dropdown-item" href="reportes/reporte_dia.php?formato=pdf" target="_blank"><i class="fas fa-file-pdf"></i> Exportar a PDF</a>
            </div>
        </div>
    </div>

    <!-- Fila de Tarjetas -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Ganancias (Mensual)</div>
                    <div id="ganancias-mes" class="h5 mb-0 font-weight-bold text-gray-800">Cargando...</div>
                </div><div class="col-auto"><i class="fas fa-calendar fa-2x text-gray-300"></i></div>
            </div></div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ganancias (Anual)</div>
                    <div id="ganancias-anio" class="h5 mb-0 font-weight-bold text-gray-800">Cargando...</div>
                </div><div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
            </div></div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mantenimientos (Mes)</div>
                    <div id="mantenimientos-mes" class="h5 mb-0 font-weight-bold text-gray-800">Cargando...</div>
                </div><div class="col-auto"><i class="fas fa-tools fa-2x text-gray-300"></i></div>
            </div></div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Nuevos Clientes (Mes)</div>
                    <div id="clientes-mes" class="h5 mb-0 font-weight-bold text-gray-800">Cargando...</div>
                </div><div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
            </div></div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Nuevas Motos (Mes)</div>
                            <div id="motos-mes" class="h5 mb-0 font-weight-bold text-gray-800">Cargando...</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-motorcycle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila de Gráficas -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Resumen de Ingresos Anuales</h6></div>
                <div class="card-body"><div class="chart-area"><canvas id="myAreaChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Top 5 Clientes por Cantidad de Motos</h6></div>
                <div class="card-body"><div class="chart-pie pt-4 pb-2"><canvas id="myPieChart"></canvas></div></div>
            </div>
        </div>
    </div>

    <!-- Fila de Auditoría -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Movimientos Diaros del Sistema</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="tablaAuditoria" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Módulo</th>
                                    <th>Acción</th>
                                    <th>Descripción</th>
                                    <th>Realizado por:</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Contenido se llenará con JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MODAL PARA SELECCIONAR FECHA DE REPORTE === -->
<div class="modal fade" id="modalReporte" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generar Reporte de Actividad</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Seleccione el rango de fechas para generar el reporte de actividad.</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reporte_fecha_inicio">Fecha Inicio:</label>
                            <input type="date" class="form-control" id="reporte_fecha_inicio">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reporte_fecha_fin">Fecha Fin:</label>
                            <input type="date" class="form-control" id="reporte_fecha_fin">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGenerarExcel"><i class="fas fa-file-excel"></i> Generar Excel</button>
                <button type="button" class="btn btn-danger" id="btnGenerarPdf"><i class="fas fa-file-pdf"></i> Generar PDF</button>
            </div>
        </div>
    </div>
</div>

<?php include 'scripts.php'; ?>
<!-- Script específico para el Dashboard -->
<script src="js/dashboard.js"></script>