<?php
// ------------------------------------------------------------
// API: Obtener publicaciones del usuario y sus amigos
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
    SELECT p.id, p.texto, p.medio_tipo, p.medio_ruta, p.creado_en,
           u.id AS usuario_id, u.nombre_usuario, u.foto_perfil
    FROM publicacion p
    INNER JOIN usuario u ON u.id = p.usuario_id
    WHERE p.usuario_id = ?
       OR p.usuario_id IN (
           SELECT amigo_id FROM amistad WHERE usuario_id = ?
       )
    ORDER BY p.creado_en DESC, p.id DESC
';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al cargar publicaciones']);
    $conn->close();
    exit;
}

$stmt->bind_param('ii', $usuarioActualId, $usuarioActualId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar publicaciones']);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$publicaciones = [];
while ($row = $result->fetch_assoc()) {
    $publicaciones[] = [
        'id' => (int) $row['id'],
        'texto' => $row['texto'],
        'medio_tipo' => $row['medio_tipo'],
        'medio_ruta' => $row['medio_ruta'],
        'creado_en' => $row['creado_en'],
        'usuario_id' => (int) $row['usuario_id'],
        'nombre_usuario' => $row['nombre_usuario'],
        'foto_perfil' => $row['foto_perfil']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'publicaciones' => $publicaciones]);
?>
