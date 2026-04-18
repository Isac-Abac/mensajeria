<?php
// ------------------------------------------------------------
// API: Editar mensaje propio (solo si no esta visto)
// ------------------------------------------------------------
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Metodo no permitido']);
    exit;
}

require_once __DIR__ . '/../conexion.php';
$usuarioId = (int) $_SESSION['usuario_id'];
$mensajeId = (int) ($_POST['mensaje_id'] ?? 0);
$contenido = trim($_POST['contenido'] ?? '');

// Confirmar que exista columna de control "visto"
$checkVisto = $conn->query("SHOW COLUMNS FROM mensaje LIKE 'visto'");
if (!$checkVisto || $checkVisto->num_rows === 0) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Falta la columna visto. Ejecuta: ALTER TABLE mensaje ADD COLUMN visto TINYINT(1) NOT NULL DEFAULT 0 AFTER contenido;'
    ]);
    $conn->close();
    exit;
}

if ($mensajeId <= 0 || $contenido === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos para editar']);
    $conn->close();
    exit;
}

// Leer estado del mensaje antes de editar
$check = $conn->prepare('SELECT remitente_id, contenido, visto FROM mensaje WHERE id = ? LIMIT 1');
if (!$check) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al validar mensaje']);
    $conn->close();
    exit;
}
$check->bind_param('i', $mensajeId);
if (!$check->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo validar el mensaje']);
    $check->close();
    $conn->close();
    exit;
}
$actual = $check->get_result()->fetch_assoc();
$check->close();

if (!$actual) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensaje' => 'Mensaje no encontrado']);
    $conn->close();
    exit;
}
if ((int) $actual['remitente_id'] !== $usuarioId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Solo puedes editar tus propios mensajes']);
    $conn->close();
    exit;
}
if ((int) $actual['visto'] === 1) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => 'No puedes editar un mensaje que ya fue visto']);
    $conn->close();
    exit;
}
if ($actual['contenido'] === $contenido) {
    echo json_encode(['ok' => true, 'mensaje' => 'Mensaje sin cambios']);
    $conn->close();
    exit;
}

// Ejecutar actualizacion
$stmt = $conn->prepare('UPDATE mensaje SET contenido = ? WHERE id = ? AND remitente_id = ? AND visto = 0');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al editar mensaje']);
    $conn->close();
    exit;
}
$stmt->bind_param('sii', $contenido, $mensajeId, $usuarioId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo editar el mensaje']);
    $stmt->close();
    $conn->close();
    exit;
}
if ($stmt->affected_rows === 0) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo editar: el mensaje ya fue visto']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'mensaje' => 'Mensaje editado correctamente']);
?>
