<?php
// ------------------------------------------------------------
// API: Obtener perfil del usuario autenticado
// ------------------------------------------------------------
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion.php';
$usuarioId = (int) $_SESSION['usuario_id'];

// Verificar columna de foto de perfil
$checkColumn = $conn->query("SHOW COLUMNS FROM usuario LIKE 'foto_perfil'");
if (!$checkColumn || $checkColumn->num_rows === 0) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Falta la columna foto_perfil. Ejecuta: ALTER TABLE usuario ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER password_hash;'
    ]);
    $conn->close();
    exit;
}

$stmt = $conn->prepare('SELECT nombre_usuario, foto_perfil FROM usuario WHERE id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al cargar perfil']);
    $conn->close();
    exit;
}
$stmt->bind_param('i', $usuarioId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo consultar perfil']);
    $stmt->close();
    $conn->close();
    exit;
}

$perfil = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$perfil) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensaje' => 'Perfil no encontrado']);
    exit;
}

echo json_encode([
    'ok' => true,
    'perfil' => [
        'nombre_usuario' => $perfil['nombre_usuario'],
        'foto_perfil' => $perfil['foto_perfil']
    ]
]);
?>
