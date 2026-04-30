<?php
// ------------------------------------------------------------
// API: Aceptar o rechazar solicitud de amistad
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
$solicitudId = (int) ($_POST['solicitud_id'] ?? 0);
$accion = trim($_POST['accion'] ?? '');

if ($solicitudId <= 0 || !in_array($accion, ['aceptar', 'rechazar'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos invalidos']);
    exit;
}

$stmt = $conn->prepare('SELECT id, solicitante_id, destinatario_id, estado FROM solicitud_amistad WHERE id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al buscar solicitud']);
    $conn->close();
    exit;
}

$stmt->bind_param('i', $solicitudId);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$solicitud || (int) $solicitud['destinatario_id'] !== $usuarioActualId || $solicitud['estado'] !== 'pendiente') {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensaje' => 'Solicitud no encontrada']);
    $conn->close();
    exit;
}

$solicitanteId = (int) $solicitud['solicitante_id'];

if ($accion === 'rechazar') {
    $update = $conn->prepare("UPDATE solicitud_amistad SET estado = 'rechazada' WHERE id = ?");
    if (!$update) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'Error SQL al rechazar solicitud']);
        $conn->close();
        exit;
    }
    $update->bind_param('i', $solicitudId);
    $update->execute();
    $update->close();
    $conn->close();
    echo json_encode(['ok' => true, 'mensaje' => 'Solicitud rechazada']);
    exit;
}

$conn->begin_transaction();

try {
    $update = $conn->prepare("UPDATE solicitud_amistad SET estado = 'aceptada' WHERE id = ?");
    if (!$update) {
        throw new Exception('Error SQL al aceptar solicitud');
    }
    $update->bind_param('i', $solicitudId);
    if (!$update->execute()) {
        throw new Exception('No se pudo aceptar la solicitud');
    }
    $update->close();

    $insert1 = $conn->prepare('INSERT INTO amistad (usuario_id, amigo_id) VALUES (?, ?)');
    $insert2 = $conn->prepare('INSERT INTO amistad (usuario_id, amigo_id) VALUES (?, ?)');
    if (!$insert1 || !$insert2) {
        throw new Exception('Error SQL al crear amistad');
    }

    $insert1->bind_param('ii', $usuarioActualId, $solicitanteId);
    $insert2->bind_param('ii', $solicitanteId, $usuarioActualId);
    if (!$insert1->execute() || !$insert2->execute()) {
        throw new Exception('No se pudo guardar la amistad');
    }

    $insert1->close();
    $insert2->close();

    $conn->commit();
    $conn->close();
    echo json_encode(['ok' => true, 'mensaje' => 'Solicitud aceptada']);
} catch (Throwable $e) {
    $conn->rollback();
    $conn->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
?>
