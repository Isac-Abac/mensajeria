<?php
// ------------------------------------------------------------
// API: Registro de cuenta
// ------------------------------------------------------------
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // Cargar conexion con comprobacion de existencia
    $conexionPath = __DIR__ . '/../conexion.php';
    if (!file_exists($conexionPath)) {
        throw new Exception('Archivo de conexion no encontrado');
    }
    if (!@include_once $conexionPath) {
        throw new Exception('No se pudo cargar conexion.php');
    }

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

    // Verificar que la tabla exista antes de seguir
    $tablaUsuario = $conn->query("SHOW TABLES LIKE 'usuario'");
    if (!$tablaUsuario || $tablaUsuario->num_rows === 0) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'La tabla usuario no existe. Ejecuta database.sql']);
        exit;
    }

    // Verificar duplicados
    $check = $conn->prepare('SELECT id FROM usuario WHERE nombre_usuario = ? OR correo = ? LIMIT 1');
    if (!$check) {
        throw new Exception('Error SQL al verificar usuario: ' . $conn->error);
    }
    $check->bind_param('ss', $nombre, $correo);
    if (!$check->execute()) {
        throw new Exception('Error SQL al validar datos: ' . $check->error);
    }
    $check->store_result();
    $check->bind_result($existingId);
    $hasExistingUser = $check->fetch();
    $check->close();

    if ($hasExistingUser) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'El usuario o correo ya existe']);
        $conn->close();
        exit;
    }

    // Crear cuenta con contrasena hasheada
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $conn->prepare('INSERT INTO usuario (nombre_usuario, correo, password_hash) VALUES (?, ?, ?)');
    if (!$insert) {
        throw new Exception('Error SQL al preparar registro: ' . $conn->error);
    }
    $insert->bind_param('sss', $nombre, $correo, $hash);
    if (!$insert->execute()) {
        throw new Exception('No se pudo crear la cuenta: ' . $insert->error);
    }

    // Crear sesion para entrar directo
    $_SESSION['usuario_id'] = $insert->insert_id;
    $_SESSION['nombre_usuario'] = $nombre;

    $insert->close();
    $conn->close();

    echo json_encode(['ok' => true, 'mensaje' => 'Cuenta creada correctamente']);
} catch (Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
    if (isset($check) && $check instanceof mysqli_stmt) {
        @$check->close();
    }
    if (isset($insert) && $insert instanceof mysqli_stmt) {
        @$insert->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->close();
    }
}
?>"
