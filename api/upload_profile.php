<?php
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

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Debes seleccionar una imagen valida']);
    exit;
}

require_once __DIR__ . '/../conexion.php';

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

$usuarioId = (int) $_SESSION['usuario_id'];
$archivo = $_FILES['foto'];

if ($archivo['size'] > 2 * 1024 * 1024) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'La imagen no debe superar 2MB']);
    $conn->close();
    exit;
}

$mimePermitidos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif'
];

$mime = mime_content_type($archivo['tmp_name']);
if (!isset($mimePermitidos[$mime])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Formato no permitido. Usa JPG, PNG, WEBP o GIF']);
    $conn->close();
    exit;
}

$extension = $mimePermitidos[$mime];
$nombre = 'user_' . $usuarioId . '_' . time() . '.' . $extension;
$directorioFisico = __DIR__ . '/../uploads/profile';
$directorioPublico = 'uploads/profile';

if (!is_dir($directorioFisico) && !mkdir($directorioFisico, 0755, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo crear carpeta de imagenes']);
    $conn->close();
    exit;
}

$rutaFisica = $directorioFisico . '/' . $nombre;
$rutaPublica = $directorioPublico . '/' . $nombre;

if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo guardar la imagen']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare('UPDATE usuario SET foto_perfil = ? WHERE id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al guardar foto']);
    $conn->close();
    exit;
}

$stmt->bind_param('si', $rutaPublica, $usuarioId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo actualizar la foto de perfil']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();
$conn->close();

echo json_encode([
    'ok' => true,
    'mensaje' => 'Foto actualizada',
    'foto_perfil' => $rutaPublica
]);
?>
