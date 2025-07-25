$(document).ready(function() {

    const formAgregar = $('#formAgregarColor');
    const formEditar = $('#formEditarColor');
    const modalAgregar = $('#modalAgregarColor');
    const modalEditar = $('#modalEditarColor');

    // --- Función de validación ---
    const validarColor = (input) => {
        const valor = input.val().trim();
        const regex = /^[A-Za-zÑñÁáÉéÍíÓóÚú\s]+$/; // Solo letras, espacios y acentos
        const feedback = input.next('.invalid-feedback');

        if (valor === '') {
            input.addClass('is-invalid');
            feedback.text('El nombre del color es obligatorio.');
            return false;
        }
        if (!regex.test(valor)) {
            input.addClass('is-invalid');
            feedback.text('Solo se permiten letras y espacios.');
            return false;
        }
        input.removeClass('is-invalid');
        return true;
    };

    // --- AGREGAR ---
    formAgregar.on('submit', function(e) {
        e.preventDefault();
        const input = formAgregar.find('[name="color"]');
        if (validarColor(input)) {
            const formData = $(this).serialize() + '&accion=agregar';
            $.ajax({
                url: '../ajax/procesar_color.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalAgregar.modal('hide');
                    Swal.fire(response.status === 'success' ? '¡Éxito!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });
    
    modalAgregar.on('show.bs.modal', () => {
        formAgregar[0].reset();
        formAgregar.find('.is-invalid').removeClass('is-invalid');
    });

    // --- EDITAR ---
    $('#dataTableColores tbody').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        const valor = $(this).data('valor');
        
        formEditar.find('[name="id_color"]').val(id);
        formEditar.find('[name="color"]').val(valor);
        modalEditar.modal('show');
    });

    formEditar.on('submit', function(e) {
        e.preventDefault();
        const input = formEditar.find('[name="color"]');
        if (validarColor(input)) {
            const formData = $(this).serialize() + '&accion=actualizar';
            $.ajax({
                url: '../ajax/procesar_color.php', type: 'POST', data: formData, dataType: 'json',
                success: response => {
                    modalEditar.modal('hide');

                    Swal.fire(response.status === 'success' ? '¡Actualizado!' : 'Error', response.message, response.status).then(() => location.reload());
                }
            });
        }
    });

    // --- ELIMINAR ---
    let idParaEliminar;
    $('#dataTableColores tbody').on('click', '.btn-eliminar', function() {
        idParaEliminar = $(this).data('id');
        $('#modalEliminarColor').modal('show');
    });

    $('#btnConfirmarEliminar').on('click', function() {
        $.ajax({
            url: '../ajax/procesar_color.php', type: 'POST', data: { accion: 'eliminar', id: idParaEliminar }, dataType: 'json',
            success: response => {
                $('#modalEliminarColor').modal('hide');
                Swal.fire(response.status === 'success' ? '¡Eliminado!' : 'Error', response.message, response.status).then(() => location.reload());
            }
        });
    });
});