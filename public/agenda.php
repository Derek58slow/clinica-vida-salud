<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login_moderno.php");
    exit;
}

$rolId = (int) ($_SESSION["rol_id"] ?? 0);
if (!in_array($rolId, [1, 2, 3], true)) {
    http_response_code(403);
    exit("No tienes permiso.");
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . '/includes/panel_helpers.php';

$errorCarga = false;
$medicoFiltro = $especialidadFiltro = null;
$estadoFiltro = '';
$fecha = date('Y-m-d');
$medicoIdPropio = null;
try {
$pdo = conectarBD();

$medicoFiltro = filter_input(INPUT_GET, "medico_id", FILTER_VALIDATE_INT);
$especialidadFiltro = filter_input(INPUT_GET, "especialidad_id", FILTER_VALIDATE_INT);
$estadoFiltro = trim($_GET["estado"] ?? "");
$fecha = trim($_GET["fecha"] ?? date("Y-m-d"));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || DateTime::createFromFormat('!Y-m-d', $fecha)?->format('Y-m-d') !== $fecha) {
    $fecha = date("Y-m-d");
}

$medicoIdPropio = null;
if ($rolId === 3) {
    $mPropio = obtenerMedicoSesion($pdo);
    $medicoIdPropio = $mPropio ? (int)$mPropio['id'] : 0;
    // Cero es un alcance vacío, nunca significa "todos los médicos".
    $medicoFiltro = $medicoIdPropio;
}

$medicos = $pdo->query("SELECT id, nombres, apellidos FROM medicos WHERE activo = TRUE ORDER BY apellidos, nombres")->fetchAll();
$stmtEspecialidades = $pdo->prepare("SELECT e.id,e.nombre FROM especialidades e WHERE e.activo=TRUE" . ($rolId === 3 ? " AND EXISTS(SELECT 1 FROM medicos_especialidades me WHERE me.especialidad_id=e.id AND me.medico_id=:medico)" : '') . ' ORDER BY e.nombre');
$stmtEspecialidades->execute($rolId === 3 ? ['medico' => $medicoIdPropio] : []);
$especialidades = $stmtEspecialidades->fetchAll();
$estados = ["solicitada", "confirmada", "completada", "cancelada", "no_asistio"];

$sql = "SELECT c.id, c.codigo_cita, c.inicio, c.fin, c.modalidad, c.estado, c.motivo,
               p.id AS paciente_id, p.nombres AS pac_nombres, p.apellidos AS pac_apellidos, p.documento_identidad,
               m.id AS medico_id, m.nombres AS med_nombres, m.apellidos AS med_apellidos,
               e.nombre AS especialidad
        FROM citas c
        JOIN pacientes p ON p.id = c.paciente_id
        JOIN medicos m ON m.id = c.medico_id
        JOIN especialidades e ON e.id = c.especialidad_id
        WHERE DATE(c.inicio) = :fecha";
$params = ["fecha" => $fecha];

if ($rolId === 3 || $medicoFiltro) {
    $sql .= " AND c.medico_id = :medico_id";
    $params["medico_id"] = $medicoFiltro;
}
if ($especialidadFiltro) {
    $sql .= " AND c.especialidad_id = :especialidad_id";
    $params["especialidad_id"] = $especialidadFiltro;
}
if ($estadoFiltro && in_array($estadoFiltro, $estados, true)) {
    $sql .= " AND c.estado = :estado";
    $params["estado"] = $estadoFiltro;
}
$sql .= " ORDER BY c.inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();

$fechaAnterior = date("Y-m-d", strtotime($fecha . " -1 day"));
$fechaSiguiente = date("Y-m-d", strtotime($fecha . " +1 day"));
} catch (Throwable $e) {
    error_log($e->getMessage());
    $errorCarga = true;
    $medicos = $especialidades = $citas = [];
    $estados = ['solicitada','confirmada','completada','cancelada','no_asistio'];
    $fechaAnterior = date('Y-m-d',strtotime($fecha.' -1 day'));
    $fechaSiguiente = date('Y-m-d',strtotime($fecha.' +1 day'));
}

renderSidebar("agenda");
$contexto = contextoAgenda(['fecha'=>$fecha,'estado'=>$estadoFiltro,'medico_id'=>$medicoFiltro,'especialidad_id'=>$especialidadFiltro]);
?>
<div class="agenda-page">
    <?php if ($errorCarga): ?><div class="alert alert-error" role="alert">No se pudo cargar la agenda. Inténtalo nuevamente en unos momentos.</div><?php elseif ($rolId === 3 && !$medicoIdPropio): ?><div class="alert alert-warning" role="status">Tu cuenta no tiene un perfil médico activo. Administración debe vincularla para mostrar tu agenda.</div><?php endif; ?>
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <span class="doctor-eyebrow">Organiza tu jornada</span>
            <h1 class="page-heading-title"><?= $rolId === 3 ? 'Mi agenda médica' : 'Agenda médica' ?></h1>
            <p class="card-subtitle">Revisa cada cita, su estado y los datos de atención.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="agenda.php?fecha=<?= htmlspecialchars($fechaAnterior) ?><?= $medicoFiltro && $rolId !== 3 ? "&medico_id=$medicoFiltro" : "" ?><?= $especialidadFiltro ? "&especialidad_id=$especialidadFiltro" : "" ?><?= $estadoFiltro ? "&estado=".urlencode($estadoFiltro) : "" ?>" class="btn btn-secondary">← Anterior</a>
            <a href="agenda.php?fecha=<?= date("Y-m-d") ?><?= $medicoFiltro && $rolId !== 3 ? "&medico_id=$medicoFiltro" : "" ?><?= $especialidadFiltro ? "&especialidad_id=$especialidadFiltro" : "" ?><?= $estadoFiltro ? "&estado=".urlencode($estadoFiltro) : "" ?>" class="btn btn-secondary">Hoy</a>
            <a href="agenda.php?fecha=<?= htmlspecialchars($fechaSiguiente) ?><?= $medicoFiltro && $rolId !== 3 ? "&medico_id=$medicoFiltro" : "" ?><?= $especialidadFiltro ? "&especialidad_id=$especialidadFiltro" : "" ?><?= $estadoFiltro ? "&estado=".urlencode($estadoFiltro) : "" ?>" class="btn btn-secondary">Siguiente →</a>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="padding: 20px;">
            <form method="GET" action="agenda.php" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                    <label class="form-label" for="agenda-fecha">Fecha</label>
                    <input id="agenda-fecha" type="date" name="fecha" class="form-input" value="<?= htmlspecialchars($fecha) ?>" onchange="this.form.submit()">
                </div>
                <?php if ($rolId !== 3): ?>
                <div class="form-group" style="margin-bottom: 0; min-width: 220px;">
                    <label class="form-label" for="agenda-medico">Médico</label>
                    <select id="agenda-medico" name="medico_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los médicos</option>
                        <?php foreach ($medicos as $m): ?>
                            <option value="<?= (int)$m["id"] ?>" <?= $medicoFiltro == $m["id"] ? "selected" : "" ?>>Dr(a). <?= htmlspecialchars($m["apellidos"].", ".$m["nombres"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group" style="margin-bottom: 0; min-width: 220px;">
                    <label class="form-label" for="agenda-especialidad">Especialidad</label>
                    <select id="agenda-especialidad" name="especialidad_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($especialidades as $e): ?>
                            <option value="<?= (int)$e["id"] ?>" <?= $especialidadFiltro == $e["id"] ? "selected" : "" ?>><?= htmlspecialchars($e["nombre"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                    <label class="form-label" for="agenda-estado">Estado</label>
                    <select id="agenda-estado" name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $est): ?>
                            <option value="<?= $est ?>" <?= $estadoFiltro === $est ? "selected" : "" ?>><?= ucfirst(str_replace("_", " ", $est)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <h2 style="font-size: 18px; font-weight: 600; color: var(--color-text);">Citas del <?= date("d/m/Y", strtotime($fecha)) ?></h2>
        <span style="color: var(--color-text-light); font-size: 14px;"><?= count($citas) ?> cita(s)</span>
    </div>

    <?php if ($errorCarga || ($rolId === 3 && !$medicoIdPropio)): ?>
    <?php elseif (empty($citas)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 48px 20px;">
                <div style="color: var(--color-text-light); font-size: 15px;">No hay citas programadas para esta fecha con los filtros seleccionados.</div>
            </div>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 16px;">
            <?php foreach ($citas as $c): 
                $estado = $c["estado"];
                $badgeClass = "badge";
                if ($estado === "confirmada") $badgeClass = "badge badge-success";
                elseif ($estado === "solicitada") $badgeClass = "badge badge-warning";
                elseif ($estado === "completada") $badgeClass = "badge badge-info";
                elseif ($estado === "cancelada") $badgeClass = "badge badge-danger";
                elseif ($estado === "no_asistio") $badgeClass = "badge";
            ?>
            <div class="card" style="transition: box-shadow 0.2s;">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center; justify-content: space-between;">
                        <div style="display: flex; gap: 16px; align-items: center; flex: 1; min-width: 280px;">
                            <div style="background: linear-gradient(135deg, var(--color-primary) 0%, #1E40AF 100%); color: white; width: 56px; height: 56px; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;">
                                <div style="font-size: 16px; font-weight: 700;"><?= date("H:i", strtotime($c["inicio"])) ?></div>
                                <div style="font-size: 11px; opacity: 0.9;">a <?= date("H:i", strtotime($c["fin"])) ?></div>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                                    <h3 style="font-size: 16px; font-weight: 600; color: var(--color-text); margin: 0;"><?= htmlspecialchars($c["pac_apellidos"].", ".$c["pac_nombres"]) ?></h3>
                                    <span class="<?= $badgeClass ?>"><?= ucfirst(str_replace("_", " ", $estado)) ?></span>
                                    <span class="badge"><?= htmlspecialchars($c["modalidad"]) ?></span>
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 16px; color: var(--color-text-light); font-size: 13px;">
                                    <span>Médico: Dr(a). <?= htmlspecialchars($c["med_apellidos"].", ".$c["med_nombres"]) ?></span>
                                    <span>Especialidad: <?= htmlspecialchars($c["especialidad"]) ?></span>
                                    <span>Código: <?= htmlspecialchars($c["codigo_cita"]) ?></span>
                                    <?php if ($c["documento_identidad"]): ?><span>CI: <?= htmlspecialchars($c["documento_identidad"]) ?></span><?php endif; ?>
                                </div>
                                <?php if ($c["motivo"]): ?>
                                    <div style="margin-top: 8px; color: var(--color-text); font-size: 13px; background: var(--color-bg); padding: 8px 12px; border-radius: 8px;">
                                        <strong>Motivo:</strong> <?= htmlspecialchars($c["motivo"]) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="ver_cita.php?id=<?= (int)$c["id"] ?>&amp;<?= h($contexto) ?>" class="btn btn-secondary">Ver cita <?= icono('flecha') ?></a>
                            <?php if ($rolId === 3): ?><a href="medico_paciente.php?id=<?= (int)$c['paciente_id'] ?>" class="btn btn-secondary"><?= icono('pacientes') ?> Ficha del paciente</a><?php endif; ?>
                            <?php if (in_array($rolId, [1, 2]) && in_array($estado, ["solicitada", "confirmada"], true)): ?>
                                <a href="reprogramar_cita.php?id=<?= (int)$c["id"] ?>" class="btn btn-secondary" style="padding: 8px 16px;">Reprogramar</a>
                            <?php endif; ?>
                            <?php if (in_array($rolId, [1, 2]) && in_array($estado, ["solicitada", "confirmada"], true)): ?>
                                <a href="cancelar_cita.php?id=<?= (int)$c["id"] ?>" class="btn" style="padding: 8px 16px; background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5;">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>

