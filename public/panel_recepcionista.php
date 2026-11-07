<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([2]);
require_once __DIR__.'/../config/database.php';
$stats=['pacientes'=>0,'hoy'=>0,'solicitadas'=>0,'confirmadas'=>0]; $citas=[]; $error=false;
try {
    $pdo=conectarBD();
    $stats['pacientes']=(int)$pdo->query('SELECT COUNT(*) FROM pacientes WHERE activo=TRUE')->fetchColumn();
    $stats['hoy']=(int)$pdo->query('SELECT COUNT(*) FROM citas WHERE inicio::date=CURRENT_DATE')->fetchColumn();
    $stats['solicitadas']=(int)$pdo->query("SELECT COUNT(*) FROM citas WHERE estado='solicitada' AND inicio>=CURRENT_TIMESTAMP")->fetchColumn();
    $stats['confirmadas']=(int)$pdo->query("SELECT COUNT(*) FROM citas WHERE estado='confirmada' AND inicio>=CURRENT_TIMESTAMP")->fetchColumn();
    $citas=$pdo->query("SELECT c.id,c.inicio,c.estado,p.nombres||' '||p.apellidos paciente,m.nombres||' '||m.apellidos medico FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN medicos m ON m.id=c.medico_id WHERE c.inicio::date=CURRENT_DATE ORDER BY c.inicio LIMIT 8")->fetchAll();
} catch(Throwable $e) { error_log($e->getMessage()); $error=true; }
require_once __DIR__.'/includes/layout.php'; renderSidebar('panel');
?>
<div class="doctor-page-heading"><div><span class="doctor-eyebrow">Organización y seguimiento</span><h1>Panel de recepción</h1><p>Pacientes, reservas y citas del día.</p></div><a class="btn btn-primary" href="nueva_cita.php"><?= icono('agenda') ?> Nueva cita</a></div>
<?php if($error): ?><div class="alert alert-error">No se pudo cargar el panel. Inténtalo nuevamente.</div><?php else: ?>
<div class="stats-grid"><?php foreach([['Pacientes activos','pacientes','blue','pacientes'],['Citas de hoy','hoy','orange','agenda'],['Por confirmar','solicitadas','purple','reloj'],['Confirmadas','confirmadas','green','atendida']] as [$titulo,$clave,$color,$icono]): ?><article class="stat-card"><span class="stat-icon <?= $color ?> illustrated-icon"><?= icono($icono) ?></span><div class="stat-info"><p class="stat-label"><?= $titulo ?></p><strong class="stat-value"><?= number_format($stats[$clave]) ?></strong></div></article><?php endforeach; ?></div>
<div class="reception-grid"><section class="card"><div class="card-header"><h2 class="card-title">Citas de hoy</h2><a class="btn btn-sm btn-secondary" href="agenda.php">Abrir agenda</a></div><div class="card-body flush"><?php if(!$citas): ?><div class="empty-state">No hay citas para hoy.</div><?php else: ?><div class="table-container"><table class="table"><thead><tr><th>Hora</th><th>Paciente</th><th>Médico</th><th>Estado</th><th>Detalle</th></tr></thead><tbody><?php foreach($citas as $cita): ?><tr><td><?= date('H:i',strtotime($cita['inicio'])) ?></td><td><?= h($cita['paciente']) ?></td><td>Dr(a). <?= h($cita['medico']) ?></td><td><span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= nombreEstado($cita['estado']) ?></span></td><td><a class="btn btn-sm btn-secondary" href="ver_cita.php?id=<?= (int)$cita['id'] ?>">Ver cita</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></section><aside class="card"><div class="card-header"><h2 class="card-title">Acciones rápidas</h2></div><div class="card-body quick-actions"><a class="btn btn-primary" href="nueva_cita.php"><?= icono('agenda') ?> Nueva cita</a><a class="btn btn-secondary" href="pagos.php"><?= icono('atendida') ?> Pagos y caja</a><a class="btn btn-secondary" href="registrar_paciente_moderno.php"><?= icono('pacientes') ?> Nuevo paciente</a><a class="btn btn-secondary" href="pacientes_moderno.php"><?= icono('buscar') ?> Buscar paciente</a><a class="btn btn-secondary" href="citas_moderno.php"><?= icono('atendida') ?> Gestionar citas</a></div></aside></div>
<?php endif; renderFooter(); ?>
