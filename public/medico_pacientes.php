<?php
require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([3]);
require_once __DIR__ . '/../config/database.php';

$buscar = trim($_GET['q'] ?? '');
$pacientes = [];
$medico = null;
$error = null;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 12;
$total = 0;
try {
    $pdo = conectarBD();
    $medico = obtenerMedicoSesion($pdo);
    if ($medico) {
        $sql = "SELECT p.id,p.nombres,p.apellidos,p.documento_identidad,p.telefono,p.correo,
                       COUNT(c.id) total_citas,
                        MAX(c.inicio) FILTER (WHERE c.inicio<CURRENT_TIMESTAMP) ultima_cita,
                       MIN(c.inicio) FILTER (WHERE c.inicio>=CURRENT_TIMESTAMP AND c.estado IN ('solicitada','confirmada')) proxima_cita,
                       COUNT(*) FILTER (WHERE c.estado='completada') atenciones
                FROM pacientes p JOIN citas c ON c.paciente_id=p.id
                WHERE c.medico_id=:medico";
        $params = ['medico' => $medico['id']];
        if ($buscar !== '') {
            $sql .= " AND (lower(p.nombres||' '||p.apellidos) LIKE lower(:buscar) OR lower(COALESCE(p.documento_identidad,'')) LIKE lower(:buscar))";
            $params['buscar'] = '%' . $buscar . '%';
        }
        $contador = $pdo->prepare('SELECT COUNT(*) FROM (' . $sql . ' GROUP BY p.id) relacionados');
        $contador->execute($params);
        $total = (int)$contador->fetchColumn();
        $pagina = min($pagina, max(1,(int)ceil($total/$porPagina)));
        $offset = ($pagina-1)*$porPagina;
        $sql .= " GROUP BY p.id ORDER BY MIN(c.inicio) FILTER (WHERE c.inicio>=CURRENT_TIMESTAMP AND c.estado IN ('solicitada','confirmada')) ASC NULLS LAST, p.apellidos,p.id LIMIT $porPagina OFFSET $offset";
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $pacientes = $stmt->fetchAll();
    }
} catch (Throwable $e) { error_log($e->getMessage()); $error = 'No se pudo cargar la lista de pacientes.'; }

require_once __DIR__ . '/includes/layout.php'; renderSidebar('medico_pacientes');
?>
<div class="doctor-page-heading"><div class="section-title"><span class="page-emblem coral"><?= icono('pacientes') ?></span><div><span class="doctor-eyebrow">Seguimiento profesional</span><h1>Mis pacientes</h1><p>Pacientes que tienen o tuvieron una cita contigo.</p></div></div><a class="btn btn-secondary" href="agenda.php"><?= icono('agenda') ?> Abrir mi agenda</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($error): ?><?php elseif (!$medico): ?><div class="alert alert-warning">Tu cuenta no está vinculada con un perfil médico activo.</div><?php else: ?>
<div class="card"><div class="card-body"><form class="search-bar" method="GET"><div class="search-input-wrapper"><label class="sr-only" for="buscar-paciente">Nombre o documento del paciente</label><?= icono('buscar','search-icon') ?><input id="buscar-paciente" class="search-input" name="q" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar por nombre o documento"></div><button class="btn btn-primary" type="submit">Buscar</button><?php if($buscar!==''): ?><a class="btn btn-secondary" href="medico_pacientes.php">Limpiar</a><?php endif; ?></form><p class="card-subtitle"><?= $total ?> paciente(s) relacionados · Ordenados por próxima cita</p></div></div>
<?php if (!$pacientes): ?><div class="card"><div class="empty-state"><div class="empty-state-title">No se encontraron pacientes</div><div class="empty-state-text"><?= $buscar!==''?'Prueba con otro nombre o documento.':'Los pacientes aparecerán cuando tengan una cita asignada contigo.' ?></div></div></div>
<?php else: ?><div class="doctor-patient-grid"><?php foreach($pacientes as $p): ?><article class="doctor-patient-card"><div class="patient-card-top"><span class="patient-avatar"><?= getUserInitials($p['nombres'].' '.$p['apellidos']) ?></span><span class="badge badge-info"><?= (int)$p['total_citas'] ?> cita(s)</span></div><h2><?= htmlspecialchars($p['nombres'].' '.$p['apellidos']) ?></h2><p><?= $p['documento_identidad']?'CI: '.htmlspecialchars($p['documento_identidad']):'Sin documento registrado' ?></p><dl><div><dt>Próxima cita</dt><dd><?= $p['proxima_cita']?date('d/m/Y H:i',strtotime($p['proxima_cita'])):'Sin cita próxima' ?></dd></div><div><dt>Atenciones realizadas</dt><dd><?= (int)$p['atenciones'] ?></dd></div></dl><a class="btn btn-secondary" href="medico_paciente.php?id=<?= (int)$p['id'] ?>">Ver ficha <?= icono('flecha') ?></a></article><?php endforeach; ?></div><?php endif; ?>
<?php if ($total > $porPagina): ?><nav class="pagination" aria-label="Páginas de pacientes"><?php if($pagina>1): ?><a class="btn btn-secondary" href="?<?= h(http_build_query(['q'=>$buscar,'pagina'=>$pagina-1])) ?>">← Anterior</a><?php endif; ?><span>Página <?= $pagina ?> de <?= (int)ceil($total/$porPagina) ?></span><?php if($pagina*$porPagina<$total): ?><a class="btn btn-secondary" href="?<?= h(http_build_query(['q'=>$buscar,'pagina'=>$pagina+1])) ?>">Siguiente →</a><?php endif; ?></nav><?php endif; ?>
<?php endif; renderFooter(); ?>
