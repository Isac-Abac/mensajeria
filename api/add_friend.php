<?php
// ------------------------------------------------------------
// API: Enviar solicitud de amistad
// ------------------------------------------------------------
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

$usuarioActualId = (int) $_SESSION['usuario_id'];
$nombreUsuario = trim($_POST['nombre_usuario'] ?? '');

if ($nombreUsuario === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Escribe un nombre de usuario']);
    exit;
}

$stmt = $conn->prepare('SELECT id, nombre_usuario FROM usuario WHERE nombre_usuario = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al buscar usuario']);
    $conn->close();
    exit;
}

$stmt->bind_param('s', $nombreUsuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensaje' => 'No existe ese usuario']);
    $conn->close();
    exit;
}

$destinatarioId = (int) $usuario['id'];
if ($destinatarioId === $usuarioActualId) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'No puedes agregarte a ti mismo']);
    $conn->close();
    exit;
}

// Verificar si ya son amigos
$amigosStmt = $conn->prepare('
    SELECT id
    FROM amistad
    WHERE (usuario_id = ? AND amigo_id = ?)
       OR (usuario_id = ? AND amigo_id = ?)
    LIMIT 1
');
if (!$amigosStmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al verificar amistad']);
    $conn->close();
    exit;
}

$amigosStmt->bind_param('iiii', $usuarioActualId, $destinatarioId, $destinatarioId, $usuarioActualId);
$amigosStmt->execute();
$yaSonAmigos = $amigosStmt->get_result()->fetch_assoc();
$amigosStmt->close();

if ($yaSonAmigos) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => 'Ya son amigos']);
    $conn->close();
    exit;
}

// Verificar solicitud pendiente en cualquier direccion
$solicitudStmt = $conn->prepare('
    SELECT id, estado
    FROM solicitud_amistad
    WHERE (solicitante_id = ? AND destinatario_id = ?)
       OR (solicitante_id = ? AND destinatario_id = ?)
    ORDER BY id DESC
    LIMIT 1
');
if (!$solicitudStmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al verificar solicitud']);
    $conn->close();
    exit;
}

$solicitudStmt->bind_param('iiii', $usuarioActualId, $destinatarioId, $destinatarioId, $usuarioActualId);
$solicitudStmt->execute();
$solicitud = $solicitudStmt->get_result()->fetch_assoc();
$solicitudStmt->close();

if ($solicitud) {
    if ($solicitud['estado'] === 'pendiente') {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => 'Ya existe una solicitud pendiente']);
        $conn->close();
        exit;
    }
}

$insert = $conn->prepare('INSERT INTO solicitud_amistad (solicitante_id, destinatario_id, estado) VALUES (?, ?, \'pendiente\')');
if (!$insert) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al crear solicitud']);
    $conn->close();
    exit;
}

$insert->bind_param('ii', $usuarioActualId, $destinatarioId);
if (!$insert->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo enviar la solicitud']);
    $insert->close();
    $conn->close();
    exit;
}

$insert->close();
$conn->close();

echo json_encode(['ok' => true, 'mensaje' => 'Solicitud de amistad enviada']);
?>
