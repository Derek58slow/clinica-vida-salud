<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if ($rolId !== 1) {
    http_response_code(403);
    exit('No tienes permiso para guardar especialidades.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: especialidades_moderno.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

require_once __DIR__ . '/../config/database.php';

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if ($nombre === '') {
    header('Location: registrar_especialidad.php?error=datos');
    exit;
}

if ($descripcion === '') {
    $descripcion = null;
}

try {
    $pdo = conectarBD();
    
    $check = $pdo->prepare("SELECT id FROM especialidades WHERE LOWER(nombre) = LOWER(:nombre) LIMIT 1");
    $check->execute(['nombre' => $nombre]);
    if ($check->fetch()) {
        header('Location: registrar_especialidad.php?error=duplicado');
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO especialidades (nombre, descripcion, activo) VALUES (:nombre, :descripcion, TRUE)");
    $stmt->execute([
        'nombre' => $nombre,
        'descripcion' => $descripcion
    ]);
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: especialidades_moderno.php?registrado=1');
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: registrar_especialidad.php?error=servidor');
    exit;
}
