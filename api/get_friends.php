<?php
// ------------------------------------------------------------
// API: Listar amigos del usuario autenticado
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
    SELECT u.id, u.nombre_usuario, u.foto_perfil
    FROM amistad a
    INNER JOIN usuario u ON u.id = a.amigo_id
    WHERE a.usuario_id = ?
    ORDER BY u.nombre_usuario ASC
';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al listar amigos']);
    $conn->close();
    exit;
}

$stmt->bind_param('i', $usuarioActualId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar amigos']);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$amigos = [];
while ($row = $result->fetch_assoc()) {
    $amigos[] = [
        'id' => (int) $row['id'],
        'nombre_usuario' => $row['nombre_usuario'],
        'foto_perfil' => $row['foto_perfil']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'amigos' => $amigos]);
?>
