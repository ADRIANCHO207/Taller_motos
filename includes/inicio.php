<?php
// --- inicio.php (Versión a prueba de errores) ---

session_start();
require_once('../conecct/conex.php'); 

header('Content-Type: application/json');

// Respuesta por defecto
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

try {
    $db = new Database();
    $con = $db->conectar();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $doc = $_POST['documento'] ?? '';
        $passw = $_POST['password'] ?? '';

        if (empty($doc) || empty($passw)) {
            $response['message'] = 'Error: Todos los campos son obligatorios.';
        } else {
            $query = $con->prepare("SELECT * FROM administradores WHERE id_documento = ?");
            $query->execute([$doc]);
            $user = $query->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($passw, $user['password'])) {
                $_SESSION['id_documento'] = $user['id_documento']; 
                $_SESSION['nombre'] = $user['nombre'];
                
                $response['status'] = 'success';
                $response['message'] = 'Login exitoso. Redirigiendo...';
            } else {
                $response['message'] = 'Error: Documento o contraseña incorrectos.';
            }
        }
    } else {
        $response['message'] = 'Petición no válida.';
    }

} catch (PDOException $e) {
    // Si ocurre cualquier error con la base de datos, lo atrapamos aquí
    $response['message'] = 'Error de Base de Datos: ' . $e->getMessage();
    // Para depuración, podrías querer registrar el error completo:
    // error_log('PDO Error: ' . $e->getMessage());
} catch (Exception $e) {
    // Atrapa cualquier otro tipo de error
    $response['message'] = 'Error General: ' . $e->getMessage();
}

// Sea cual sea el resultado, siempre devolvemos un JSON válido
echo json_encode($response);
exit;
?>