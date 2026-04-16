<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Metodo no permitido']);
    exit;
}

$remitenteId = (int) $_SESSION['usuario_id'];
$destinatarioId = (int) ($_POST['destinatario_id'] ?? 0);
$contenido = trim($_POST['contenido'] ?? '');

if ($destinatarioId <= 0 || $contenido === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Destinatario y mensaje son obligatorios']);
    exit;
}

if ($destinatarioId === $remitenteId) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'No puedes enviarte mensajes a ti mismo']);
    exit;
}

$checkUser = $conn->prepare('SELECT id FROM usuario WHERE id = ? LIMIT 1');
if (!$checkUser) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al validar destinatario']);
    $conn->close();
    exit;
}

$checkUser->bind_param('i', $destinatarioId);
if (!$checkUser->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al consultar destinatario']);
    $checkUser->close();
    $conn->close();
    exit;
}

$existe = $checkUser->get_result()->fetch_assoc();
$checkUser->close();

if (!$existe) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensaje' => 'Solo puedes enviar mensajes a cuentas existentes']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare('INSERT INTO mensaje (remitente_id, destinatario_id, contenido) VALUES (?, ?, ?)');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al preparar mensaje']);
    $conn->close();
    exit;
}

$stmt->bind_param('iis', $remitenteId, $destinatarioId, $contenido);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo enviar el mensaje']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'mensaje' => 'Mensaje enviado']);
?>
