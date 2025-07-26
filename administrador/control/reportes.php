<?php 
include '../header.php'; 
?>

<!-- Inicio del contenido de la página -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Generador de Reportes</h1>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Seleccione los Criterios del Reporte</h6>
        </div>
        <div class="card-body">
            <form id="formReportes" onsubmit="return false;">
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label for="tipo_reporte"><strong>1. Seleccione un tipo de reporte:</strong></label>
                        <select id="tipo_reporte" name="tipo" class="form-control">
                            <option value="">-- Seleccione --</option>
                            <optgroup label="Actividad y Finanzas">
                                <option value="actividad">Reportes de Actividad del sistema</option>
                                <option value="mantenimiento_de_hoy">Reportes de mantenimientos dia actual</option>
                                <option value="mantenimientos">Mantenimientos Realizados</option>
                            </optgroup>
                            <optgroup label="Taller">
                                <option value="clientes">Listado de Clientes</option>
                                <option value="motos">Listado de Motos</option>
                            </optgroup>
                            <optgroup label="Catálogos">
                                <option value="marcas">Listado de Marcas</option>
                                <option value="referencias">Listado de Referencias</option>
                                <option value="modelos">Listado de Modelos (Años)</option>
                                <option value="cilindrajes">Listado de Cilindrajes</option>
                                <option value="colores">Listado de Colores</option>
                                <option value="tipos_trabajos">Listado de Tipos de Trabajo</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- 2. Filtros Específicos -->
                <hr>
                <div id="contenedor-filtros">
                    <label><strong>2. Aplique filtros (opcional):</strong></label>
                    
                    <!-- Filtro por Rango de Fechas -->
                    <div id="filtro_fechas" class="filtro-dinamico" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Desde la fecha:</label><input type="date" class="form-control" name="inicio"></div>
                            <div class="col-md-6 form-group"><label>Hasta la fecha:</label><input type="date" class="form-control" name="fin"></div>
                        </div>
                    </div>
                    
                    <!-- Filtro por Búsqueda de Texto -->
                    <div id="filtro_texto" class="filtro-dinamico" style="display:none;">
                        <div class="row">
                            <div class="col-md-12 form-group"><label>Buscar por término:</label><input type="text" class="form-control" name="busqueda" placeholder="Escriba aquí para buscar..."></div>
                        </div>
                    </div>

                    <!--  Filtro por Rango Numérico -->
                    <div id="filtro_number" class="filtro-dinamico" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Desde:</label>
                                <input type="number" class="form-control" name="rango_inicio" placeholder="Ej: 100">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Hasta:</label>
                                <input type="number" class="form-control" name="rango_fin" placeholder="Ej: 200">
                            </div>
                        </div>
                    </div>

                    <div id="placeholder-filtros" class="text-center text-muted mt-3">
                        <p>Seleccione un tipo de reporte para ver los filtros disponibles.</p>
                    </div>
                </div>

                <!-- 3. Botones de Acción -->
                <hr>
                <hr>
                <div class="text-right">
                    <button type="button" class="btn btn-info" id="btnVisualizarReporte">
                        <i class="fas fa-search"></i> Visualizar Reporte
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de resultados  -->
    <div class="card shadow mb-4" id="cardResultados" style="display: none;">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary" id="tituloResultado">Resultados del Reporte</h6>
            <div>
                <button type="button" class="btn btn-success btn-sm btn-exportar" data-formato="excel"><i class="fas fa-file-excel"></i> Exportar a Excel</button>
                <button type="button" class="btn btn-danger btn-sm btn-exportar" data-formato="pdf"><i class="fas fa-file-pdf"></i> Exportar a PDF</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaResultados" width="100%" cellspacing="0">
                    <!-- El thead y tbody se generarán con JavaScript -->
                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../scripts.php'; ?>
<script src="../js/reportes.js"></script>