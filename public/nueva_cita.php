<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para agendar citas.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/database.php';

$pacienteId = filter_input(INPUT_GET, 'paciente_id', FILTER_VALIDATE_INT);
$medicoId = filter_input(INPUT_GET, 'medico_id', FILTER_VALIDATE_INT);
$especialidadId = filter_input(INPUT_GET, 'especialidad_id', FILTER_VALIDATE_INT);
$fecha = trim($_GET['fecha'] ?? '');
$horaSeleccionada = trim($_GET['hora'] ?? '');

try {
    $pdo = conectarBD();
    $pacientes = $pdo->query("SELECT id, nombres, apellidos, documento_identidad FROM pacientes WHERE activo = TRUE ORDER BY apellidos, nombres")->fetchAll();
    $especialidades = $pdo->query("SELECT id, nombre FROM especialidades WHERE activo = TRUE ORDER BY nombre")->fetchAll();
    $medicos = [];

    if ($especialidadId) {
        $stmtMedicos = $pdo->prepare(
            "SELECT m.id, m.nombres, m.apellidos
             FROM medicos m
             INNER JOIN medicos_especialidades me ON me.medico_id = m.id
             INNER JOIN especialidades e ON e.id = me.especialidad_id
             WHERE m.activo = TRUE
               AND e.activo = TRUE
               AND me.especialidad_id = :especialidad_id
             ORDER BY m.apellidos, m.nombres"
        );
        $stmtMedicos->execute(['especialidad_id' => $especialidadId]);
        $medicos = $stmtMedicos->fetchAll();

        $medicoValido = array_filter(
            $medicos,
            static fn(array $medico): bool => (int)$medico['id'] === (int)$medicoId
        );
        if ($medicoId && empty($medicoValido)) {
            $medicoId = null;
        }
    } else {
        $medicoId = null;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $pacientes = $especialidades = $medicos = [];
}

$horariosDisponibles = [];

if ($medicoId && $fecha) {
    try {
        $pdo2 = conectarBD();
        $diaSemana = (int)date('N', strtotime($fecha));
        $stmtH = $pdo2->prepare("SELECT hora_inicio, hora_fin, intervalo_minutos FROM horarios_atencion WHERE medico_id = :m AND dia_semana = :d AND activo = TRUE ORDER BY hora_inicio ASC");
        $stmtH->execute(['m' => $medicoId, 'd' => $diaSemana]);
        $horarios = $stmtH->fetchAll();
        
        if (!empty($horarios)) {
            // Obtener bloqueos
            $stmtB = $pdo2->prepare("SELECT inicio, fin FROM bloqueos_horario WHERE medico_id = :m AND DATE(inicio) <= :f AND DATE(fin) >= :f");
            $stmtB->execute(['m' => $medicoId, 'f' => $fecha]);
            $bloqueos = $stmtB->fetchAll();
            
            // Obtener citas activas
            $stmtC = $pdo2->prepare("SELECT inicio, fin FROM citas WHERE medico_id = :m AND DATE(inicio) = :f AND estado IN ('solicitada','confirmada')");
            $stmtC->execute(['m' => $medicoId, 'f' => $fecha]);
            $citasOcupadas = $stmtC->fetchAll();
            
            foreach ($horarios as $horario) {
                $inicio = strtotime($fecha . ' ' . $horario['hora_inicio']);
                $fin = strtotime($fecha . ' ' . $horario['hora_fin']);
                $intervalo = (int)$horario['intervalo_minutos'] * 60;
                
            for ($t = $inicio; $t + $intervalo <= $fin; $t += $intervalo) {
                $horaSlot = date('H:i', $t);
                $slotInicio = $t;
                $slotFin = $slotInicio + $intervalo;
                if ($slotFin > $fin) {
                    continue;
                }
                
                if ($slotFin > $fin) {
                    continue;
                }
                
                if ($fecha == date('Y-m-d') && $slotInicio < time()) {
                    continue;
                }
                
                $ocupado = false;
                foreach ($bloqueos as $b) {
                    $bInicio = strtotime($b['inicio']);
                    $bFin = strtotime($b['fin']);
                    if ($slotInicio < $bFin && $slotFin > $bInicio) {
                        $ocupado = true;
                        break;
                    }
                }
                
                if (!$ocupado) {
                    foreach ($citasOcupadas as $c) {
                        $cInicio = strtotime($c['inicio']);
                        $cFin = strtotime($c['fin']);
                        if ($slotInicio < $cFin && $slotFin > $cInicio) {
                            $ocupado = true;
                            break;
                        }
                    }
                }
                
                    if (!$ocupado) {
                        $horariosDisponibles[] = $horaSlot;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('citas');
?>

<div style="margin-bottom: 24px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Nueva cita médica</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Solo se muestran horarios disponibles</p>
        </div>
        <a href="citas_moderno.php" class="btn btn-secondary">Volver al listado</a>
    </div>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto; box-shadow: var(--shadow-lg);">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $e = [
                    'datos' => 'Completa todos los campos obligatorios.',
                    'pasado' => 'No se pueden agendar citas en fechas pasadas.',
                    'horario' => 'El horario seleccionado no es válido.',
                    'conflicto' => 'El médico ya tiene una cita en ese horario.',
                    'bloqueado' => 'El horario se encuentra bloqueado.',
                    'inactivo' => 'El médico o paciente se encuentra inactivo.',
                    'especialidad' => 'El médico seleccionado no pertenece a la especialidad indicada.',
                    'servidor' => 'No se pudo registrar la cita.'
                ];
                echo htmlspecialchars($e[$_GET['error']] ?? 'Error al guardar la cita.');
                ?>
            </div>
        <?php endif; ?>
        
        <form action="guardar_cita.php" method="POST" novalidate id="formCita">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div style="margin-bottom: 28px;">
                <h3 style="font-size: 17px; font-weight: 600; margin-bottom: 16px;">Datos de la cita</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Paciente</label>
                        <select name="paciente_id" class="form-select" style="height: 44px;" required>
                            <option value="">Seleccionar paciente</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ($pacienteId == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['apellidos'].', '.$p['nombres'].' ('.$p['documento_identidad'].')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Especialidad</label>
                        <select name="especialidad_id" id="especialidad_id" class="form-select" style="height: 44px;" required onchange="actualizarEspecialidad()">
                            <option value="">Seleccionar especialidad</option>
                            <?php foreach ($especialidades as $esp): ?>
                                <option value="<?= (int)$esp['id'] ?>" <?= ($especialidadId == $esp['id']) ? 'selected' : '' ?>><?= htmlspecialchars($esp['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Médico</label>
                        <select name="medico_id" id="medico_id" class="form-select" style="height: 44px;" required onchange="actualizarDisponibilidad()" <?= !$especialidadId ? 'disabled' : '' ?>>
                            <option value=""><?= $especialidadId ? 'Seleccionar médico' : 'Selecciona una especialidad primero' ?></option>
                            <?php foreach ($medicos as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= ($medicoId == $m['id']) ? 'selected' : '' ?>>Dr(a). <?= htmlspecialchars($m['nombres'].' '.$m['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($especialidadId && empty($medicos)): ?>
                            <div style="margin-top: 8px; color: var(--color-warning); font-size: 12px;">No hay médicos activos asignados a esta especialidad.</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Modalidad</label>
                        <select name="modalidad" class="form-select" style="height: 44px;" required>
                            <option value="presencial">Presencial</option>
                            <option value="virtual">Virtual</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div style="padding-top: 28px; border-top: 1px solid var(--color-border); margin-bottom: 28px;">
                <h3 style="font-size: 17px; font-weight: 600; margin-bottom: 16px;">Fecha y hora</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Fecha</label>
                        <input type="date" name="fecha" id="fecha" class="form-input" style="height: 44px;" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($fecha) ?>" required onchange="actualizarDisponibilidad()">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Horario disponible</label>
                        <?php if ($medicoId && $fecha): ?>
                            <?php if (empty($horariosDisponibles)): ?>
                                <div style="padding: 12px; background: #FEF3C7; color: #92400E; border-radius: var(--radius); font-size: 13px;">No hay horarios disponibles para esta fecha</div>
                            <?php else: ?>
                                <select name="hora_inicio" class="form-select" style="height: 44px;" required>
                                    <option value="">Seleccionar hora</option>
                                    <?php foreach ($horariosDisponibles as $h): ?>
                                        <option value="<?= $h ?>" <?= ($horaSeleccionada === $h) ? 'selected' : '' ?>><?= $h ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        <?php else: ?>
                            <select class="form-select" style="height: 44px;" disabled>
                                <option>Selecciona médico y fecha primero</option>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div style="padding-top: 28px; border-top: 1px solid var(--color-border);">
                <div class="form-group">
                    <label class="form-label">Motivo de consulta</label>
                    <textarea name="motivo" class="form-textarea" rows="3" placeholder="Descripción breve del motivo (opcional)"></textarea>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 36px; padding-top: 28px; border-top: 1px solid var(--color-border);">
                <button type="submit" class="btn btn-primary" style="padding: 11px 26px;">Agendar cita</button>
                <a href="citas_moderno.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function parametrosCita() {
    const parametros = new URLSearchParams();
    const paciente = document.querySelector('select[name="paciente_id"]')?.value || '';
    const especialidad = document.getElementById('especialidad_id')?.value || '';
    const medico = document.getElementById('medico_id')?.value || '';
    const fecha = document.getElementById('fecha')?.value || '';

    if (paciente) parametros.set('paciente_id', paciente);
    if (especialidad) parametros.set('especialidad_id', especialidad);
    if (medico) parametros.set('medico_id', medico);
    if (fecha) parametros.set('fecha', fecha);
    return parametros;
}

function actualizarEspecialidad() {
    const parametros = parametrosCita();
    parametros.delete('medico_id');
    window.location.href = 'nueva_cita.php?' + parametros.toString();
}

function actualizarDisponibilidad() {
    const medico = document.getElementById('medico_id').value;
    const fecha = document.getElementById('fecha').value;
    if (medico && fecha) {
        window.location.href = 'nueva_cita.php?' + parametrosCita().toString();
    }
}
</script>

<?php renderFooter(); ?>
