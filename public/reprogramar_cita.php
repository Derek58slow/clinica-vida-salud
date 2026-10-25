<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login_moderno.php");
    exit;
}

$rolId = (int) ($_SESSION["rol_id"] ?? 0);
if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit("No tienes permiso.");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: citas_moderno.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pdo = conectarBD();
$stmt = $pdo->prepare("SELECT c.*, m.id AS medico_id, p.id AS paciente_id, e.id AS especialidad_id FROM citas c JOIN medicos m ON m.id = c.medico_id JOIN pacientes p ON p.id = c.paciente_id JOIN especialidades e ON e.id = c.especialidad_id WHERE c.id = :id");
$stmt->execute(["id" => $id]);
$cita = $stmt->fetch();
if (!$cita) {
    header("Location: citas_moderno.php");
    exit;
}

if (in_array($cita["estado"], ["completada", "cancelada"], true)) {
    header("Location: ver_cita.php?id=".$id."&error=no_reprogramar");
    exit;
}

$fecha = trim($_GET["fecha"] ?? "");
$horaSeleccionada = trim($_GET["hora"] ?? "");
$horariosDisponibles = [];

if ($fecha && preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha)) {
    if ($fecha >= date("Y-m-d")) {
        try {
            $diaSemana = (int)date("N", strtotime($fecha));
            $stmtH = $pdo->prepare("SELECT hora_inicio, hora_fin, intervalo_minutos FROM horarios_atencion WHERE medico_id = :m AND dia_semana = :d AND activo = TRUE ORDER BY hora_inicio ASC");
            $stmtH->execute(["m" => $cita["medico_id"], "d" => $diaSemana]);
            $horarios = $stmtH->fetchAll();
            if (!empty($horarios)) {
                $stmtB = $pdo->prepare("SELECT inicio, fin FROM bloqueos_horario WHERE medico_id = :m AND DATE(inicio) <= :f AND DATE(fin) >= :f");
                $stmtB->execute(["m" => $cita["medico_id"], "f" => $fecha]);
                $bloqueos = $stmtB->fetchAll();
                $stmtC = $pdo->prepare("SELECT inicio, fin FROM citas WHERE medico_id = :m AND id != :id AND DATE(inicio) = :f AND estado IN (\"solicitada\",\"confirmada\")");
                $stmtC->execute(["m" => $cita["medico_id"], "id" => $id, "f" => $fecha]);
                $citasOcupadas = $stmtC->fetchAll();
                foreach ($horarios as $horario) {
                    $inicio = strtotime($fecha." ".$horario["hora_inicio"]);
                    $fin = strtotime($fecha." ".$horario["hora_fin"]);
                    $intervalo = (int)$horario["intervalo_minutos"] * 60;
                    for ($t = $inicio; $t + $intervalo <= $fin; $t += $intervalo) {
                        $slotInicio = $t;
                        $slotFin = $slotInicio + $intervalo;
                        if ($slotFin > $fin) continue;
                        if ($fecha == date("Y-m-d") && $slotInicio < time()) continue;
                        $ocupado = false;
                        foreach ($bloqueos as $b) {
                            if ($slotInicio < strtotime($b["fin"]) && $slotFin > strtotime($b["inicio"])) { $ocupado = true; break; }
                        }
                        if (!$ocupado) {
                            foreach ($citasOcupadas as $c2) {
                                if ($slotInicio < strtotime($c2["fin"]) && $slotFin > strtotime($c2["inicio"])) { $ocupado = true; break; }
                            }
                        }
                        if (!$ocupado) {
                            $horariosDisponibles[] = date("H:i", $t);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Solicitud no válida.");
    }
    $nuevaFecha = trim($_POST["fecha"] ?? "");
    $nuevaHora = trim($_POST["hora_inicio"] ?? "");
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $nuevaFecha) || !preg_match("/^\d{2}:\d{2}$/", $nuevaHora) || $nuevaFecha < date("Y-m-d")) {
        header("Location: reprogramar_cita.php?id=$id&error=datos");
        exit;
    }
    try {
        $pdo->beginTransaction();
        $diaSemana = (int)date("N", strtotime($nuevaFecha));
        $stmtH = $pdo->prepare("SELECT hora_inicio, hora_fin, intervalo_minutos FROM horarios_atencion WHERE medico_id = :m AND dia_semana = :d AND activo = TRUE ORDER BY hora_inicio ASC");
        $stmtH->execute(["m" => $cita["medico_id"], "d" => $diaSemana]);
        $horarios = $stmtH->fetchAll();
        $horario = null; $finPropuestoTs = null;
        $nuevaHoraNorm = $nuevaHora.":00";
        foreach ($horarios as $h) {
            $inicioSlotTs = strtotime($nuevaFecha." ".$nuevaHoraNorm);
            $finTurno = strtotime($nuevaFecha." ".$h["hora_fin"]);
            $inicioHorarioTs = strtotime($nuevaFecha." ".$h["hora_inicio"]);
            $intervaloMinutos = (int)$h["intervalo_minutos"];
            if ($inicioSlotTs===false||$finTurno===false||$inicioHorarioTs===false) continue;
            $finPropuesto = $inicioSlotTs + ($intervaloMinutos*60);
            if ($inicioSlotTs < $inicioHorarioTs || $finPropuesto > $finTurno) continue;
            $diffMin = ($inicioSlotTs - $inicioHorarioTs)/60;
            if ($diffMin < 0 || ($diffMin % $intervaloMinutos) !== 0) continue;
            $horario = $h; $finPropuestoTs = $finPropuesto; break;
        }
        if (!$horario) {
            $pdo->rollBack();
            header("Location: reprogramar_cita.php?id=$id&error=horario");
            exit;
        }
        $inicioNuevo = $nuevaFecha." ".$nuevaHoraNorm;
        $finNuevo = date("Y-m-d H:i:s", $finPropuestoTs);
        $stmtB = $pdo->prepare("SELECT 1 FROM bloqueos_horario WHERE medico_id = :m AND tsrange(inicio, fin, \"[\") && tsrange(:ini::timestamp, :fin::timestamp, \"[\")");
        $stmtB->execute(["m" => $cita["medico_id"], "ini" => $inicioNuevo, "fin" => $finNuevo]);
        if ($stmtB->fetch()) {
            $pdo->rollBack();
            header("Location: reprogramar_cita.php?id=$id&error=bloqueado");
            exit;
        }
        $stmtC = $pdo->prepare("SELECT 1 FROM citas WHERE medico_id = :m AND id != :id AND estado IN (\"solicitada\",\"confirmada\") AND tsrange(inicio, fin, \"[\") && tsrange(:ini::timestamp, :fin::timestamp, \"[\")");
        $stmtC->execute(["m" => $cita["medico_id"], "id" => $id, "ini" => $inicioNuevo, "fin" => $finNuevo]);
        if ($stmtC->fetch()) {
            $pdo->rollBack();
            header("Location: reprogramar_cita.php?id=$id&error=conflicto");
            exit;
        }
        $upd = $pdo->prepare("UPDATE citas SET inicio = :ini, fin = :fin, updated_at = now() WHERE id = :id");
        $upd->execute(["ini" => $inicioNuevo, "fin" => $finNuevo, "id" => $id]);
        $pdo->commit();
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        header("Location: ver_cita.php?id=$id&reprogramada=1");
        exit;
    } catch (Throwable $e) {
        if (isset($pdo)) { try { $pdo->rollBack(); } catch (Throwable $e2) {} }
        error_log($e->getMessage());
        header("Location: reprogramar_cita.php?id=$id&error=servidor");
        exit;
    }
}

require_once __DIR__ . "/includes/layout.php";
renderSidebar("citas");
?>
<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Reprogramar Cita</h1>
            <p style="color: var(--color-text-light);">Código: <?= htmlspecialchars($cita["codigo_cita"]) ?></p>
        </div>
        <a href="ver_cita.php?id=<?= (int)$id ?>" class="btn btn-secondary">Volver</a>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px;">Paciente</div>
                    <div style="font-weight: 600;">Paciente #<?= (int)$cita["paciente_id"] ?></div>
                </div>
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px;">Médico</div>
                    <div style="font-weight: 600;">Médico #<?= (int)$cita["medico_id"] ?></div>
                </div>
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px;">Fecha/Hora actual</div>
                    <div style="font-weight: 600;"><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET["error"])): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
            <?php
            $e = [
                "datos" => "Completa todos los campos.",
                "pasado" => "No se pueden reprogramar a fechas pasadas.",
                "horario" => "El horario seleccionado no es válido.",
                "conflicto" => "El médico ya tiene una cita en ese horario.",
                "bloqueado" => "El horario se encuentra bloqueado.",
                "servidor" => "No se pudo reprogramar la cita."
            ];
            echo htmlspecialchars($e[$_GET["error"]] ?? "Error al reprogramar.");
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="reprogramar_cita.php" style="margin-bottom: 24px;">
                <input type="hidden" name="id" value="<?= (int)$id ?>">
                <div class="form-group" style="max-width: 300px;">
                    <label class="form-label required">Nueva fecha</label>
                    <input type="date" name="fecha" class="form-input" min="<?= date("Y-m-d") ?>" value="<?= htmlspecialchars($fecha) ?>" onchange="this.form.submit()" required>
                </div>
            </form>

            <?php if ($fecha): ?>
                <form method="POST" action="reprogramar_cita.php?id=<?= (int)$id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                    <div class="form-group" style="max-width: 300px; margin-bottom: 24px;">
                        <label class="form-label required">Nuevo horario disponible</label>
                        <?php if (empty($horariosDisponibles)): ?>
                            <div style="padding: 12px; background: #FEF3C7; color: #92400E; border-radius: 8px;">No hay horarios disponibles</div>
                        <?php else: ?>
                            <select name="hora_inicio" class="form-select" required>
                                <option value="">Seleccionar hora</option>
                                <?php foreach ($horariosDisponibles as $h): ?>
                                    <option value="<?= $h ?>" <?= ($horaSeleccionada === $h) ? "selected" : "" ?>><?= $h ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($horariosDisponibles)): ?>
                        <button type="submit" class="btn btn-primary">Confirmar reprogramación</button>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div style="color: var(--color-text-light);">Seleccione una fecha para ver horarios disponibles.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php renderFooter(); ?>

