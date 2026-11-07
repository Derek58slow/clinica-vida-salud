<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([1,2,3,4]);
require_once __DIR__.'/../config/database.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$notificaciones=[]; $error=false; $total=0; $sinLeer=0;
$pagina=max(1,(int)($_GET['pagina']??1));
$filtro=($_GET['filtro']??'todas')==='sin_leer'?'sin_leer':'todas';
try {
    $pdo=conectarBD();
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $token=$_POST['csrf_token']??'';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'],$token)) { http_response_code(403); exit('Solicitud no válida.'); }
        $id=filter_var($_POST['notificacion_id']??null,FILTER_VALIDATE_INT);
        if($id){$stmt=$pdo->prepare('UPDATE notificaciones SET leida_at=now() WHERE id=:id AND usuario_id=:u');$stmt->execute(['id'=>$id,'u'=>$_SESSION['usuario_id']]);}
        else{$stmt=$pdo->prepare('UPDATE notificaciones SET leida_at=now() WHERE usuario_id=:u AND leida_at IS NULL');$stmt->execute(['u'=>$_SESSION['usuario_id']]);}
        header('Location: notificaciones.php?leidas=1&filtro='.urlencode($filtro)); exit;
    }
    $stmt=$pdo->prepare('SELECT COUNT(*) FILTER(WHERE leida_at IS NULL) sin_leer,COUNT(*) total FROM notificaciones WHERE usuario_id=:u');$stmt->execute(['u'=>$_SESSION['usuario_id']]);$conteos=$stmt->fetch();$sinLeer=(int)$conteos['sin_leer'];$total=$filtro==='sin_leer'?$sinLeer:(int)$conteos['total'];
    $pagina=min($pagina,max(1,(int)ceil($total/20))); $offset=($pagina-1)*20;
    $condicion=$filtro==='sin_leer'?' AND n.leida_at IS NULL':'';
    $stmt=$pdo->prepare("SELECT n.*,c.codigo_cita,c.estado FROM notificaciones n LEFT JOIN citas c ON c.id=n.cita_id WHERE n.usuario_id=:u$condicion ORDER BY n.created_at DESC,n.id DESC LIMIT 20 OFFSET $offset");
    $stmt->execute(['u'=>$_SESSION['usuario_id']]); $notificaciones=$stmt->fetchAll();
} catch(Throwable $e) { error_log($e->getMessage()); $error=true; }
require_once __DIR__.'/includes/layout.php'; renderSidebar('notificaciones');
?>
<div class="doctor-page-heading"><div class="section-title"><span class="page-emblem gold"><?= icono('campana') ?></span><div><span class="doctor-eyebrow">Mantente al día</span><h1>Notificaciones</h1><p>Confirmaciones, reprogramaciones y cancelaciones de tus citas.</p></div></div><?php if($sinLeer && !$error): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><button class="btn btn-secondary" type="submit"><?= icono('atendida') ?> Marcar todas como leídas</button></form><?php endif; ?></div>
<?php if($error): ?><div class="alert alert-error">No se pudieron cargar los avisos. Inténtalo nuevamente.</div>
<?php else: ?>
<?php if(isset($_GET['leidas'])): ?><div class="alert alert-success" role="status">Tus notificaciones están marcadas como leídas.</div><?php endif; ?>
<div class="filter-pills notification-filters"><a class="<?= $filtro==='todas'?'active':'' ?>" href="?filtro=todas">Todas</a><a class="<?= $filtro==='sin_leer'?'active':'' ?>" href="?filtro=sin_leer">Sin leer <span><?= $sinLeer ?></span></a></div>
<section class="card"><div class="card-body notification-list">
    <?php if(!$notificaciones): ?><div class="empty-state"><span class="page-emblem gold"><?= icono('campana') ?></span><h2 class="empty-state-title">Todo está al día</h2><p>Los cambios en tus citas aparecerán aquí.</p></div><?php else: foreach($notificaciones as $aviso): ?>
    <?php $tipo=str_contains(mb_strtolower($aviso['mensaje']),'reprogram')?'lilac':(($aviso['estado']??'')==='cancelada'?'coral':(($aviso['estado']??'')==='confirmada'?'green':'blue')); ?>
    <article class="notification-item <?= $aviso['leida_at']?'':'is-unread' ?>"><span class="section-icon <?= $tipo ?>"><?= icono('campana') ?></span><div><div class="notification-meta"><time><?= date('d/m/Y H:i',strtotime($aviso['created_at'])) ?></time><?php if(!$aviso['leida_at']): ?><span class="badge badge-info">Nueva</span><?php endif; ?></div><p><?= h($aviso['mensaje']) ?></p><div class="notification-actions"><?php if($aviso['cita_id']): ?><a class="doctor-patient-link" href="ver_cita.php?id=<?= (int)$aviso['cita_id'] ?>">Ver cita <?= h($aviso['codigo_cita']) ?> →</a><?php endif; ?><?php if(!$aviso['leida_at']): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="notificacion_id" value="<?= (int)$aviso['id'] ?>"><button type="submit">Marcar como leída</button></form><?php endif; ?></div></div></article>
    <?php endforeach; endif; ?>
</div></section>
<?php if($total>20): ?><nav class="pagination" aria-label="Páginas de notificaciones"><?php if($pagina>1): ?><a class="btn btn-secondary" href="?pagina=<?= $pagina-1 ?>">← Anterior</a><?php endif; ?><span>Página <?= $pagina ?> de <?= (int)ceil($total/20) ?></span><?php if($pagina*20<$total): ?><a class="btn btn-secondary" href="?pagina=<?= $pagina+1 ?>">Siguiente →</a><?php endif; ?></nav><?php endif; ?>
<?php endif; renderFooter(); ?>
