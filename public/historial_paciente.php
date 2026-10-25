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

$pacienteId = filter_input(INPUT_GET, "paciente_id", FILTER_VALIDATE_INT);
if (!$pacienteId) {
    header("Location: pacientes_moderno.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/includes/layout.php";

$pdo = conectarBD();
$stmtP = $pdo->prepare("SELECT * FROM pacientes WHERE id = :id");
$stmtP->execute(["id" => $pacienteId]);
$paciente = $stmtP->fetch();
if (!$paciente) {
    header("Location: pacientes_moderno.php");
    exit;
}

$stmt = $pdo->prepare("SELECT c.*, m.nombres AS med_nombres, m.apellidos AS med_apellidos, e.nombre AS especialidad
                       FROM citas c
                       JOIN medicos m ON m.id = c.medico_id
                       JOIN especialidades e ON e.id = c.especialidad_id
                       WHERE c.paciente_id = :p
                       ORDER BY c.inicio DESC");
$stmt->execute(["p" => $pacienteId]);
$citas = $stmt->fetchAll();

$stmtH = $pdo->prepare("SELECT h.*, c.id AS cita_id, c.codigo_cita, u.nombre AS usuario_nombre
                        FROM historial_citas h
                        JOIN citas c ON c.id = h.cita_id
                        LEFT JOIN usuarios u ON u.id = h.usuario_id
                        WHERE c.paciente_id = :p
                        ORDER BY h.created_at DESC");
$stmtH->execute(["p" => $pacienteId]);
$historial = $stmtH->fetchAll();

renderSidebar("pacientes");
?>
<div style="max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Historial del Paciente</h1>
            <p style="color: var(--color-text-light);"><?= htmlspecialchars($paciente["apellidos"].", ".$paciente["nombres"]) ?> <?php if($paciente["documento_identidad"]): ?>- CI: <?= htmlspecialchars($paciente["documento_identidad"]) ?><?php endif; ?></p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="pacientes_moderno.php" class="btn btn-secondary">Volver a Pacientes</a>
            <a href="editar_paciente_moderno.php?id=<?= (int)$pacienteId ?>" class="btn btn-secondary">Ver Paciente</a>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Citas (Anteriores y Futuras)</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($citas)): ?>
                <div style="padding: 32px; text-align: center; color: var(--color-text-light);">No hay citas registradas</div>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Código</th>
                        <th>Médico</th>
                        <th>Especialidad</th>
                        <th>Modalidad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($citas as $c): 
                        $estado = $c["estado"];
                        $badgeClass = "badge";
                        if ($estado === "confirmada") $badgeClass = "badge badge-success";
                        elseif ($estado === "solicitada") $badgeClass = "badge badge-warning";
                        elseif ($estado === "completada") $badgeClass = "badge badge-info";
                        elseif ($estado === "cancelada") $badgeClass = "badge badge-danger";
                    ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($c["inicio"])) ?></td>
                        <td><?= htmlspecialchars($c["codigo_cita"]) ?></td>
                        <td>Dr(a). <?= htmlspecialchars($c["med_apellidos"].", ".$c["med_nombres"]) ?></td>
                        <td><?= htmlspecialchars($c["especialidad"]) ?></td>
                        <td><?= ucfirst($c["modalidad"]) ?></td>
                        <td><span class="<?= $badgeClass ?>"><?= ucfirst(str_replace("_", " ", $estado)) ?></span></td>
                        <td><a href="ver_cita.php?id=<?= (int)$c["id"] ?>" class="btn btn-secondary" style="padding: 6px 12px;">Ver</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($historial)): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Cambios de Fecha/Horario y Estados</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha Cambio</th>
                        <th>Cita</th>
                        <th>Acción</th>
                        <th>Estado</th>
                        <th>Nuevo Horario</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($h["created_at"])) ?></td>
                        <td><?= htmlspecialchars($h["codigo_cita"]) ?></td>
                        <td><?= ucfirst($h["accion"]) ?></td>
                        <td><?= $h["estado_anterior"] ? ucfirst(str_replace("_"," ",$h["estado_anterior"]))." ? " : "" ?><?= ucfirst(str_replace("_"," ",$h["estado_nuevo"])) ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($h["inicio_nuevo"])) ?> - <?= date("H:i", strtotime($h["fin_nuevo"])) ?></td>
                        <td><?= $h["usuario_nombre"] ? htmlspecialchars($h["usuario_nombre"]) : "-" ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php renderFooter(); ?>

