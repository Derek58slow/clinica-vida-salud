<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if ($rolId !== 1) {
    http_response_code(403);
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: horarios_moderno.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

require_once __DIR__ . '/../config/database.php';

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: horarios_moderno.php');
    exit;
}

try {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT activo FROM horarios_atencion WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $h = $stmt->fetch();
    
    if (!$h) {
        header('Location: horarios_moderno.php');
        exit;
    }
    
    $nuevo = $h['activo'] ? false : true;
    $upd = $pdo->prepare("UPDATE horarios_atencion SET activo = :activo WHERE id = :id");
    $upd->execute(['activo' => $nuevo ? 'true' : 'false', 'id' => $id]);
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: horarios_moderno.php?cambiado=1');
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    $codigo=$e->getCode()==='PVS01'?'citas':($e->getCode()==='23P01'?'superpuesto':'servidor');
    header('Location: horarios_moderno.php?error='.$codigo);
    exit;
}
