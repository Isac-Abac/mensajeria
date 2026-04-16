<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Metodo no permitido']);
    exit;
}

$nombre = trim($_POST['nombre_usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($nombre === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Usuario y contrasena son obligatorios']);
    exit;
}

$stmt = $conn->prepare('SELECT id, nombre_usuario, password_hash FROM usuario WHERE nombre_usuario = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al iniciar sesion']);
    $conn->close();
    exit;
}

$stmt->bind_param('s', $nombre);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al validar credenciales']);
    $stmt->close();
    $conn->close();
    exit;
}

$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'Credenciales incorrectas']);
    $conn->close();
    exit;
}

$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];

$conn->close();
echo json_encode(['ok' => true, 'mensaje' => 'Inicio de sesion correcto']);
?>
