<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([4]);
require_once __DIR__.'/../config/database.php';
$paciente=null; $proximas=[]; $historial=[]; $error=false;
$busqueda=trim((string)($_GET['q']??''));
$estado=(string)($_GET['estado']??'todos');
$estadosPermitidos=['todos','completada','no_asistio','cancelada'];
if(!in_array($estado,$estadosPermitidos,true))$estado='todos';
try{
    $pdo=conectarBD();
    $paciente=obtenerPacienteSesion($pdo);
    if($paciente){
        $base="SELECT c.id,c.inicio,c.fin,c.estado,c.modalidad,c.codigo_cita,c.motivo,m.nombres||' '||m.apellidos medico,m.consultorio,m.sede,e.nombre especialidad FROM citas c JOIN medicos m ON m.id=c.medico_id JOIN especialidades e ON e.id=c.especialidad_id";
        $s=$pdo->prepare($base." WHERE c.paciente_id=:p AND c.inicio>=CURRENT_TIMESTAMP AND c.estado IN ('solicitada','confirmada') ORDER BY c.inicio");
        $s->execute(['p'=>$paciente['id']]);$proximas=$s->fetchAll();
        $condiciones=['c.paciente_id=:p',"(c.inicio<CURRENT_TIMESTAMP OR c.estado IN ('completada','cancelada','no_asistio'))"];
        $params=['p'=>$paciente['id']];
        if($estado!=='todos'){ $condiciones[]='c.estado=:estado';$params['estado']=$estado; }
        if($busqueda!==''){ $condiciones[]="(LOWER(e.nombre) LIKE :q OR LOWER(m.nombres||' '||m.apellidos) LIKE :q OR LOWER(c.codigo_cita) LIKE :q OR TO_CHAR(c.inicio,'DD/MM/YYYY') LIKE :q)";$params['q']='%'.mb_strtolower($busqueda).'%'; }
        $s=$pdo->prepare($base.' WHERE '.implode(' AND ',$condiciones).' ORDER BY c.inicio DESC LIMIT 100');
        $s->execute($params);$historial=$s->fetchAll();
    }
}catch(Throwable $e){error_log($e->getMessage());$error=true;}
require_once __DIR__.'/includes/layout.php';renderSidebar('mis_citas');
?>
<div class="doctor-page-heading"><div class="section-title"><span class="page-emblem blue"><?= icono('agenda') ?></span><div><span class="doctor-eyebrow">Reservas y atenciones</span><h1>Mis citas</h1><p>Consulta próximas reservas y revisa el historial de tu atención.</p></div></div><a class="btn btn-primary" href="nueva_cita.php"><?= icono('agenda') ?> Solicitar nueva cita</a></div>
<?php if(isset($_GET['registrada'])):?><div class="alert alert-success" role="status">Tu solicitud fue registrada. La clínica podrá confirmarla desde recepción.</div><?php endif;?>
<?php if($error):?><div class="alert alert-error">No se pudieron cargar tus citas. Inténtalo nuevamente.</div>
<?php elseif(!$paciente):?><div class="alert alert-warning">Tu usuario no está vinculado con una ficha de paciente activa.</div>
<?php else:?>
<section class="patient-upcoming-section"><div class="patient-section-heading"><div><span class="doctor-eyebrow">Agenda personal</span><h2>Próximas citas</h2></div><span class="badge badge-info"><?= count($proximas) ?> activa(s)</span></div>
<?php if(!$proximas):?><div class="card"><div class="doctor-empty"><span><?= icono('agenda') ?></span><div><strong>No tienes citas próximas</strong><p>Solicita una cita y elige entre los horarios disponibles.</p><a class="btn btn-sm btn-primary" href="nueva_cita.php">Solicitar cita</a></div></div></div>
<?php else:?><div class="patient-appointment-list"><?php foreach($proximas as $indice=>$cita):?><article class="patient-appointment-card <?= $indice===0?'is-next':'' ?>"><header><span><?= $indice===0?'Próxima cita médica':'Cita vigente' ?></span><span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= nombreEstado($cita['estado']) ?></span></header><div class="patient-appointment-content"><div><span class="appointment-specialty"><?= h($cita['especialidad']) ?></span><h3>Dr(a). <?= h($cita['medico']) ?></h3></div><div class="appointment-facts"><div><?= icono('agenda') ?><span><small>Fecha</small><strong><?= date('d/m/Y',strtotime($cita['inicio'])) ?></strong></span></div><div><?= icono('reloj') ?><span><small>Hora</small><strong><?= date('H:i',strtotime($cita['inicio'])) ?></strong></span></div><div><?= icono('perfil') ?><span><small>Atención</small><strong><?= h($cita['consultorio']?:($cita['modalidad']==='virtual'?'Virtual':'Por confirmar')) ?></strong></span></div></div><div class="action-row"><a class="btn btn-primary" href="ver_cita.php?id=<?= (int)$cita['id'] ?>">Ver detalle</a><a class="btn btn-secondary" target="_blank" href="comprobante_cita.php?id=<?= (int)$cita['id'] ?>">Comprobante PDF</a><a class="btn btn-secondary" href="reprogramar_cita.php?id=<?= (int)$cita['id'] ?>">Reprogramar</a><a class="btn patient-cancel-button" href="cancelar_cita.php?id=<?= (int)$cita['id'] ?>">Cancelar</a></div></div></article><?php endforeach;?></div><?php endif;?></section>
<section class="patient-history-section"><div class="patient-section-heading"><div><span class="doctor-eyebrow">Registro de atención</span><h2>Historial</h2></div></div>
<form class="patient-history-filters" method="GET"><div class="search-input-wrapper"><?= icono('buscar') ?><input class="form-input" type="search" name="q" value="<?= h($busqueda) ?>" placeholder="Buscar médico, especialidad, fecha o código"></div><div class="filter-pills"><?php foreach(['todos'=>'Todos','completada'=>'Completadas','no_asistio'=>'Inasistencias','cancelada'=>'Canceladas'] as $valor=>$nombre):?><button class="<?= $estado===$valor?'active':'' ?>" type="submit" name="estado" value="<?= $valor ?>"><?= $nombre ?></button><?php endforeach;?></div></form>
<?php if(!$historial):?><div class="card"><div class="empty-state">No encontramos citas con esos filtros.</div></div><?php else:?><div class="patient-history-list"><?php foreach($historial as $cita):?><a href="ver_cita.php?id=<?= (int)$cita['id'] ?>"><span class="history-accent badge-<?= estadoBadge($cita['estado']) ?>"></span><span><strong><?= h($cita['especialidad']) ?></strong><small>Dr(a). <?= h($cita['medico']) ?></small><em><?= date('d/m/Y · H:i',strtotime($cita['inicio'])) ?> · <?= h(ucfirst($cita['modalidad'])) ?></em></span><span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= nombreEstado($cita['estado']) ?></span><?= icono('flecha') ?></a><?php endforeach;?></div><?php endif;?></section>
<?php endif;renderFooter();?>
