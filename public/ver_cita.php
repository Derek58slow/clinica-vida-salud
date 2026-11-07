<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login_moderno.php");
    exit;
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    echo 'La cita solicitada no existe.';
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . '/includes/panel_helpers.php';

$pdo = conectarBD();
$stmt = $pdo->prepare("SELECT c.*, p.nombres AS pac_nombres, p.apellidos AS pac_apellidos, p.documento_identidad, p.telefono AS pac_telefono, p.correo AS pac_correo,
                              m.nombres AS med_nombres, m.apellidos AS med_apellidos, m.numero_licencia,
                              e.nombre AS especialidad, u.nombre AS registrado_por
                       FROM citas c
                       JOIN pacientes p ON p.id = c.paciente_id
                       JOIN medicos m ON m.id = c.medico_id
                       JOIN especialidades e ON e.id = c.especialidad_id
                       LEFT JOIN usuarios u ON u.id = c.registrado_por
                       WHERE c.id = :id");
$stmt->execute(["id" => $id]);
$cita = $stmt->fetch();
if (!$cita) {
    http_response_code(404);
    echo 'La cita solicitada no existe.';
    exit;
}

$rolId = (int)($_SESSION["rol_id"] ?? 0);
if (!in_array($rolId, [1, 2, 3, 4], true)) {
    http_response_code(403);
    exit("No tienes permiso para ver esta cita.");
}
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
if ($rolId === 3) {
    $stm = $pdo->prepare("SELECT id FROM medicos WHERE usuario_id = :u AND activo = TRUE");
    $stm->execute(["u" => $_SESSION["usuario_id"]]);
    $mp = $stm->fetch();
    if (!$mp || (int)$mp["id"] !== (int)$cita["medico_id"]) {
        http_response_code(403);
        exit("No tienes permiso para ver esta cita.");
    }
}
if ($rolId === 4) {
    $stm = $pdo->prepare("SELECT id FROM pacientes WHERE usuario_id = :u AND activo = TRUE");
    $stm->execute(["u" => $_SESSION["usuario_id"]]);
    $pacientePropio = $stm->fetch();
    if (!$pacientePropio || (int)$pacientePropio["id"] !== (int)$cita["paciente_id"]) {
        http_response_code(403);
        exit("No tienes permiso para ver esta cita.");
    }
}

$histStmt = $pdo->prepare("SELECT h.*, u.nombre AS usuario_nombre FROM historial_citas h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.cita_id = :id ORDER BY h.created_at DESC,h.id DESC LIMIT 100");
$histStmt->execute(["id" => $id]);
$historial = $histStmt->fetchAll();
$contexto = contextoAgenda($_GET + ['fecha'=>substr($cita['inicio'],0,10)]);
parse_str($contexto, $camposContexto);
$puedeRegistrarAsistencia = transicionCitaPermitida($cita['estado'], 'completada', $rolId, new DateTimeImmutable($cita['inicio']));

renderSidebar($rolId === 4 ? "mis_citas" : ($rolId === 3 ? "agenda" : "citas"));
?>
<div class="appointment-detail">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="doctor-eyebrow">Información de la atención</span>
            <h1 class="page-heading-title">Detalle de cita</h1>
            <p style="color: var(--color-text-light);"><?= htmlspecialchars($cita["codigo_cita"]) ?></p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($rolId === 3): ?>
                <a href="agenda.php?<?= h($contexto) ?>" class="btn btn-secondary"><?= icono('volver') ?> Volver a mi agenda</a>
                <a href="medico_paciente.php?id=<?= (int)$cita['paciente_id'] ?>" class="btn btn-primary"><?= icono('pacientes') ?> Ficha del paciente</a>
            <?php elseif ($rolId === 4): ?>
                <a href="mis_citas.php" class="btn btn-secondary">Volver a mis citas</a>
                <a href="comprobante_cita.php?id=<?= (int)$id ?>" target="_blank" class="btn btn-primary"><?= icono('historial') ?> Ver comprobante PDF</a>
            <?php else: ?>
                <a href="agenda.php" class="btn btn-secondary">Volver a Agenda</a>
                <a href="citas_moderno.php" class="btn btn-secondary">Listado</a>
            <?php endif; ?>
            <?php if (in_array($rolId, [1,2,4], true) && in_array($cita["estado"], ["solicitada","confirmada"], true) && strtotime($cita['inicio']) > time()): ?>
                <a href="reprogramar_cita.php?id=<?= (int)$id ?>" class="btn btn-primary">Reprogramar</a>
            <?php endif; ?>
            <?php if (in_array($rolId, [1,2,4], true) && in_array($cita["estado"], ["solicitada","confirmada"], true) && strtotime($cita['inicio']) > time()): ?>
                <a href="cancelar_cita.php?id=<?= (int)$id ?>" class="btn" style="background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5;">Cancelar</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET["reprogramada"])): ?>
        <div class="alert alert-success" style="margin-bottom: 16px;">Cita reprogramada correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET["cancelada"])): ?>
        <div class="alert alert-success" style="margin-bottom: 16px;">Cita cancelada correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET["estado_ok"])): ?>
        <div class="alert alert-success" style="margin-bottom: 16px;">Estado actualizado correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET["error"])): ?>
        <div class="alert alert-error" style="margin-bottom: 16px;">
            <?php
            $e = ["fecha" => "No se puede registrar atención ni inasistencia antes del inicio de la cita.", "no_reprogramar" => "Solo se pueden reprogramar citas solicitadas o confirmadas.", "no_cancelar" => "Solo se pueden cancelar citas solicitadas o confirmadas.", "transicion" => "Transición de estado no permitida.", "estado" => "Estado inválido.", "servidor" => "Error al procesar la solicitud."];
            echo htmlspecialchars($e[$_GET["error"]] ?? "Error.");
            ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Información de la Cita</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Estado</div>
                    <span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= nombreEstado($cita['estado']) ?></span>
                </div>
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Fecha y Hora</div>
                    <div style="font-weight: 600;"><?= date("d/m/Y H:i", strtotime($cita["inicio"])) ?> - <?= date("H:i", strtotime($cita["fin"])) ?></div>
                </div>
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Modalidad</div>
                    <div style="font-weight: 600;"><?= ucfirst($cita["modalidad"]) ?></div>
                </div>
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Especialidad</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($cita["especialidad"]) ?></div>
                </div>
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Médico</div>
                    <div style="font-weight: 600;">Dr(a). <?= htmlspecialchars($cita["med_apellidos"].", ".$cita["med_nombres"]) ?></div>
                    <div style="color: var(--color-text-light); font-size: 13px;">Lic. <?= htmlspecialchars($cita["numero_licencia"]) ?></div>
                </div>
                <div>
                    <div style="color: var(--color-text-light); font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Paciente</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($cita["pac_apellidos"].", ".$cita["pac_nombres"]) ?></div>
                    <div style="color: var(--color-text-light); font-size: 13px;">CI: <?= htmlspecialchars($cita["documento_identidad"]) ?></div>
                    <?php if ($cita["pac_telefono"]): ?><div style="color: var(--color-text-light); font-size: 13px;">Tel: <?= htmlspecialchars($cita["pac_telefono"]) ?></div><?php endif; ?>
                </div>
            </div>
            <?php if ($cita["motivo"]): ?>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--color-border);">
                <div style="color: var(--color-text-light); font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">Motivo</div>
                <div><?= nl2br(htmlspecialchars($cita["motivo"])) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($cita["motivo_cancelacion"]): ?>
            <div style="margin-top: 16px; padding: 12px; background: #FEF2F2; border-radius: 8px; color: #991B1B;">
                <strong>Motivo de cancelación:</strong> <?= nl2br(htmlspecialchars($cita["motivo_cancelacion"])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (in_array($rolId, [1,2,3]) && in_array($cita["estado"], ['solicitada','confirmada'], true)): ?>
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <div class="section-title"><span class="section-icon blue"><?= icono('atendida') ?></span><h2 class="card-title">Seguimiento de la cita</h2></div>
        </div>
        <div class="card-body">
            <?php if ($rolId === 3 && $cita['estado'] === 'solicitada'): ?><p class="form-hint">Esta cita está pendiente de confirmación por recepción. La asistencia podrá registrarse cuando esté confirmada y haya llegado su hora de inicio.</p><?php endif; ?>
            <?php if ($cita['estado'] === 'confirmada' && !$puedeRegistrarAsistencia): ?><p class="form-hint">La cita está confirmada. Podrás registrar atención o inasistencia a partir del <?= date('d/m/Y H:i',strtotime($cita['inicio'])) ?>.</p><?php endif; ?>
            <div class="action-row">
                <?php if ($cita["estado"] === "solicitada"): ?>
                    <?php if (in_array($rolId, [1,2], true)): ?>
                    <form method="POST" action="cambiar_estado_cita.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                        <input type="hidden" name="estado" value="confirmada">
                        <?php foreach ($camposContexto as $campo=>$valor): ?><input type="hidden" name="<?= h($campo) ?>" value="<?= h((string)$valor) ?>"><?php endforeach; ?>
                        <button type="submit" class="btn btn-primary">Marcar como Confirmada</button>
                    </form>
                    <a href="cancelar_cita.php?id=<?= (int)$id ?>" class="btn" style="background: #FEE2E2; color: #991B1B;">Cancelar</a>
                    <?php endif; ?>
                <?php elseif ($cita["estado"] === "confirmada"): ?>
                    <?php if ($puedeRegistrarAsistencia): ?>
                    <form method="POST" action="cambiar_estado_cita.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                        <input type="hidden" name="estado" value="completada">
                        <?php foreach ($camposContexto as $campo=>$valor): ?><input type="hidden" name="<?= h($campo) ?>" value="<?= h((string)$valor) ?>"><?php endforeach; ?>
                        <button type="submit" class="btn btn-primary"><?= icono('atendida') ?> Marcar como atendida</button>
                    </form>
                    <form method="POST" action="cambiar_estado_cita.php" data-confirm="¿Confirmas que este paciente no asistió? La cita quedará cerrada como inasistencia.">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                        <input type="hidden" name="estado" value="no_asistio">
                        <?php foreach ($camposContexto as $campo=>$valor): ?><input type="hidden" name="<?= h($campo) ?>" value="<?= h((string)$valor) ?>"><?php endforeach; ?>
                        <button type="submit" class="btn btn-warning"><?= icono('ausencia') ?> Registrar inasistencia</button>
                    </form>
                    <?php endif; ?>
                    <?php if (in_array($rolId, [1,2], true)): ?>
                    <a href="cancelar_cita.php?id=<?= (int)$id ?>" class="btn" style="background: #FEE2E2; color: #991B1B;">Cancelar</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($historial)): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Historial de Cambios</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Acción</th>
                        <th>Estado</th>
                        <th>Periodo</th>
                        <th>Usuario</th>
                        <th>Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($h["created_at"])) ?></td>
                        <td><?= ucfirst($h["accion"]) ?></td>
                        <td><?= $h["estado_anterior"] ? ucfirst(str_replace("_"," ",$h["estado_anterior"]))." → " : "" ?><?= ucfirst(str_replace("_"," ",$h["estado_nuevo"])) ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($h["inicio_nuevo"])) ?> - <?= date("H:i", strtotime($h["fin_nuevo"])) ?></td>
                        <td><?= $h["usuario_nombre"] ? htmlspecialchars($h["usuario_nombre"]) : "-" ?></td>
                        <td><?= $h["comentario"] ? htmlspecialchars($h["comentario"]) : "-" ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php renderFooter(); ?>

