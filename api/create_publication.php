<?php
// ------------------------------------------------------------
// API: Crear publicacion tipo Instagram
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
$texto = trim($_POST['texto'] ?? '');

$medioRuta = null;
$medioTipo = null;

if (isset($_FILES['medio']) && ($_FILES['medio']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $archivo = $_FILES['medio'];
    $maxSize = 120 * 1024 * 1024;
    if ((int) $archivo['size'] > $maxSize) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'La publicacion supera el tamano permitido']);
        exit;
    }

    $mime = strtolower((string) mime_content_type($archivo['tmp_name']));
    $mime = explode(';', $mime)[0];
    $permitidos = [
        'image/jpeg' => ['tipo' => 'imagen', 'ext' => 'jpg'],
        'image/png' => ['tipo' => 'imagen', 'ext' => 'png'],
        'image/webp' => ['tipo' => 'imagen', 'ext' => 'webp'],
        'image/gif' => ['tipo' => 'imagen', 'ext' => 'gif'],
        'video/mp4' => ['tipo' => 'video', 'ext' => 'mp4'],
        'video/webm' => ['tipo' => 'video', 'ext' => 'webm'],
        'video/quicktime' => ['tipo' => 'video', 'ext' => 'mov']
    ];

    if (!isset($permitidos[$mime])) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'Formato de publicacion no permitido']);
        exit;
    }

    $tipo = $permitidos[$mime]['tipo'];
    $ext = $permitidos[$mime]['ext'];
    $baseName = pathinfo($archivo['name'], PATHINFO_FILENAME);
    $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    $baseName = $baseName === '' ? 'publicacion' : $baseName;

    $dirFisico = __DIR__ . '/../uploads/publications';
    $dirPublico = 'uploads/publications';
    if (!is_dir($dirFisico) && !mkdir($dirFisico, 0755, true)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'No se pudo crear la carpeta de publicaciones']);
        exit;
    }

    $nombreFinal = 'pub_u' . $usuarioActualId . '_' . time() . '_' . $baseName . '.' . $ext;
    $rutaFisica = $dirFisico . '/' . $nombreFinal;
    $rutaPublica = $dirPublico . '/' . $nombreFinal;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'No se pudo guardar la publicacion']);
        exit;
    }

    $medioRuta = $rutaPublica;
    $medioTipo = $tipo;
}

if ($texto === '' && $medioRuta === null) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Escribe texto o adjunta una imagen/video']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO publicacion (usuario_id, texto, medio_tipo, medio_ruta) VALUES (?, ?, ?, ?)');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al crear publicacion']);
    $conn->close();
    exit;
}

$stmt->bind_param('isss', $usuarioActualId, $texto, $medioTipo, $medioRuta);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo publicar']);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'mensaje' => 'Publicacion creada']);
?>
