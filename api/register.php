<?php
// ------------------------------------------------------------
// API: Registro de cuenta
// ------------------------------------------------------------
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

// Validar metodo HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Metodo no permitido']);
    exit;
}

// Leer y limpiar parametros
$nombre = trim($_POST['nombre_usuario'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

// Validaciones base
if ($nombre === '' || $correo === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Todos los campos son obligatorios']);
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Correo invalido']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'La contrasena debe tener al menos 6 caracteres']);
    exit;
}

// Verificar duplicados
$check = $conn->prepare('SELECT id FROM usuario WHERE nombre_usuario = ? OR correo = ? LIMIT 1');
if (!$check) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al verificar usuario']);
    $conn->close();
    exit;
}
$check->bind_param('ss', $nombre, $correo);
if (!$check->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al validar datos']);
    $check->close();
    $conn->close();
    exit;
}
$existente = $check->get_result()->fetch_assoc();
$check->close();

if ($existente) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => 'El usuario o correo ya existe']);
    $conn->close();
    exit;
}

// Crear cuenta con contrasena hasheada
$hash = password_hash($password, PASSWORD_DEFAULT);
$insert = $conn->prepare('INSERT INTO usuario (nombre_usuario, correo, password_hash) VALUES (?, ?, ?)');
if (!$insert) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al preparar registro']);
    $conn->close();
    exit;
}
$insert->bind_param('sss', $nombre, $correo, $hash);
if (!$insert->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo crear la cuenta']);
    $insert->close();
    $conn->close();
    exit;
}

// Crear sesion para entrar directo
$_SESSION['usuario_id'] = $insert->insert_id;
$_SESSION['nombre_usuario'] = $nombre;

$insert->close();
$conn->close();

echo json_encode(['ok' => true, 'mensaje' => 'Cuenta creada correctamente']);
?>
