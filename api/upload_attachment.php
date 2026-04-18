<?php
// ------------------------------------------------------------
// API: Subida de adjuntos de chat
// Tipos: imagenes, videos, documentos, audios
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

$tipo = strtolower(trim($_POST['tipo'] ?? ''));
if (!in_array($tipo, ['imagenes', 'videos', 'documentos', 'audios'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Tipo de archivo invalido']);
    exit;
}

if (!isset($_FILES['archivo'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Debes seleccionar un archivo valido']);
    exit;
}

$archivo = $_FILES['archivo'];
$errorArchivo = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
if ($errorArchivo !== UPLOAD_ERR_OK) {
    http_response_code(422);
    if ($errorArchivo === UPLOAD_ERR_INI_SIZE || $errorArchivo === UPLOAD_ERR_FORM_SIZE) {
        echo json_encode(['ok' => false, 'mensaje' => 'El archivo excede el limite de PHP (upload_max_filesize/post_max_size)']);
    } else {
        echo json_encode(['ok' => false, 'mensaje' => 'Debes seleccionar un archivo valido']);
    }
    exit;
}
$usuarioId = (int) $_SESSION['usuario_id'];

// Limites por categoria
$maxSize = [
    'imagenes' => 8 * 1024 * 1024,
    'videos' => 120 * 1024 * 1024,
    'documentos' => 12 * 1024 * 1024,
    'audios' => 40 * 1024 * 1024
];
if ($archivo['size'] > $maxSize[$tipo]) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'El archivo supera el tamano permitido para ' . $tipo]);
    exit;
}

// Mime types permitidos por categoria
$mime = strtolower((string) mime_content_type($archivo['tmp_name']));
$mime = explode(';', $mime)[0];
$extensionOriginal = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
$permitidos = [
    'imagenes' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'],
    'videos' => [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi',
        'video/x-matroska' => 'mkv'
    ],
    'documentos' => [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'text/plain' => 'txt',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar'
    ],
    'audios' => [
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/wave' => 'wav',
        'audio/vnd.wave' => 'wav',
        'audio/ogg' => 'ogg',
        'audio/webm' => 'webm',
        'audio/mp4' => 'm4a',
        'video/webm' => 'webm'
    ]
];

$extPermitidas = [
    'imagenes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    'videos' => ['mp4', 'webm', 'mov', 'avi', 'mkv'],
    'documentos' => ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'],
    'audios' => ['mp3', 'wav', 'ogg', 'webm', 'm4a']
];

if (!isset($permitidos[$tipo][$mime])) {
    // Fallback por extension cuando el servidor detecta MIME generico.
    if (!in_array($extensionOriginal, $extPermitidas[$tipo], true)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'mensaje' => 'Formato no permitido para ' . $tipo]);
        exit;
    }
    $extension = $extensionOriginal === 'jpeg' ? 'jpg' : $extensionOriginal;
} else {
    $extension = $permitidos[$tipo][$mime];
}

// Construir nombre seguro y rutas
$baseName = pathinfo($archivo['name'], PATHINFO_FILENAME);
$baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
$baseName = $baseName === '' ? 'archivo' : $baseName;

$nombreFinal = $tipo . '_u' . $usuarioId . '_' . time() . '_' . $baseName . '.' . $extension;
$dirFisico = __DIR__ . '/../uploads/messages/' . $tipo;
$dirPublico = 'uploads/messages/' . $tipo;

if (!is_dir($dirFisico) && !mkdir($dirFisico, 0755, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo crear carpeta de adjuntos']);
    exit;
}

$rutaFisica = $dirFisico . '/' . $nombreFinal;
$rutaPublica = $dirPublico . '/' . $nombreFinal;

if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo guardar el archivo']);
    exit;
}

echo json_encode([
    'ok' => true,
    'mensaje' => 'Archivo subido correctamente',
    'tipo' => $tipo,
    'nombre' => $archivo['name'],
    'ruta' => $rutaPublica
]);
?>
