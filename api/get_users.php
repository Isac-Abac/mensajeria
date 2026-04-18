<?php
// ------------------------------------------------------------
// API: Listar usuarios disponibles para chatear
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

// Detectar si existe la columna de foto (compatibilidad)
$tieneFotoPerfil = false;
$checkColumn = $conn->query("SHOW COLUMNS FROM usuario LIKE 'foto_perfil'");
if ($checkColumn && $checkColumn->num_rows > 0) {
    $tieneFotoPerfil = true;
}

$sql = $tieneFotoPerfil
    ? 'SELECT id, nombre_usuario, foto_perfil FROM usuario WHERE id <> ? ORDER BY nombre_usuario ASC'
    : 'SELECT id, nombre_usuario, NULL AS foto_perfil FROM usuario WHERE id <> ? ORDER BY nombre_usuario ASC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al listar usuarios']);
    $conn->close();
    exit;
}
$stmt->bind_param('i', $usuarioActualId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudieron cargar usuarios']);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = [
        'id' => (int) $row['id'],
        'nombre_usuario' => $row['nombre_usuario'],
        'foto_perfil' => $row['foto_perfil']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'usuarios' => $usuarios]);
?>
