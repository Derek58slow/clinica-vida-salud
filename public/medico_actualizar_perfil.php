<?php
require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([3]);
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medico_perfil.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

$correo = trim((string)($_POST['correo'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$consultorio = trim((string)($_POST['consultorio'] ?? ''));

if ($correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
    header('Location: medico_perfil.php?error=correo#editar-contacto');
    exit;
}

$telefonoValido = $telefono === '' || (mb_strlen($telefono) >= 6 && mb_strlen($telefono) <= 30 && preg_match('/^[0-9+() .-]+$/u', $telefono));
if (!$telefonoValido || mb_strlen($correo) > 255 || mb_strlen($consultorio) > 100) {
    header('Location: medico_perfil.php?error=datos#editar-contacto');
    exit;
}

try {
    $pdo = conectarBD();
    $medico = obtenerMedicoSesion($pdo);
    if (!$medico) {
        http_response_code(403);
        exit('Tu cuenta no tiene un perfil médico activo.');
    }
    $stmt = $pdo->prepare(
        'UPDATE medicos
         SET correo = :correo, telefono = :telefono, consultorio = :consultorio, updated_at = CURRENT_TIMESTAMP
         WHERE id = :id AND usuario_id = :usuario_id AND activo = TRUE'
    );
    $stmt->execute([
        'correo' => $correo !== '' ? mb_strtolower($correo) : null,
        'telefono' => $telefono !== '' ? $telefono : null,
        'consultorio' => $consultorio !== '' ? $consultorio : null,
        'id' => $medico['id'],
        'usuario_id' => $_SESSION['usuario_id'],
    ]);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: medico_perfil.php?actualizado=1#editar-contacto');
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: medico_perfil.php?error=servidor#editar-contacto');
}
exit;
