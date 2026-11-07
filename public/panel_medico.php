<?php
require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([3]);
require_once __DIR__ . '/../config/database.php';

$medico = null;
$stats = ['pendientes'=>0,'atendidas'=>0,'ausencias'=>0,'pacientes'=>0];
$citasHoy = $actividad = [];
$proxima = null;
$error = false;
try {
    $pdo = conectarBD();
    $medico = obtenerMedicoSesion($pdo);
    if ($medico) {
        $stmt = $pdo->prepare("SELECT
            COUNT(*) FILTER (WHERE inicio::date=CURRENT_DATE AND estado IN ('solicitada','confirmada')) pendientes,
            COUNT(*) FILTER (WHERE inicio::date=CURRENT_DATE AND estado='completada') atendidas,
            COUNT(*) FILTER (WHERE inicio::date=CURRENT_DATE AND estado='no_asistio') ausencias,
            COUNT(DISTINCT paciente_id) pacientes FROM citas WHERE medico_id=:medico");
        $stmt->execute(['medico'=>$medico['id']]);
        $stats = $stmt->fetch();
        $base = "SELECT c.id,c.inicio,c.fin,c.estado,c.modalidad,c.motivo,p.id paciente_id,
            p.nombres||' '||p.apellidos paciente,e.nombre especialidad
            FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN especialidades e ON e.id=c.especialidad_id";
        $stmt = $pdo->prepare($base . ' WHERE c.medico_id=:medico AND c.inicio::date=CURRENT_DATE ORDER BY c.inicio');
        $stmt->execute(['medico'=>$medico['id']]);
        $citasHoy = $stmt->fetchAll();
        $stmt = $pdo->prepare($base . " WHERE c.medico_id=:medico AND c.fin>CURRENT_TIMESTAMP AND c.estado IN ('solicitada','confirmada') ORDER BY c.inicio LIMIT 1");
        $stmt->execute(['medico'=>$medico['id']]);
        $proxima = $stmt->fetch() ?: null;
        $stmt = $pdo->prepare("SELECT h.created_at,h.accion,h.estado_nuevo,c.id,p.nombres||' '||p.apellidos paciente,u.nombre actor
            FROM historial_citas h JOIN citas c ON c.id=h.cita_id JOIN pacientes p ON p.id=c.paciente_id
            LEFT JOIN usuarios u ON u.id=h.usuario_id WHERE c.medico_id=:medico ORDER BY h.created_at DESC,h.id DESC LIMIT 5");
        $stmt->execute(['medico'=>$medico['id']]);
        $actividad = $stmt->fetchAll();
    }
} catch (Throwable $e) { error_log($e->getMessage()); $error=true; }
require_once __DIR__ . '/includes/layout.php';
renderSidebar('panel');
?>
<section class="doctor-welcome">
    <div class="doctor-welcome-identity"><span class="doctor-emblem"><?= icono('medico') ?></span><div>
        <span class="doctor-eyebrow">Tu espacio profesional · <?= date('d/m/Y') ?></span>
        <h1><?= $medico ? 'Hola, Dr(a). '.h($medico['nombres']) : 'Bienvenido a tu espacio médico' ?></h1>
        <p><?= $medico ? h($medico['especialidades'] ?: 'Profesional médico') . ($medico['consultorio'] ? ' · '.h($medico['consultorio']) : '') : 'Agenda, pacientes y disponibilidad en un solo lugar.' ?></p>
    </div></div>
    <?php if ($medico): ?><a class="btn btn-primary" href="agenda.php"><?= icono('agenda') ?> Abrir mi agenda</a><?php endif; ?>
</section>
<?php if ($error): ?>
    <div class="alert alert-error" role="alert">No se pudo cargar la información. <a href="panel_medico.php">Volver a intentar</a></div>
<?php elseif (!$medico): ?>
    <div class="alert alert-warning">Administración debe vincular tu cuenta con un perfil médico activo para mostrar tus citas.</div>
<?php else: ?>
<div class="stats-grid doctor-stats">
    <?php foreach ([['Pendientes de hoy','pendientes','orange','agenda','Solicitadas y confirmadas'],['Atendidas hoy','atendidas','green','atendida','Consultas completadas'],['Ausencias de hoy','ausencias','purple','ausencia','Pacientes que no asistieron'],['Pacientes relacionados','pacientes','blue','pacientes','Total de pacientes con citas']] as [$titulo,$clave,$color,$icono,$ayuda]): ?>
    <article class="stat-card"><span class="stat-icon <?= $color ?> illustrated-icon"><?= icono($icono) ?></span><div class="stat-info"><p class="stat-label"><?= $titulo ?></p><strong class="stat-value"><?= number_format((int)$stats[$clave]) ?></strong><p class="stat-caption"><?= $ayuda ?></p></div></article>
    <?php endforeach; ?>
</div>
<div class="doctor-dashboard-grid">
    <div class="stack">
        <section class="card next-appointment">
            <div class="card-header"><div class="section-title"><span class="section-icon blue"><?= icono('reloj') ?></span><div><h2 class="card-title">Tu próxima consulta</h2><p class="card-subtitle">La atención en curso o la siguiente en tu agenda</p></div></div></div>
            <div class="card-body">
            <?php if ($proxima): ?>
                <div class="next-appointment-summary"><span class="appointment-date"><strong><?= date('H:i',strtotime($proxima['inicio'])) ?></strong><small><?= date('d/m/Y',strtotime($proxima['inicio'])) ?></small></span><div><span class="badge badge-<?= estadoBadge($proxima['estado']) ?>"><?= nombreEstado($proxima['estado']) ?></span><h3><?= h($proxima['paciente']) ?></h3><p><?= h($proxima['especialidad']) ?> · <?= h(ucfirst($proxima['modalidad'])) ?></p></div></div>
                <p class="appointment-reason"><strong>Motivo:</strong> <?= h($proxima['motivo'] ?: 'Sin motivo registrado') ?></p>
                <div class="action-row"><a class="btn btn-primary" href="ver_cita.php?id=<?= (int)$proxima['id'] ?>&amp;fecha=<?= date('Y-m-d',strtotime($proxima['inicio'])) ?>">Ver cita <?= icono('flecha') ?></a><a class="btn btn-secondary" href="medico_paciente.php?id=<?= (int)$proxima['paciente_id'] ?>"><?= icono('pacientes') ?> Ficha del paciente</a></div>
            <?php else: ?><div class="doctor-empty"><span><?= icono('atendida') ?></span><div><strong>Tu agenda próxima está libre</strong><p>Las nuevas reservas aparecerán aquí cuando estén asignadas a tu perfil.</p></div></div><?php endif; ?>
            </div>
        </section>
        <section class="card">
            <div class="card-header"><div class="section-title"><span class="section-icon coral"><?= icono('agenda') ?></span><div><h2 class="card-title">Agenda de hoy</h2><p class="card-subtitle"><?= count($citasHoy) ?> cita(s) · Todos los estados</p></div></div><a class="btn btn-sm btn-secondary" href="agenda.php?fecha=<?= date('Y-m-d') ?>">Ver día completo</a></div>
            <div class="card-body doctor-timeline">
                <?php if (!$citasHoy): ?><div class="doctor-empty"><span><?= icono('agenda') ?></span><div><strong>Sin citas para hoy</strong><p>Puedes revisar otra fecha desde tu agenda.</p></div></div>
                <?php else: foreach ($citasHoy as $cita): ?>
                <article class="timeline-item <?= in_array($cita['estado'],['completada','cancelada','no_asistio'],true) ? 'is-finished' : '' ?>"><time><?= date('H:i',strtotime($cita['inicio'])) ?><small>hasta <?= date('H:i',strtotime($cita['fin'])) ?></small></time><span class="timeline-dot"></span><div><div class="timeline-title"><strong><?= h($cita['paciente']) ?></strong><span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= nombreEstado($cita['estado']) ?></span></div><p><?= h($cita['especialidad']) ?> · <?= h(ucfirst($cita['modalidad'])) ?></p><a href="ver_cita.php?id=<?= (int)$cita['id'] ?>&amp;fecha=<?= date('Y-m-d') ?>">Ver cita →</a></div></article>
                <?php endforeach; endif; ?>
            </div>
        </section>
    </div>
    <aside class="stack">
        <section class="card"><div class="card-header"><h2 class="card-title">Herramientas de tu consulta</h2></div><div class="card-body doctor-action-list">
        <?php foreach ([['agenda.php','agenda','blue','Mi agenda','Organiza las citas por fecha'],['medico_pacientes.php','pacientes','coral','Mis pacientes','Fichas y seguimiento de citas'],['medico_perfil.php','perfil','lilac','Mi perfil y horarios','Disponibilidad y datos profesionales'],['notificaciones.php','campana','gold','Notificaciones','Cambios y novedades de tus citas']] as [$url,$icono,$color,$titulo,$descripcion]): ?>
            <a href="<?= $url ?>"><span class="action-symbol <?= $color ?> illustrated-icon"><?= icono($icono) ?></span><span><strong><?= $titulo ?></strong><small><?= $descripcion ?></small></span><?= icono('flecha') ?></a>
        <?php endforeach; ?></div></section>
        <section class="card"><div class="card-header"><div class="section-title"><span class="section-icon lilac"><?= icono('historial') ?></span><h2 class="card-title">Actividad reciente</h2></div></div><div class="card-body activity-list">
            <?php if (!$actividad): ?><p class="muted">Aún no hay cambios registrados en tus citas.</p><?php else: foreach ($actividad as $evento): ?>
            <a href="ver_cita.php?id=<?= (int)$evento['id'] ?>"><strong><?= h($evento['paciente']) ?></strong><span><?= nombreEstado($evento['estado_nuevo']) ?> · <?= h($evento['actor'] ?: 'Autor no registrado') ?></span><time><?= date('d/m/Y H:i',strtotime($evento['created_at'])) ?></time></a>
            <?php endforeach; endif; ?>
        </div></section>
    </aside>
</div>
<?php endif; renderFooter(); ?>
