<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nueva_cita.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

require_once __DIR__ . '/../config/database.php';

$pacienteId = filter_var($_POST['paciente_id'] ?? null, FILTER_VALIDATE_INT);
$medicoId = filter_var($_POST['medico_id'] ?? null, FILTER_VALIDATE_INT);
$especialidadId = filter_var($_POST['especialidad_id'] ?? null, FILTER_VALIDATE_INT);
$modalidad = $_POST['modalidad'] ?? 'presencial';
$fecha = trim($_POST['fecha'] ?? '');
$horaInicio = trim($_POST['hora_inicio'] ?? '');
$motivo = trim($_POST['motivo'] ?? '') ?: null;

if (!$pacienteId || !$medicoId || !$especialidadId || $fecha === '' || $horaInicio === '') {
    header('Location: nueva_cita.php?error=datos');
    exit;
}

if ($fecha < date('Y-m-d')) {
    header('Location: nueva_cita.php?error=pasado');
    exit;
}

if (!in_array($modalidad, ['presencial', 'virtual'], true)) {
    $modalidad = 'presencial';
}

try {
    $pdo = conectarBD();
    $pdo->beginTransaction();
    
    $checkMed = $pdo->prepare("SELECT activo FROM medicos WHERE id = :id");
    $checkMed->execute(['id' => $medicoId]);
    $med = $checkMed->fetch();
    if (!$med || $med['activo'] !== true) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=inactivo');
        exit;
    }
    
    $checkPac = $pdo->prepare("SELECT activo FROM pacientes WHERE id = :id");
    $checkPac->execute(['id' => $pacienteId]);
    $pac = $checkPac->fetch();
    if (!$pac || $pac['activo'] !== true) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=inactivo');
        exit;
    }
    
    $checkEsp = $pdo->prepare("SELECT activo FROM especialidades WHERE id = :id");
    $checkEsp->execute(['id' => $especialidadId]);
    $esp = $checkEsp->fetch();
    if (!$esp || $esp['activo'] !== true) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=inactivo');
        exit;
    }
    
    $checkMe = $pdo->prepare("SELECT 1 FROM medicos_especialidades WHERE medico_id = :m AND especialidad_id = :e");
    $checkMe->execute(['m' => $medicoId, 'e' => $especialidadId]);
    if (!$checkMe->fetch()) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=horario');
        exit;
    }
    
    $diaSemana = (int)date('N', strtotime($fecha)); // 1 lunes .. 7 domingo
    $checkHor = $pdo->prepare("SELECT id, hora_inicio, hora_fin, intervalo_minutos FROM horarios_atencion WHERE medico_id = :m AND dia_semana = :d AND activo = TRUE ORDER BY hora_inicio ASC");
    $checkHor->execute(['m' => $medicoId, 'd' => $diaSemana]);
    $horarios = $checkHor->fetchAll();
    
    if (empty($horarios)) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=horario');
        exit;
    }
    
    // Encontrar el horario que puede acomodar este slot
    $horario = null;
    $intervaloMin = null;
    $finPropuestoTs = null;
    foreach ($horarios as $h) {
        $horaInicioNorm = strlen($horaInicio) === 5 ? $horaInicio . ':00' : $horaInicio;
        $inicioSlotTs = strtotime($fecha . ' ' . $horaInicioNorm);
        $finTurno = strtotime($fecha . ' ' . $h['hora_fin']);
        $inicioHorarioTs = strtotime($fecha . ' ' . $h['hora_inicio']);
        $intervaloMinutos = (int)$h['intervalo_minutos'];
        if ($inicioSlotTs === false || $finTurno === false || $inicioHorarioTs === false) {
            continue;
        }
        $finPropuesto = $inicioSlotTs + ($intervaloMinutos * 60);
        if ($inicioSlotTs < $inicioHorarioTs || $finPropuesto > $finTurno) {
            continue;
        }
        $diffMin = ($inicioSlotTs - $inicioHorarioTs) / 60;
        if ($diffMin < 0 || ($diffMin % $intervaloMinutos) !== 0) {
            continue;
        }
        $horario = $h;
        $intervaloMin = $intervaloMinutos;
        $finPropuestoTs = $finPropuesto;
        break;
    }
    
    if (!$horario) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=horario');
        exit;
    }
    
    if ($finPropuestoTs === null) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=horario');
        exit;
    }
    $inicioTs = $fecha . ' ' . $horaInicio . ':00';
    $finTs = date('Y-m-d H:i:s', $finPropuestoTs);
    
    // Verificar bloqueos
    $checkBloq = $pdo->prepare("SELECT 1 FROM bloqueos_horario WHERE medico_id = :m AND tsrange(inicio, fin, '[)') && tsrange(:ini::timestamp, :fin::timestamp, '[)')");
    $checkBloq->execute(['m' => $medicoId, 'ini' => $inicioTs, 'fin' => $finTs]);
    if ($checkBloq->fetch()) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=bloqueado');
        exit;
    }
    
    // Verificar citas superpuestas activas
    $checkCit = $pdo->prepare("SELECT 1 FROM citas WHERE medico_id = :m AND estado IN ('solicitada','confirmada') AND tsrange(inicio, fin, '[)') && tsrange(:ini::timestamp, :fin::timestamp, '[)')");
    $checkCit->execute(['m' => $medicoId, 'ini' => $inicioTs, 'fin' => $finTs]);
    if ($checkCit->fetch()) {
        $pdo->rollBack();
        header('Location: nueva_cita.php?error=conflicto');
        exit;
    }
    
    $codigo = 'CITA-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Ymd');
    
    $stmt = $pdo->prepare("INSERT INTO citas (codigo_cita, paciente_id, medico_id, especialidad_id, inicio, fin, modalidad, estado, motivo, registrado_por)
                           VALUES (:cod, :p, :m, :e, :ini, :fin, :mod, 'solicitada', :mot, :reg) RETURNING id");
    $stmt->execute([
        'cod' => $codigo,
        'p' => $pacienteId,
        'm' => $medicoId,
        'e' => $especialidadId,
        'ini' => $inicioTs,
        'fin' => $finTs,
        'mod' => $modalidad,
        'mot' => $motivo,
        'reg' => $_SESSION['usuario_id']
    ]);
    
    $pdo->commit();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: citas_moderno.php?registrada=1');
    exit;
} catch (Throwable $e) {
    if (isset($pdo)) {
        try { $pdo->rollBack(); } catch (Throwable $e2) {}
    }
    error_log($e->getMessage());
    header('Location: nueva_cita.php?error=servidor');
    exit;
}
