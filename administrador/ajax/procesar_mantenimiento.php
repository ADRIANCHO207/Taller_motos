<?php
// Iniciar la sesión para acceder a las variables de sesión del administrador.
session_start();
// Definir la cabecera para asegurar que la respuesta siempre sea interpretada como JSON por el navegador.
header('Content-Type: application/json');

// Incluir el archivo de conexión a la base de datos y configuración.
require_once __DIR__ . '/../../conecct/conex.php';

// Instanciar la conexión a la base de datos (PDO).
$db = new Database();
$conexion = $db->conectar();

/**
 * --- Auditoría con Triggers ---
 * Establece una variable de sesión a nivel de la conexión de MySQL (@current_admin_id).
 * Esta variable es "visible" para los Triggers de la base de datos durante toda la ejecución de este script.
 * Permite que los triggers registren qué administrador realizó cada acción (INSERT, UPDATE, DELETE).
 */
$id_admin_actual = $_SESSION['id_documento'] ?? 'sistema';
$stmt_session_var = $conexion->prepare("SET @current_admin_id = ?");
$stmt_session_var->execute([$id_admin_actual]);


// Determina la operación a realizar basándose en el parámetro 'accion' enviado por AJAX.
// Usa el operador de fusión de null (??) para evitar errores si el parámetro no se envía.
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    /**
     * --- Caso para Agregar y Actualizar Mantenimientos ---
     * Se agrupan porque comparten gran parte de la lógica, como la validación y la inserción de detalles.
     * Se utiliza una transacción de base de datos para garantizar la atomicidad de la operación:
     * o se guardan todos los datos (en 'mantenimientos' y 'detalle_mantenimientos'), o no se guarda nada.
     */


    case 'agregar':
    case 'actualizar':
        // Inicia una transacción. Desactiva el autocommit de la base de datos.
        $conexion->beginTransaction();
        try {
            // Validación de datos principales del formulario. Si falta alguno, se lanza una excepción.
            if (empty($_POST['id_placa']) || empty($_POST['fecha_realizo']) || !isset($_POST['kilometraje'])) {
                throw new Exception('Faltan datos generales (placa, fecha, kilometraje).');
            }
            // Los detalles de los trabajos vienen como una cadena JSON desde JavaScript.
            // json_decode la convierte en un array asociativo de PHP.
            $detalles = json_decode($_POST['detalles'] ?? '[]', true);
            if (empty($detalles)) {
                throw new Exception('Debe agregar al menos un trabajo realizado.');
            }

            if ($accion === 'agregar') {
                 // Si es un nuevo registro, se prepara un INSERT en la tabla principal 'mantenimientos'.
                $stmt = $conexion->prepare("INSERT INTO mantenimientos (fecha_realizo, kilometraje, total, observaciones_entrada, observaciones_salida, id_placa) VALUES (:fecha, :km, :total, :obs_in, :obs_out, :placa)");
                $stmt->execute([
                    ':fecha' => $_POST['fecha_realizo'],
                    ':km' => $_POST['kilometraje'],
                    ':total' => $_POST['total'],
                    ':obs_in' => $_POST['observaciones_entrada'],
                    ':obs_out' => $_POST['observaciones_salida'],
                    ':placa' => $_POST['id_placa']
                ]);
                // Se recupera el ID auto-generado del mantenimiento que acabamos de crear.
                $id_mantenimiento = $conexion->lastInsertId();
            } else {// Si la acción es 'actualizar'
                // Se prepara un UPDATE en la tabla principal.
                $id_mantenimiento = intval($_POST['id_mantenimientos']);
                $stmt = $conexion->prepare("UPDATE mantenimientos SET fecha_realizo=:fecha, kilometraje=:km, total=:total, observaciones_entrada=:obs_in, observaciones_salida=:obs_out, id_placa=:placa WHERE id_mantenimientos=:id");
                $stmt->execute([
                    ':fecha' => $_POST['fecha_realizo'],
                    ':km' => $_POST['kilometraje'],
                    ':total' => $_POST['total'],
                    ':obs_in' => $_POST['observaciones_entrada'],
                    ':obs_out' => $_POST['observaciones_salida'],
                    ':placa' => $_POST['id_placa'],
                    ':id' => $id_mantenimiento
                ]);

                // CRUCIAL para editar: Antes de insertar los nuevos detalles,
                // borramos todos los detalles antiguos asociados a este mantenimiento para evitar duplicados.
                $stmt_delete = $conexion->prepare("DELETE FROM detalle_mantenimientos WHERE id_mantenimiento = :id");
                $stmt_delete->execute([':id' => $id_mantenimiento]);
            }

             // Si el ID es 0, significa que la operación anterior falló. Lanzamos una excepción para activar el rollback.
            if ($id_mantenimiento == 0) throw new Exception('No se pudo procesar el registro de mantenimiento.');


            // Bucle para insertar cada uno de los detalles en la tabla 'detalle_mantenimientos'.
            // Esta tabla une el mantenimiento con los tipos de trabajo.
            $stmt_detail = $conexion->prepare("INSERT INTO detalle_mantenimientos (id_tipo_trabajo, cantidad, subtotal, id_mantenimiento) VALUES (:id_tipo, :cant, :subtotal, :id_mant)");
            foreach ($detalles as $detalle) {
                $stmt_detail->execute([
                    ':id_tipo' => $detalle['id_tipo'],
                    ':cant' => $detalle['cantidad'],
                    ':subtotal' => $detalle['subtotal'],
                    ':id_mant' => $id_mantenimiento
                ]);
            }

            // Si todas las consultas (INSERT/UPDATE y los INSERT de detalles) se ejecutaron sin errores,
            // se confirman permanentemente los cambios en la base de datos.
            $conexion->commit();
            $message = $accion === 'agregar' ? 'registrado' : 'actualizado';
            echo json_encode(['status' => 'success', 'message' => "Mantenimiento $message con éxito."]);

        } catch (Exception $e) {
            // Si cualquier consulta dentro del bloque 'try' falla, se revierte toda la transacción.
            // Ningún cambio se guardará en la base de datos. 
            $conexion->rollBack(); 
             // Se devuelve un mensaje de error claro al frontend.
            echo json_encode(['status' => 'error', 'message' => 'Error al procesar: ' . $e->getMessage()]); 
        }
        break;

         /**
     * --- Caso para Obtener un Mantenimiento Completo ---
     * Utilizado al abrir el modal de "Editar". Devuelve los datos principales del mantenimiento
     * y un array con todos sus detalles para poder poblar el formulario.
     */
    case 'obtener_mantenimiento':
        $id = $_GET['id'] ?? 0;
        //  Hacemos JOINs para obtener todos los datos necesarios ---
        $stmt_main = $conexion->prepare("
            SELECT m.*, c.nombre as nombre_cliente, ci.cilindraje
            FROM mantenimientos m
            JOIN motos mo ON m.id_placa = mo.id_placa
            JOIN clientes c ON mo.id_documento_cli = c.id_documento_cli
            JOIN cilindraje ci ON mo.id_cilindraje = ci.id_cc
            WHERE m.id_mantenimientos = :id
        ");
        $stmt_main->execute([':id' => $id]);
        $mantenimiento = $stmt_main->fetch(PDO::FETCH_ASSOC);
        
        $stmt_details = $conexion->prepare("SELECT dm.cantidad, dm.id_tipo_trabajo as id_tipo, tt.detalle, tt.precio_unitario FROM detalle_mantenimientos dm JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo WHERE dm.id_mantenimiento = :id");
        $stmt_details->execute([':id' => $id]);
        $detalles = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['main' => $mantenimiento, 'details' => $detalles]);
        break;
        
         /**
         * --- Caso para Obtener SOLO los Detalles ---
         * Utilizado por el modal "Ver Detalles" para mostrar la lista de trabajos realizados.
         */
    case 'obtener_detalles':
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT dm.cantidad, dm.subtotal, tt.detalle FROM detalle_mantenimientos dm JOIN tipo_trabajo tt ON dm.id_tipo_trabajo = tt.id_tipo WHERE dm.id_mantenimiento = :id");
        $stmt->execute([':id' => $id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($detalles);
        break;

        /**
         * --- Caso para el Buscador Dinámico de Motos ---
         * Busca en la tabla 'motos' por placa o por nombre de cliente.
         * Utiliza JOINs para devolver también el nombre del cliente y el cilindraje de la moto,
         * datos necesarios para el frontend.
         */
   case 'buscar_motos':
        $term = $_GET['term'] ?? '';
        $stmt = $conexion->prepare("
            SELECT mo.id_placa, cli.nombre, ci.cilindraje 
            FROM motos mo
            JOIN clientes cli ON mo.id_documento_cli = cli.id_documento_cli
            JOIN cilindraje ci ON mo.id_cilindraje = ci.id_cc
            WHERE mo.id_placa LIKE :term_placa OR cli.nombre LIKE :term_nombre
            LIMIT 10
        ");
        $like_term = "%" . $term . "%";
        $stmt->execute([':term_placa' => $like_term, ':term_nombre' => $like_term]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

   /**
     * --- Caso para el Buscador Dinámico de Trabajos (con filtro de CC) ---
     * Busca en 'tipo_trabajo' por el nombre del trabajo ('detalle').
     * Si el frontend envía el cilindraje de la moto seleccionada, la consulta
     * se modifica dinámicamente para filtrar solo los trabajos cuyo rango
     * [cc_inicial, cc_final] sea compatible.
     */
    case 'buscar_trabajos':
        $term = $_GET['term'] ?? '';
        $cilindraje_moto = intval($_GET['cilindraje'] ?? 0); // Recibimos el CC de la moto
        
        $sql = "SELECT id_tipo, detalle, cc_inicial, cc_final, precio_unitario 
                FROM tipo_trabajo 
                WHERE detalle LIKE :term";
        $params = [':term' => "%" . $term . "%"];
        
        // Si se proporcionó un cilindraje, añadimos el filtro
        if ($cilindraje_moto > 0) {
            $sql .= " AND :cilindraje_moto BETWEEN cc_inicial AND cc_final";
            $params[':cilindraje_moto'] = $cilindraje_moto;
        }
        $sql .= " LIMIT 10";

        $stmt = $conexion->prepare($sql);
        $stmt->execute($params); 
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;


         /**
         * --- Caso para Eliminar un Mantenimiento y sus Detalles ---
         * Utiliza una transacción para asegurar que se borren los registros de ambas tablas
         * ('mantenimientos' y 'detalle_mantenimientos') de forma atómica.
         */
    case 'eliminar':
        $conexion->beginTransaction();
        try {
            $id = $_POST['id'] ?? 0;
            if ($id <= 0) throw new Exception('ID no válido.');

            $stmt1 = $conexion->prepare("DELETE FROM detalle_mantenimientos WHERE id_mantenimiento = :id");
            $stmt1->execute([':id' => $id]);

            $stmt2 = $conexion->prepare("DELETE FROM mantenimientos WHERE id_mantenimientos = :id");
            $stmt2->execute([':id' => $id]);

            if ($stmt2->rowCount() > 0) {
                $conexion->commit();
                echo json_encode(['status' => 'success', 'message' => 'Mantenimiento y sus detalles eliminados.']);
            } else {
                throw new Exception('No se encontró el mantenimiento para eliminar.');
            }
        } catch (Exception $e) {
            $conexion->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>