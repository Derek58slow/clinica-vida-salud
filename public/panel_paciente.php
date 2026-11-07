<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([4]);
require_once __DIR__.'/../config/database.php';
$paciente=null; $stats=['proximas'=>0,'realizadas'=>0,'canceladas'=>0]; $citas=[]; $error=false;
try {
    $pdo=conectarBD();
    $stmt=$pdo->prepare('SELECT id,nombres,apellidos FROM pacientes WHERE usuario_id=:u AND activo=TRUE');
    $stmt->execute(['u'=>$_SESSION['usuario_id']]); $paciente=$stmt->fetch();
    if($paciente) {
        $stmt=$pdo->prepare("SELECT COUNT(*) FILTER(WHERE estado IN ('solicitada','confirmada') AND inicio>=CURRENT_TIMESTAMP) proximas,COUNT(*) FILTER(WHERE estado='completada') realizadas,COUNT(*) FILTER(WHERE estado='cancelada') canceladas FROM citas WHERE paciente_id=:p");
        $stmt->execute(['p'=>$paciente['id']]); $stats=$stmt->fetch();
        $stmt=$pdo->prepare("SELECT c.id,c.inicio,c.estado,m.nombres||' '||m.apellidos medico,e.nombre especialidad FROM citas c JOIN medicos m ON m.id=c.medico_id JOIN especialidades e ON e.id=c.especialidad_id WHERE c.paciente_id=:p AND c.inicio>=CURRENT_TIMESTAMP AND c.estado IN ('solicitada','confirmada') ORDER BY c.inicio LIMIT 5");
        $stmt->execute(['p'=>$paciente['id']]); $citas=$stmt->fetchAll();
    }
} catch(Throwable $e) { error_log($e->getMessage()); $error=true; }
require_once __DIR__.'/includes/layout.php'; renderSidebar('panel');
?>
<div class="doctor-page-heading"><div><span class="doctor-eyebrow">Tu espacio personal</span><h1>Mi salud</h1><p><?= $paciente ? 'Bienvenido(a), '.h($paciente['nombres'].' '.$paciente['apellidos']) : 'Consulta tus citas y el seguimiento de tu atención.' ?></p></div><div class="action-row"><a class="btn btn-secondary" href="paciente_perfil.php"><?= icono('perfil') ?> Mi perfil</a><a class="btn btn-primary" href="nueva_cita.php"><?= icono('agenda') ?> Solicitar cita</a></div></div>
<?php if($paciente): ?><a class="patient-booking-cta" href="nueva_cita.php"><span class="action-symbol green"><?= icono('agenda') ?></span><span><strong>Nueva reserva</strong><small>Elige especialidad, médico, fecha y horario disponible</small></span><?= icono('flecha') ?></a><?php endif; ?>
<?php if($error): ?><div class="alert alert-error">No se pudo cargar tu información. Inténtalo nuevamente.</div>
<?php elseif(!$paciente): ?><div class="alert alert-warning">Administración debe vincular tu cuenta con una ficha de paciente activa para mostrar tus citas.</div>
<?php else: ?>
<div class="stats-grid"><?php foreach([['Próximas citas','proximas','purple','agenda'],['Atenciones realizadas','realizadas','green','atendida'],['Canceladas','canceladas','red','ausencia']] as [$titulo,$clave,$color,$icono]): ?><article class="stat-card"><span class="stat-icon <?= $color ?> illustrated-icon"><?= icono($icono) ?></span><div class="stat-info"><p class="stat-label"><?= $titulo ?></p><strong class="stat-value"><?= number_format((int)$stats[$clave]) ?></strong></div></article><?php endforeach; ?></div>
<section class="card"><div class="card-header"><h2 class="card-title">Mis próximas citas</h2><a class="btn btn-sm btn-secondary" href="mis_citas.php">Ver todas</a></div><div class="card-body flush"><?php if(!$citas): ?><div class="empty-state">No tienes citas próximas activas.</div><?php else: ?><div class="table-container"><table class="table"><thead><tr><th>Fecha</th><th>Médico</th><th>Especialidad</th><th>Estado</th><th>Detalle</th></tr></thead><tbody><?php foreach($citas as $cita): ?><tr><td><?= date('d/m/Y H:i',strtotime($cita['inicio'])) ?></td><td>Dr(a). <?= h($cita['medico']) ?></td><td><?= h($cita['especialidad']) ?></td><td><span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= nombreEstado($cita['estado']) ?></span></td><td><a class="btn btn-sm btn-secondary" href="ver_cita.php?id=<?= (int)$cita['id'] ?>">Ver cita</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></section>
<?php endif; renderFooter(); ?>
