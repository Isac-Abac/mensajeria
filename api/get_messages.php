<?php
// ------------------------------------------------------------
// API: Obtener conversacion y marcar mensajes como vistos
// ------------------------------------------------------------
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion.php';
$usuarioActualId = (int) $_SESSION['usuario_id'];
$destinatarioId = (int) ($_GET['destinatario_id'] ?? 0);

// Validar columnas de estado "visto"
$checkVisto = $conn->query("SHOW COLUMNS FROM mensaje LIKE 'visto'");
$checkVistoEn = $conn->query("SHOW COLUMNS FROM mensaje LIKE 'visto_en'");
if (!$checkVisto || !$checkVistoEn || $checkVisto->num_rows === 0 || $checkVistoEn->num_rows === 0) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Faltan columnas de visto. Ejecuta: ALTER TABLE mensaje ADD COLUMN visto TINYINT(1) NOT NULL DEFAULT 0 AFTER contenido; ALTER TABLE mensaje ADD COLUMN visto_en TIMESTAMP NULL DEFAULT NULL AFTER visto;'
    ]);
    $conn->close();
    exit;
}

if ($destinatarioId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Destinatario invalido']);
    exit;
}

// Verificar usuario destino
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
    echo json_encode(['ok' => false, 'mensaje' => 'El destinatario no existe']);
    $conn->close();
    exit;
}

// Marcar como vistos los mensajes recibidos por el usuario actual
$updateSeen = $conn->prepare('
    UPDATE mensaje
    SET visto = 1, visto_en = NOW()
    WHERE remitente_id = ? AND destinatario_id = ? AND visto = 0
');
if ($updateSeen) {
    $updateSeen->bind_param('ii', $destinatarioId, $usuarioActualId);
    $updateSeen->execute();
    $updateSeen->close();
}

// Cargar la conversacion completa ordenada por fecha
$query = '
    SELECT m.id, m.contenido, m.enviado_en, m.visto, m.visto_en,
           m.remitente_id, ur.nombre_usuario AS remitente,
           m.destinatario_id, ud.nombre_usuario AS destinatario
    FROM mensaje m
    INNER JOIN usuario ur ON ur.id = m.remitente_id
    INNER JOIN usuario ud ON ud.id = m.destinatario_id
    WHERE (m.remitente_id = ? AND m.destinatario_id = ?)
       OR (m.remitente_id = ? AND m.destinatario_id = ?)
    ORDER BY m.enviado_en ASC, m.id ASC
';

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al cargar mensajes']);
    $conn->close();
    exit;
}
$stmt->bind_param('iiii', $usuarioActualId, $destinatarioId, $destinatarioId, $usuarioActualId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo leer la conversacion']);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$mensajes = [];
while ($row = $result->fetch_assoc()) {
    $mensajes[] = [
        'id' => (int) $row['id'],
        'contenido' => $row['contenido'],
        'enviado_en' => $row['enviado_en'],
        'visto' => (int) $row['visto'],
        'visto_en' => $row['visto_en'],
        'remitente_id' => (int) $row['remitente_id'],
        'destinatario_id' => (int) $row['destinatario_id'],
        'remitente' => $row['remitente'],
        'destinatario' => $row['destinatario']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'mensajes' => $mensajes]);
?>
