<?php
require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([4]);
require_once __DIR__ . '/../config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: paciente_perfil.php'); exit; }
$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) { http_response_code(403); exit('Solicitud no válida.'); }
$correo = trim((string)($_POST['correo'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$direccion = trim((string)($_POST['direccion'] ?? ''));
if ($correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) { header('Location: paciente_perfil.php?error=correo#editar-contacto'); exit; }
$telefonoValido = $telefono === '' || (mb_strlen($telefono) >= 6 && mb_strlen($telefono) <= 30 && preg_match('/^[0-9+() .-]+$/u', $telefono));
if (!$telefonoValido || mb_strlen($correo) > 255 || mb_strlen($direccion) > 500) { header('Location: paciente_perfil.php?error=datos#editar-contacto'); exit; }
try {
    $pdo = conectarBD();
    $paciente = exigirPacienteActivo($pdo);
    $stmt = $pdo->prepare('UPDATE pacientes SET correo=:correo,telefono=:telefono,direccion=:direccion,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND usuario_id=:usuario_id AND activo=TRUE');
    $stmt->execute(['correo'=>$correo !== '' ? mb_strtolower($correo) : null,'telefono'=>$telefono !== '' ? $telefono : null,'direccion'=>$direccion !== '' ? $direccion : null,'id'=>$paciente['id'],'usuario_id'=>$_SESSION['usuario_id']]);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: paciente_perfil.php?actualizado=1#editar-contacto');
} catch (Throwable $e) { error_log($e->getMessage()); header('Location: paciente_perfil.php?error=servidor#editar-contacto'); }
exit;
