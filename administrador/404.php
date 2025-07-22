<?php 
// --- 404.php ---

// Incluimos el encabezado y la barra lateral/superior
include 'header.php'; 
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- 404 Error Text -->
    <div class="text-center">
        <div class="error mx-auto" data-text="404">404</div>
        <p class="lead text-gray-800 mb-5">Página no Encontrada</p>
        <p class="text-gray-500 mb-0">Parece que encontraste una falla en la matrix, estas buscando una pagina que no existe...</p>
        <!-- Usando ruta absoluta con la constante BASE_URL -->
        <a href="<?php echo ADMIN_URL; ?>/index.php">← Volver al Dashboard</a>
    </div>

</div>
<!-- /.container-fluid -->

<?php 
// Incluimos el pie de página y los scripts usando ruta absoluta
include 'scripts.php'; 
?>