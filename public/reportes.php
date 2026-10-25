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

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/includes/layout.php";

$pdo = conectarBD();

$fechaDesde = trim($_GET["desde"] ?? date("Y-m-01"));
$fechaHasta = trim($_GET["hasta"] ?? date("Y-m-d"));
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaDesde)) $fechaDesde = date("Y-m-01");
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaHasta)) $fechaHasta = date("Y-m-d");

$params = ["desde" => $fechaDesde." 00:00:00", "hasta" => $fechaHasta." 23:59:59"];

$total = $pdo->prepare("SELECT COUNT(*) as c FROM citas WHERE inicio BETWEEN :desde AND :hasta");
$total->execute($params);
$totalCitas = (int)$total->fetch()["c"];

$porEstado = $pdo->prepare("SELECT estado, COUNT(*) as c FROM citas WHERE inicio BETWEEN :desde AND :hasta GROUP BY estado");
$porEstado->execute($params);
$estadosCount = [];
foreach ($porEstado->fetchAll() as $r) {
    $estadosCount[$r["estado"]] = (int)$r["c"];
}

$porMedico = $pdo->prepare("SELECT m.id, m.apellidos, m.nombres, COUNT(*) as c FROM citas c JOIN medicos m ON m.id = c.medico_id WHERE c.inicio BETWEEN :desde AND :hasta GROUP BY m.id, m.apellidos, m.nombres ORDER BY c DESC, m.apellidos");
$porMedico->execute($params);
$medicosCount = $porMedico->fetchAll();

$porEspecialidad = $pdo->prepare("SELECT e.id, e.nombre, COUNT(*) as c FROM citas c JOIN especialidades e ON e.id = c.especialidad_id WHERE c.inicio BETWEEN :desde AND :hasta GROUP BY e.id, e.nombre ORDER BY c DESC");
$porEspecialidad->execute($params);
$espCount = $porEspecialidad->fetchAll();

renderSidebar("reportes");
?>
<div style="max-width: 1400px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Reportes</h1>
            <p style="color: var(--color-text-light);">Estadísticas con datos reales de PostgreSQL</p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="padding: 20px;">
            <form method="GET" action="reportes.php" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-input" value="<?= htmlspecialchars($fechaDesde) ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" class="form-input" value="<?= htmlspecialchars($fechaHasta) ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">Aplicar filtro</button>
            </form>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card">
            <div class="card-body" style="padding: 20px;">
                <div style="color: var(--color-text-light); font-size: 13px; margin-bottom: 8px;">Total de citas</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--color-primary);"><?= $totalCitas ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding: 20px;">
                <div style="color: var(--color-text-light); font-size: 13px; margin-bottom: 8px;">Programadas/Solicitadas</div>
                <div style="font-size: 32px; font-weight: 700;"><?= (int)($estadosCount["solicitada"] ?? 0) ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding: 20px;">
                <div style="color: var(--color-text-light); font-size: 13px; margin-bottom: 8px;">Confirmadas</div>
                <div style="font-size: 32px; font-weight: 700; color: #059669;"><?= (int)($estadosCount["confirmada"] ?? 0) ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding: 20px;">
                <div style="color: var(--color-text-light); font-size: 13px; margin-bottom: 8px;">Atendidas</div>
                <div style="font-size: 32px; font-weight: 700; color: #0284C7;"><?= (int)($estadosCount["completada"] ?? 0) ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding: 20px;">
                <div style="color: var(--color-text-light); font-size: 13px; margin-bottom: 8px;">Canceladas</div>
                <div style="font-size: 32px; font-weight: 700; color: #DC2626;"><?= (int)($estadosCount["cancelada"] ?? 0) ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="padding: 20px;">
                <div style="color: var(--color-text-light); font-size: 13px; margin-bottom: 8px;">Inasistencias</div>
                <div style="font-size: 32px; font-weight: 700; color: #D97706;"><?= (int)($estadosCount["no_asistio"] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Citas por Médico</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Médico</th>
                            <th style="text-align: right;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicosCount as $m): ?>
                        <tr>
                            <td>Dr(a). <?= htmlspecialchars($m["apellidos"].", ".$m["nombres"]) ?></td>
                            <td style="text-align: right; font-weight: 600;"><?= (int)$m["c"] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Citas por Especialidad</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Especialidad</th>
                            <th style="text-align: right;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($espCount as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e["nombre"]) ?></td>
                            <td style="text-align: right; font-weight: 600;"><?= (int)$e["c"] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php renderFooter(); ?>

