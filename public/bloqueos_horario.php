<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([1]);
require_once __DIR__.'/../config/database.php';
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$medicos=$bloqueos=[]; $errorCarga=false;
try {
    $pdo=conectarBD();
    if($_SERVER['REQUEST_METHOD']==='POST') {
        $token=$_POST['csrf_token']??'';
        if(!is_string($token)||!hash_equals($_SESSION['csrf_token'],$token)) { http_response_code(403); exit('Solicitud no válida.'); }
        if(($_POST['accion']??'')==='eliminar') {
            $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
            if(!$id) { http_response_code(400); exit('Bloqueo no válido.'); }
            $stmt=$pdo->prepare('DELETE FROM bloqueos_horario WHERE id=:id AND inicio>CURRENT_TIMESTAMP');
            $stmt->execute(['id'=>$id]);
            header('Location: bloqueos_horario.php?eliminado=1'); exit;
        }
        $medicoId=filter_input(INPUT_POST,'medico_id',FILTER_VALIDATE_INT);
        $inicioTexto=is_string($_POST['inicio']??null)?str_replace('T',' ',$_POST['inicio']):'';
        $finTexto=is_string($_POST['fin']??null)?str_replace('T',' ',$_POST['fin']):'';
        $inicio=inicioCitaValido(substr($inicioTexto,0,10),substr($inicioTexto,11));
        $fin=inicioCitaValido(substr($finTexto,0,10),substr($finTexto,11));
        $motivo=is_string($_POST['motivo']??null)?trim($_POST['motivo']):'';
        if(!$medicoId || !$inicio || !$fin || $inicio<=new DateTimeImmutable() || $fin<=$inicio || $motivo==='' || mb_strlen($motivo)>250) {
            header('Location: bloqueos_horario.php?error=datos'); exit;
        }
        $stmt=$pdo->prepare('INSERT INTO bloqueos_horario(medico_id,inicio,fin,motivo) SELECT id,:inicio,:fin,:motivo FROM medicos WHERE id=:medico AND activo=TRUE');
        $stmt->execute(['medico'=>$medicoId,'inicio'=>$inicio->format('Y-m-d H:i:s'),'fin'=>$fin->format('Y-m-d H:i:s'),'motivo'=>$motivo]);
        if(!$stmt->rowCount()) { header('Location: bloqueos_horario.php?error=datos'); exit; }
        header('Location: bloqueos_horario.php?creado=1'); exit;
    }
    $medicos=$pdo->query('SELECT id,nombres,apellidos FROM medicos WHERE activo=TRUE ORDER BY apellidos,nombres')->fetchAll();
    $bloqueos=$pdo->query("SELECT b.*,m.nombres||' '||m.apellidos medico FROM bloqueos_horario b JOIN medicos m ON m.id=b.medico_id WHERE b.fin>=CURRENT_TIMESTAMP ORDER BY b.inicio LIMIT 100")->fetchAll();
} catch(Throwable $e) {
    error_log($e->getMessage());
    if($_SERVER['REQUEST_METHOD']==='POST') {
        $error=$e->getCode()==='23P01'?'superpuesto':($e->getCode()==='P0001'?'citas':'servidor');
        header('Location: bloqueos_horario.php?error='.$error); exit;
    }
    $errorCarga=true;
}
require_once __DIR__.'/includes/layout.php'; renderSidebar('horarios');
?>
<div class="doctor-page-heading"><div class="section-title"><span class="page-emblem coral"><?= icono('ausencia') ?></span><div><span class="doctor-eyebrow">Disponibilidad del equipo médico</span><h1>Ausencias y bloqueos</h1><p>Registra periodos en los que no se podrán reservar citas.</p></div></div><a class="btn btn-secondary" href="horarios_moderno.php"><?= icono('volver') ?> Horarios de atención</a></div>
<?php if($errorCarga): ?><div class="alert alert-error">No se pudo cargar la disponibilidad.</div><?php else: ?>
<?php if(isset($_GET['error'])): ?><div class="alert alert-error"><?= h(['datos'=>'Indica un médico activo, un motivo y un periodo futuro válido.','superpuesto'=>'Ese periodo se superpone con otro bloqueo.','citas'=>'Hay citas activas en ese periodo. Reprográmalas o cancélalas antes de bloquear la disponibilidad.','servidor'=>'No se pudo guardar el bloqueo.'][$_GET['error']]??'No se pudo procesar la solicitud.') ?></div><?php endif; ?>
<?php if(isset($_GET['creado'])): ?><div class="alert alert-success">Bloqueo registrado. El médico puede consultarlo en su perfil.</div><?php endif; ?>
<?php if(isset($_GET['eliminado'])): ?><div class="alert alert-success">Se procesó la eliminación del bloqueo futuro.</div><?php endif; ?>
<section class="card"><div class="card-header"><h2 class="card-title">Nuevo periodo sin disponibilidad</h2></div><div class="card-body"><form method="POST"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><div class="form-grid"><div class="form-group"><label class="form-label" for="bloqueo-medico">Médico</label><select class="form-select" name="medico_id" id="bloqueo-medico" required><option value="">Seleccionar profesional</option><?php foreach($medicos as $medico): ?><option value="<?= (int)$medico['id'] ?>"><?= h($medico['apellidos'].', '.$medico['nombres']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label class="form-label" for="bloqueo-motivo">Motivo</label><input class="form-input" id="bloqueo-motivo" name="motivo" maxlength="250" required placeholder="Ej.: ausencia programada"></div><div class="form-group"><label class="form-label" for="bloqueo-inicio">Desde</label><input class="form-input" type="datetime-local" id="bloqueo-inicio" name="inicio" min="<?= date('Y-m-d\TH:i') ?>" required></div><div class="form-group"><label class="form-label" for="bloqueo-fin">Hasta</label><input class="form-input" type="datetime-local" id="bloqueo-fin" name="fin" min="<?= date('Y-m-d\TH:i') ?>" required></div></div><button class="btn btn-primary" type="submit"><?= icono('ausencia') ?> Registrar bloqueo</button></form></div></section>
<section class="card"><div class="card-header"><h2 class="card-title">Próximos bloqueos</h2><p class="card-subtitle">Hasta 100 periodos próximos</p></div><div class="card-body flush"><?php if(!$bloqueos): ?><div class="empty-state">No hay bloqueos próximos registrados.</div><?php else: ?><div class="table-container"><table class="table"><thead><tr><th>Médico</th><th>Desde</th><th>Hasta</th><th>Motivo</th><th>Acción</th></tr></thead><tbody><?php foreach($bloqueos as $bloqueo): ?><tr><td><?= h($bloqueo['medico']) ?></td><td><?= date('d/m/Y H:i',strtotime($bloqueo['inicio'])) ?></td><td><?= date('d/m/Y H:i',strtotime($bloqueo['fin'])) ?></td><td><?= h($bloqueo['motivo']) ?></td><td><?php if(strtotime($bloqueo['inicio'])>time()): ?><form method="POST" data-confirm="¿Eliminar este bloqueo y volver a habilitar el periodo para reservas?"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$bloqueo['id'] ?>"><button class="btn btn-sm btn-secondary" type="submit">Eliminar</button></form><?php else: ?><span class="badge badge-secondary">En curso</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></section>
<?php endif; renderFooter(); ?>
