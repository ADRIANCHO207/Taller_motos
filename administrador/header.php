<?php

// Definición de constantes para rutas
if (!defined('BASE_URL')) {
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        define('BASE_URL', '/Taller_motos');
    } else {
        define('BASE_URL', '');
    }
}

// Definir rutas globales
define('ADMIN_URL', BASE_URL . '/administrador');
define('IMG_URL', BASE_URL . '/img');
define('VENDOR_URL', ADMIN_URL . '/vendor');
define('CSS_URL', ADMIN_URL . '/css');
define('JS_URL', ADMIN_URL . '/js');

// Iniciar sesión y validar la sesión del usuario

session_start();
require_once('C:/xampp/htdocs/Taller_motos/conecct/conex.php');
include 'C:/xampp/htdocs/Taller_motos/includes/validarsession.php';



$db = new Database();
$con = $db->conectar();

if (!isset($_SESSION['id_documento']) || !isset($_SESSION['nombre'])) {
    header('Location: login.php');
    exit();
}

// Obtiene el nombre del administrador desde la sesión
$nombre_administrador = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'administrador';
$documento_administrador = isset($_SESSION['id_documento']) ? $_SESSION['id_documento'] : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="<?php echo IMG_URL; ?>/logo.png" type="image/x-icon">

    <title>Administrador - Taller de Motos</title>

    <!-- Fuentes personalizadas para esta plantilla -->
    <link href="<?php echo VENDOR_URL; ?>/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Estilos personalizados para esta plantilla -->
    <link href="<?php echo CSS_URL; ?>/sb-admin-2.min.css" rel="stylesheet">
    <link href="<?php echo CSS_URL; ?>/general.css" rel="stylesheet">
    <!-- Custom styles for datatables -->
    <link href="<?php echo VENDOR_URL; ?>/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Contenedor principal de la página -->
    <div id="wrapper">

        <!-- Barra lateral -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Marca de la barra lateral -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo ADMIN_URL; ?>/index.php">
                <img src="<?php echo IMG_URL; ?>/logo.png" class="img" alt="Logo del Taller de Motos">
            </a>

            <br>
            <!-- Separador -->
            <hr class="sidebar-divider">

            <!-- Encabezado -->
            <div class="sidebar-heading">
                Control
            </div>

            <!-- Elemento de navegación - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Elemento de navegación - Administradores-->
            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/control/administradores.php">
                    <i class="fas fa-user-shield"></i>
                    <span>Administradores</span></a>
            </li>
            
            <!-- Elemento de navegación - Reporte -->
            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/control/reportes.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Reportes</span></a>
            </li>

            <!-- Separador -->
            <hr class="sidebar-divider">

            <!-- Encabezado -->
            <div class="sidebar-heading">
                Taller
            </div>

            <!-- Elemento de navegación - Taller -->

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/taller/clientes.php">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/taller/motos.php">
                    <i class="fas fa-motorcycle"></i>
                    <span>Motos</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/taller/tipos_trabajos.php">
                    <i class="fas fa-toolbox"></i>
                    <span>Tipos de trabajos</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/taller/mantenimientos.php">
                    <i class="fas fa-tools"></i>
                    <span>Mantenimientos</span></a>
            </li>

            <!-- Separador -->
            <hr class="sidebar-divider">

            <!-- Encabezado -->
            <div class="sidebar-heading">
                Sobre Motos
            </div>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/sobre_motos/cilindraje.php">
                    <i class="fas fa-cogs"></i>
                    <span>Cilindraje</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/sobre_motos/marcas.php">
                    <i class="fas fa-registered"></i>
                    <span>Marcas</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/sobre_motos/referencias_marcas.php">
                    <i class="fas fa-box-open"></i>
                    <span>Referencias</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/sobre_motos/modelos.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Modelos</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo ADMIN_URL; ?>/sobre_motos/colores.php">
                    <i class="fas fa-palette"></i>
                    <span>Colores</span></a>
            </li>

             <!-- Separador -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Encabezado -->
            <div class="sidebar-heading">
                Sesion
            </div>

            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    <span>Cerrar sesion</span></a>
            </li>

            <!-- Separador -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Botón para mostrar/ocultar la barra lateral -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

            

        </ul>
        <!-- Fin de la barra lateral -->

        <!-- Contenedor de contenido -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Contenido principal -->
            <div id="content">

                <!-- Barra superior -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Botón para mostrar/ocultar la barra lateral (Barra superior) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Barra de navegación superior -->
                    <ul class="navbar-nav ml-auto">

                         <!-- elemento de navegacion - informacion del administrador -->

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Elemento de navegación - Información del administrador -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $nombre_administrador; ?></span>
                                    <span class="mr-3 d-none d-lg-inline text-gray-600 small"> - <?php echo $documento_administrador; ?></span>
                                <span></span>
                                <img class="img-profile rounded-circle"
                                    src="<?php echo CSS_URL; ?>/img/undraw_profile.svg">
                            </a>
                            <!-- Menú desplegable - Información del usuario -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Perfil
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- Fin de la barra superior -->

                <!-- Inicio del contenido de la página -->