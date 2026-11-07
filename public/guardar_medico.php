<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if ($rolId !== 1) {
    http_response_code(403);
    exit('No tienes permiso para guardar médicos.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medicos_moderno.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

require_once __DIR__ . '/../config/database.php';

$nombres = trim($_POST['nombres'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$licencia = trim($_POST['numero_licencia'] ?? '');
$telefono = trim($_POST['telefono'] ?? '') ?: null;
$correo = trim($_POST['correo'] ?? '') ?: null;
$consultorio = trim($_POST['consultorio'] ?? '') ?: null;
$especialidades = $_POST['especialidades'] ?? [];

if ($nombres === '' || $apellidos === '' || $licencia === '') {
    header('Location: registrar_medico.php?error=datos');
    exit;
}

if ($correo !== null && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: registrar_medico.php?error=datos');
    exit;
}

try {
    $pdo = conectarBD();
    $pdo->beginTransaction();
    
    $check = $pdo->prepare("SELECT id FROM medicos WHERE LOWER(numero_licencia) = LOWER(:lic) LIMIT 1");
    $check->execute(['lic' => $licencia]);
    if ($check->fetch()) {
        $pdo->rollBack();
        header('Location: registrar_medico.php?error=licencia');
        exit;
    }
    
    if ($correo !== null) {
        $check2 = $pdo->prepare("SELECT id FROM medicos WHERE LOWER(correo) = LOWER(:correo) LIMIT 1");
        $check2->execute(['correo' => $correo]);
        if ($check2->fetch()) {
            $pdo->rollBack();
            header('Location: registrar_medico.php?error=correo');
            exit;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO medicos (nombres, apellidos, numero_licencia, telefono, correo, consultorio, activo) VALUES (:n, :a, :lic, :tel, :cor, :cons, TRUE) RETURNING id");
    $stmt->execute([
        'n' => $nombres,
        'a' => $apellidos,
        'lic' => $licencia,
        'tel' => $telefono,
        'cor' => $correo,
        'cons' => $consultorio
    ]);
    $medicoId = $stmt->fetch()['id'];
    
    if (!empty($especialidades) && is_array($especialidades)) {
        $ins = $pdo->prepare("INSERT INTO medicos_especialidades (medico_id, especialidad_id) VALUES (:m, :e)");
        foreach ($especialidades as $eid) {
            $eidInt = (int)$eid;
            if ($eidInt > 0) {
                $ins->execute(['m' => $medicoId, 'e' => $eidInt]);
            }
        }
    }
    
    $pdo->commit();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: medicos_moderno.php?registrado=1');
    exit;
} catch (Throwable $e) {
    if (isset($pdo)) {
        try { $pdo->rollBack(); } catch (Throwable $e2) {}
    }
    error_log($e->getMessage());
    header('Location: registrar_medico.php?error=servidor');
    exit;
}
