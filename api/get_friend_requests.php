<?php
// ------------------------------------------------------------
// API: Listar solicitudes de amistad pendientes
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

$sql = '
    SELECT s.id, s.creado_en, u.id AS solicitante_id, u.nombre_usuario, u.foto_perfil
    FROM solicitud_amistad s
    INNER JOIN usuario u ON u.id = s.solicitante_id
    WHERE s.destinatario_id = ? AND s.estado = \'pendiente\'
    ORDER BY s.creado_en DESC, s.id DESC
';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al listar solicitudes']);
    $conn->close();
    exit;
}

$stmt->bind_param('i', $usuarioActualId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar solicitudes']);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$solicitudes = [];
while ($row = $result->fetch_assoc()) {
    $solicitudes[] = [
        'id' => (int) $row['id'],
        'creado_en' => $row['creado_en'],
        'solicitante_id' => (int) $row['solicitante_id'],
        'nombre_usuario' => $row['nombre_usuario'],
        'foto_perfil' => $row['foto_perfil']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'solicitudes' => $solicitudes]);
?>
