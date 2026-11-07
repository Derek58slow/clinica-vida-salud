<?php
require_once __DIR__.'/includes/panel_helpers.php'; exigirRol([1]);
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: usuarios_moderno.php');exit;}
$tokenCsrf=$_POST['csrf_token']??'';
if(!is_string($tokenCsrf)||empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],$tokenCsrf)){http_response_code(403);exit('Solicitud no válida.');}
require_once __DIR__.'/../config/database.php';
$nombre=trim($_POST['nombre']??'');$usuario=trim($_POST['nombre_usuario']??'');$correo=trim($_POST['correo']??'');
$rolId=filter_var($_POST['rol_id']??null,FILTER_VALIDATE_INT);$medicoId=filter_var($_POST['medico_id']??null,FILTER_VALIDATE_INT);$pacienteId=filter_var($_POST['paciente_id']??null,FILTER_VALIDATE_INT);
if($nombre===''||!$rolId||!preg_match('/^[A-Za-z0-9._-]{3,80}$/',$usuario)||($correo!==''&&!filter_var($correo,FILTER_VALIDATE_EMAIL))){header('Location: usuarios_moderno.php?error=datos#nueva-invitacion');exit;}
if(($rolId===3&&!$medicoId)||($rolId===4&&!$pacienteId)){header('Location: usuarios_moderno.php?error=vinculo#nueva-invitacion');exit;}
try{
    $pdo=conectarBD();$pdo->beginTransaction();
    $rol=$pdo->prepare('SELECT nombre FROM roles WHERE id=:id');$rol->execute(['id'=>$rolId]);$rolNombre=$rol->fetchColumn();if(!$rolNombre)throw new DomainException('rol');
    $hashTemporal=password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT);
    $stmt=$pdo->prepare('INSERT INTO usuarios(rol_id,nombre_usuario,nombre,correo,contrasena_hash,activo) VALUES(:rol,:usuario,:nombre,:correo,:hash,FALSE) RETURNING id');
    $stmt->execute(['rol'=>$rolId,'usuario'=>$usuario,'nombre'=>$nombre,'correo'=>$correo!==''?$correo:null,'hash'=>$hashTemporal]);$usuarioId=(int)$stmt->fetchColumn();
    if($rolId===3){$v=$pdo->prepare('UPDATE medicos SET usuario_id=:u WHERE id=:id AND usuario_id IS NULL AND activo=TRUE RETURNING id');$v->execute(['u'=>$usuarioId,'id'=>$medicoId]);if(!$v->fetchColumn())throw new DomainException('vinculo');}
    if($rolId===4){$v=$pdo->prepare('UPDATE pacientes SET usuario_id=:u WHERE id=:id AND usuario_id IS NULL AND activo=TRUE RETURNING id');$v->execute(['u'=>$usuarioId,'id'=>$pacienteId]);if(!$v->fetchColumn())throw new DomainException('vinculo');}
    $token=bin2hex(random_bytes(32));$tokenHash=hash('sha256',$token);
    $inv=$pdo->prepare("INSERT INTO invitaciones_usuario(usuario_id,token_hash,expira_at,creado_por) VALUES(:u,:hash,now()+interval '72 hours',:creador)");$inv->execute(['u'=>$usuarioId,'hash'=>$tokenHash,'creador'=>$_SESSION['usuario_id']]);
    $pdo->commit();$_SESSION['csrf_token']=bin2hex(random_bytes(32));
    $https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';$base=($https?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost').rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');
    $_SESSION['invitacion_link']=$base.'/activar_cuenta.php?token='.urlencode($token);
    header('Location: usuarios_moderno.php?creada=1');exit;
}catch(DomainException $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();header('Location: usuarios_moderno.php?error='.$e->getMessage().'#nueva-invitacion');exit;}
catch(PDOException $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log($e->getMessage());$codigo=$e->getCode()==='23505'?'duplicado':'servidor';header('Location: usuarios_moderno.php?error='.$codigo.'#nueva-invitacion');exit;}
catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log($e->getMessage());header('Location: usuarios_moderno.php?error=servidor#nueva-invitacion');exit;}
