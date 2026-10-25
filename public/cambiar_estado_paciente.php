<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pacientes_moderno.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

require_once __DIR__ . '/../config/database.php';

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: pacientes_moderno.php');
    exit;
}

try {
    $pdo = conectarBD();
    
    $stmt = $pdo->prepare("SELECT activo FROM pacientes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $paciente = $stmt->fetch();
    
    if (!$paciente) {
        header('Location: pacientes_moderno.php');
        exit;
    }
    
    $nuevoEstado = $paciente['activo'] ? false : true;
    
    $stmt = $pdo->prepare("UPDATE pacientes SET activo = :activo, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        'activo' => $nuevoEstado ? 'true' : 'false',
        'id' => $id
    ]);
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    header('Location: pacientes_moderno.php?cambiado=1');
    exit;
    
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: pacientes_moderno.php');
    exit;
}
