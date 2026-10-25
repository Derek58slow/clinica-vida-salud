<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2, 3], true)) {
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

$medicoId = filter_var($_POST['medico_id'] ?? null, FILTER_VALIDATE_INT);
$dia = filter_var($_POST['dia_semana'] ?? null, FILTER_VALIDATE_INT);
$inicio = trim($_POST['hora_inicio'] ?? '');
$fin = trim($_POST['hora_fin'] ?? '');
$intervalo = filter_var($_POST['intervalo_minutos'] ?? null, FILTER_VALIDATE_INT);

if (!$medicoId || $dia < 1 || $dia > 7 || $inicio === '' || $fin === '' || !$intervalo || $intervalo < 5) {
    header('Location: registrar_horario.php?error=datos');
    exit;
}

if ($fin <= $inicio) {
    header('Location: registrar_horario.php?error=rango');
    exit;
}

try {
    $pdo = conectarBD();
    
    // Verificar superposición con horarios existentes del mismo médico y día
    $check = $pdo->prepare("SELECT id, hora_inicio, hora_fin FROM horarios_atencion 
                            WHERE medico_id = :m AND dia_semana = :d AND activo = TRUE 
                              AND (:ini < hora_fin AND :fin > hora_inicio)");
    $check->execute([
        'm' => $medicoId,
        'd' => $dia,
        'ini' => $inicio,
        'fin' => $fin
    ]);
    
    if ($check->fetch()) {
        header('Location: registrar_horario.php?error=superpuesto');
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO horarios_atencion (medico_id, dia_semana, hora_inicio, hora_fin, intervalo_minutos, activo) VALUES (:m, :d, :i, :f, :intv, TRUE)");
    $stmt->execute([
        'm' => $medicoId,
        'd' => $dia,
        'i' => $inicio,
        'f' => $fin,
        'intv' => $intervalo
    ]);
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: horarios_moderno.php?registrado=1');
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: registrar_horario.php?error=servidor');
    exit;
}
