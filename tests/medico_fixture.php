<?php
// Solo crea o elimina un esquema aislado de pruebas; nunca utiliza tablas de public.
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
$esquema=$argv[2]??'';
if (!preg_match('/^test_medico_[a-f0-9]{16}$/D',$esquema)) throw new RuntimeException('Esquema de pruebas no válido.');
$pdo=conectarBD();
if (($argv[1]??'')==='cleanup') { $pdo->exec('DROP SCHEMA IF EXISTS '.$esquema.' CASCADE'); exit; }
if (($argv[1]??'')==='verify') {
    $pdo->exec('SET search_path TO '.$esquema.',public');
    $verificar=static function(bool $condicion,string $mensaje): void { if(!$condicion) throw new RuntimeException($mensaje); };
    $verificar($pdo->query("SELECT estado FROM citas WHERE codigo_cita='TEST-FUTURA'")->fetchColumn()==='confirmada','La cita futura no debe cambiar.');
    $verificar($pdo->query("SELECT estado FROM citas WHERE codigo_cita='TEST-AJENA'")->fetchColumn()==='confirmada','La cita ajena no debe cambiar.');
    $verificar($pdo->query("SELECT estado FROM citas WHERE codigo_cita='TEST-PROPIA'")->fetchColumn()==='completada','La cita propia debe completarse.');
    $verificar($pdo->query("SELECT u.nombre_usuario FROM historial_citas h JOIN usuarios u ON u.id=h.usuario_id JOIN citas c ON c.id=h.cita_id WHERE c.codigo_cita='TEST-PROPIA' AND h.estado_nuevo='completada' ORDER BY h.id DESC LIMIT 1")->fetchColumn()==='medico','El historial debe registrar al médico como actor.');
    $verificar($pdo->query("SELECT u.nombre_usuario FROM citas c JOIN usuarios u ON u.id=c.registrado_por WHERE c.codigo_cita='TEST-PROPIA'")->fetchColumn()==='recepcion','Debe conservarse el creador original.');
    $verificar((int)$pdo->query('SELECT COUNT(*) FROM historial_informacion_medica')->fetchColumn()===1,'Debe existir un único cambio clínico autorizado.');
    $verificar((int)$pdo->query('SELECT COUNT(*) FROM notificaciones')->fetchColumn()>=8,'Deben generarse avisos al crear y atender citas.');
    $verificar((int)$pdo->query("SELECT COUNT(*) FROM notificaciones n JOIN usuarios u ON u.id=n.usuario_id WHERE u.nombre_usuario='otro_medico' AND n.leida_at IS NOT NULL")->fetchColumn()===0,'Marcar leídas no debe afectar avisos ajenos.');
    $verificar((int)$pdo->query('SELECT COUNT(*) FROM citas')->fetchColumn()===3,'Las reservas inválidas no deben crear citas.');
    $pdo->beginTransaction();
    try {
        $pdo->exec("UPDATE horarios_atencion h SET activo=FALSE WHERE h.medico_id=(SELECT medico_id FROM citas WHERE codigo_cita='TEST-FUTURA') AND h.dia_semana=(SELECT EXTRACT(ISODOW FROM inicio) FROM citas WHERE codigo_cita='TEST-FUTURA')");
        throw new RuntimeException('Se permitió deshabilitar un horario con citas.');
    } catch(PDOException $e) {
        $verificar($e->getCode()==='PVS01','Debe impedir modificar horarios con citas activas.');
    } finally { $pdo->rollBack(); }
    echo 'Integridad, auditoría y notificaciones: OK'; exit;
}
if (($argv[1]??'')!=='setup') throw new RuntimeException('Acción no válida.');
$pdo->exec('CREATE SCHEMA '.$esquema);
$pdo->exec('SET search_path TO '.$esquema.',public');
$pdo->exec(file_get_contents(__DIR__.'/../database/migrations/clinica_vida_salud_completa.sql'));
$pdo->exec(file_get_contents(__DIR__.'/../database/migrations/20261030_invitaciones_usuario.sql'));
$pdo->exec(file_get_contents(__DIR__.'/../database/migrations/20261101_flujo_medico.sql'));
$pdo->exec(file_get_contents(__DIR__.'/../database/migrations/20261103_pagos_recepcion.sql'));
$usuarios=[];
foreach (['admin'=>1,'recepcion'=>2,'medico'=>3,'otro_medico'=>3,'sin_perfil'=>3,'inactivo'=>3,'paciente'=>4,'otro_paciente'=>4] as $usuario=>$rol) {
    $stmt=$pdo->prepare('INSERT INTO usuarios(rol_id,nombre_usuario,nombre,contrasena_hash) VALUES(:r,:u,:n,:h) RETURNING id');
    $stmt->execute(['r'=>$rol,'u'=>$usuario,'n'=>'Prueba '.$usuario,'h'=>password_hash('PruebaMedico2026!',PASSWORD_DEFAULT)]);
    $usuarios[$usuario]=(int)$stmt->fetchColumn();
}
$pdo->exec("INSERT INTO especialidades(nombre) VALUES('Medicina de prueba')");
$medicos=[];
foreach (['medico','otro_medico','inactivo'] as $nombre) {
    $stmt=$pdo->prepare("INSERT INTO medicos(usuario_id,nombres,apellidos,numero_licencia,consultorio,activo) VALUES(:u,:n,'Profesional','LIC-'||:lic,'Consultorio 1',:a) RETURNING id");
    $stmt->execute(['u'=>$usuarios[$nombre],'n'=>$nombre,'lic'=>$nombre,'a'=>$nombre==='inactivo'?'false':'true']); $medicos[$nombre]=(int)$stmt->fetchColumn();
    $pdo->exec('INSERT INTO medicos_especialidades VALUES('.$medicos[$nombre].',1)');
    for($dia=1;$dia<=7;$dia++) $pdo->exec("INSERT INTO horarios_atencion(medico_id,dia_semana,hora_inicio,hora_fin,intervalo_minutos) VALUES({$medicos[$nombre]},$dia,'00:00','23:59',15)");
}
$pacientes=[];
foreach (['paciente','otro_paciente'] as $nombre) {
    $stmt=$pdo->prepare("INSERT INTO pacientes(usuario_id,nombres,apellidos,documento_identidad) VALUES(:u,:n,'Paciente','DOC-'||:d) RETURNING id");
    $stmt->execute(['u'=>$usuarios[$nombre],'n'=>$nombre,'d'=>$nombre]); $pacientes[$nombre]=(int)$stmt->fetchColumn();
}
$citas=[];
foreach (['propia'=>[$medicos['medico'],$pacientes['paciente'],'-1 day','09:00'], 'ajena'=>[$medicos['otro_medico'],$pacientes['otro_paciente'],'-1 day','10:00'], 'futura'=>[$medicos['medico'],$pacientes['paciente'],'+1 day','09:00']] as $nombre=>[$medico,$paciente,$desplazamiento,$hora]) {
    $inicio=new DateTimeImmutable(date('Y-m-d',strtotime($desplazamiento)).' '.$hora);
    $stmt=$pdo->prepare("INSERT INTO citas(codigo_cita,paciente_id,medico_id,especialidad_id,inicio,fin,estado,registrado_por,motivo) VALUES(:codigo,:p,:m,1,:inicio,:fin,'confirmada',:u,'Consulta de prueba') RETURNING id");
    $stmt->execute(['codigo'=>'TEST-'.strtoupper($nombre),'p'=>$paciente,'m'=>$medico,'inicio'=>$inicio->format('Y-m-d H:i:s'),'fin'=>$inicio->modify('+15 minutes')->format('Y-m-d H:i:s'),'u'=>$usuarios['recepcion']]); $citas[$nombre]=(int)$stmt->fetchColumn();
}
echo json_encode(['usuarios'=>$usuarios,'medicos'=>$medicos,'pacientes'=>$pacientes,'citas'=>$citas,'ayer'=>date('Y-m-d',strtotime('-1 day')),'manana'=>date('Y-m-d',strtotime('+1 day'))],JSON_THROW_ON_ERROR);
