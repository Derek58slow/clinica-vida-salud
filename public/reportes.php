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
require_once __DIR__ . "/includes/panel_helpers.php";
require_once __DIR__ . "/../src/Services/reportes.php";

$pdo = conectarBD();
$filtros = filtrosReporte($_GET);
$catalogos = obtenerCatalogosReporte($pdo);
$reporte = obtenerReporteCitas($pdo, $filtros);
$fechaDesde = $filtros['desde'];
$fechaHasta = $filtros['hasta'];
$medicoFiltro = $filtros['medico_id'];
$especialidadFiltro = $filtros['especialidad_id'];
$estadoFiltro = $filtros['estado'];
$medicosFiltro = $catalogos['medicos'];
$especialidadesFiltro = $catalogos['especialidades'];
$totalCitas = $reporte['total'];
$estadosCount = $reporte['por_estado'];
$medicosCount = $reporte['por_medico'];
$espCount = $reporte['por_especialidad'];
$pacientesRegistrados = $reporte['pacientes_registrados'];
$detalleCitas = $reporte['detalle'];
$queryExportacion = http_build_query(array_filter($filtros, static fn($valor) => $valor !== null && $valor !== ''));
$maxMedico = max(1, ...array_map(static fn($fila) => (int)$fila['cantidad'], $medicosCount));
$maxEspecialidad = max(1, ...array_map(static fn($fila) => (int)$fila['cantidad'], $espCount));

renderSidebar("reportes");
?>
<div style="max-width: 1400px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Reportes</h1>
            <p style="color: var(--color-text-light);">Estadísticas y exportaciones con datos reales de PostgreSQL</p>
        </div>
        <div class="report-export-actions">
            <a class="btn btn-secondary" target="_blank" href="exportar_reporte_pdf.php?<?= h($queryExportacion) ?>">Exportar PDF</a>
            <a class="btn btn-primary" href="exportar_reporte_csv.php?<?= h($queryExportacion) ?>">Exportar Excel/CSV</a>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="padding: 20px;">
            <form method="GET" action="reportes.php" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-input" value="<?= htmlspecialchars($fechaDesde) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;min-width:200px"><label class="form-label">Médico</label><select class="form-select" name="medico_id"><option value="">Todos</option><?php foreach($medicosFiltro as $m):?><option value="<?= (int)$m['id'] ?>" <?= $medicoFiltro==(int)$m['id']?'selected':'' ?>><?= htmlspecialchars($m['nombre']) ?></option><?php endforeach;?></select></div>
                <div class="form-group" style="margin-bottom:0;min-width:190px"><label class="form-label">Especialidad</label><select class="form-select" name="especialidad_id"><option value="">Todas</option><?php foreach($especialidadesFiltro as $e):?><option value="<?= (int)$e['id'] ?>" <?= $especialidadFiltro==(int)$e['id']?'selected':'' ?>><?= htmlspecialchars($e['nombre']) ?></option><?php endforeach;?></select></div>
                <div class="form-group" style="margin-bottom:0;min-width:170px"><label class="form-label">Estado</label><select class="form-select" name="estado"><option value="">Todos</option><?php foreach(['solicitada'=>'Solicitada','confirmada'=>'Confirmada','completada'=>'Atendida','cancelada'=>'Cancelada','no_asistio'=>'Inasistencia'] as $v=>$n):?><option value="<?= $v ?>" <?= $estadoFiltro===$v?'selected':'' ?>><?= $n ?></option><?php endforeach;?></select></div>
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
        <div class="card"><div class="card-body" style="padding:20px"><div style="color:var(--color-text-light);font-size:13px;margin-bottom:8px">Pacientes registrados</div><div style="font-size:32px;font-weight:700;color:#7C5CBF"><?= $pacientesRegistrados ?></div></div></div>
        <div class="card"><div class="card-body" style="padding:20px"><div style="color:var(--color-text-light);font-size:13px;margin-bottom:8px">Total cobrado</div><div style="font-size:32px;font-weight:700;color:#16845B">Bs <?= number_format((float)$reporte['pagos']['cobrado'],2) ?></div></div></div>
        <div class="card"><div class="card-body" style="padding:20px"><div style="color:var(--color-text-light);font-size:13px;margin-bottom:8px">Citas sin pago</div><div style="font-size:32px;font-weight:700;color:#D97706"><?= (int)$reporte['pagos']['sin_pago'] ?></div></div></div>
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
                <div class="report-bars" aria-label="Gráfico de citas por médico">
                    <?php foreach ($medicosCount as $m): $porcentaje=(int)round(((int)$m['cantidad']/$maxMedico)*100); ?>
                        <div class="report-bar-row"><span><?= h($m['apellidos'].', '.$m['nombres']) ?></span><div><i style="width:<?= $porcentaje ?>%"></i></div><strong><?= (int)$m['cantidad'] ?></strong></div>
                    <?php endforeach; ?>
                </div>
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
                            <td style="text-align: right; font-weight: 600;"><?= (int)$m["cantidad"] ?></td>
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
                <div class="report-bars report-bars-specialty" aria-label="Gráfico de citas por especialidad">
                    <?php foreach ($espCount as $e): $porcentaje=(int)round(((int)$e['cantidad']/$maxEspecialidad)*100); ?>
                        <div class="report-bar-row"><span><?= h($e['nombre']) ?></span><div><i style="width:<?= $porcentaje ?>%"></i></div><strong><?= (int)$e['cantidad'] ?></strong></div>
                    <?php endforeach; ?>
                </div>
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
                            <td style="text-align: right; font-weight: 600;"><?= (int)$e["cantidad"] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <section class="card report-detail-card" style="margin-top:24px">
        <div class="card-header">
            <div><h2 class="card-title">Detalle de citas</h2><p class="card-subtitle"><?= count($detalleCitas) ?> resultado(s) para los filtros seleccionados</p></div>
            <div class="report-export-actions">
                <a class="btn btn-sm btn-secondary" target="_blank" href="exportar_reporte_pdf.php?<?= h($queryExportacion) ?>">PDF</a>
                <a class="btn btn-sm btn-primary" href="exportar_reporte_csv.php?<?= h($queryExportacion) ?>">Excel/CSV</a>
            </div>
        </div>
        <div class="card-body flush">
            <?php if (!$detalleCitas): ?>
                <div class="empty-state">No existen citas para los filtros seleccionados.</div>
            <?php else: ?>
                <div class="table-container"><table class="table report-detail-table"><thead><tr><th>Fecha</th><th>Código</th><th>Paciente</th><th>Médico</th><th>Especialidad</th><th>Estado</th><th>Atención</th><th>Pago</th></tr></thead><tbody>
                <?php foreach ($detalleCitas as $cita): ?>
                    <tr>
                        <td><strong><?= date('d/m/Y',strtotime($cita['inicio'])) ?></strong><small class="table-secondary-text"><?= date('H:i',strtotime($cita['inicio'])) ?> - <?= date('H:i',strtotime($cita['fin'])) ?></small></td>
                        <td><?= h($cita['codigo_cita']) ?></td>
                        <td><?= h($cita['paciente']) ?></td>
                        <td>Dr(a). <?= h($cita['medico']) ?></td>
                        <td><?= h($cita['especialidad']) ?></td>
                        <td><span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= h(nombreEstado($cita['estado'])) ?></span></td>
                        <td><?= h(ucfirst($cita['modalidad'])) ?><small class="table-secondary-text"><?= h($cita['consultorio'] ?: 'Por confirmar') ?></small></td>
                        <td><?php if ($cita['estado_pago']==='pagado'): ?><strong>Bs <?= number_format((float)$cita['monto'],2) ?></strong><small class="table-secondary-text"><?= h(ucfirst($cita['metodo_pago'])) ?></small><?php elseif ($cita['estado_pago']==='anulado'): ?><span class="badge badge-danger">Anulado</span><?php else: ?><span class="badge badge-warning">Pendiente</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php renderFooter(); ?>

