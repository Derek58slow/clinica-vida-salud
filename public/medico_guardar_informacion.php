<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([3]);
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: POST'); exit; }
$token=$_POST['csrf_token']??'';
if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'],$token)) { http_response_code(403); exit('Solicitud no válida.'); }
$pacienteId=filter_input(INPUT_POST,'paciente_id',FILTER_VALIDATE_INT);
if (!$pacienteId) { http_response_code(400); exit('Paciente no válido.'); }
$datos=[];
foreach (['tipo_sangre','alergias','enfermedades_cronicas','medicamentos','antecedentes'] as $campo) {
    if (!is_string($_POST[$campo]??'')) { http_response_code(400); exit('Datos no válidos.'); }
    $valor=trim($_POST[$campo]??'');
    if (mb_strlen($valor)>4000) { header("Location: medico_paciente.php?id=$pacienteId&error=datos"); exit; }
    $datos[$campo]=$valor!==''?$valor:null;
}
if ($datos['tipo_sangre']!==null && !in_array($datos['tipo_sangre'],['A+','A-','B+','B-','AB+','AB-','O+','O-'],true)) { header("Location: medico_paciente.php?id=$pacienteId&error=datos"); exit; }
require_once __DIR__.'/../config/database.php';
try {
    $pdo=conectarBD(); $medico=exigirMedicoActivo($pdo); $pdo->beginTransaction();
    $stmt=$pdo->prepare('SELECT p.id FROM pacientes p WHERE p.id=:p AND p.activo=TRUE AND EXISTS(SELECT 1 FROM citas c WHERE c.paciente_id=p.id AND c.medico_id=:m) FOR UPDATE');
    $stmt->execute(['p'=>$pacienteId,'m'=>$medico['id']]);
    if (!$stmt->fetch()) { $pdo->rollBack(); http_response_code(403); exit('No tienes permiso para actualizar esta ficha.'); }
    $stmt=$pdo->prepare('SELECT * FROM informacion_medica_paciente WHERE paciente_id=:p'); $stmt->execute(['p'=>$pacienteId]); $anterior=$stmt->fetch() ?: null;
    if (!is_string($_POST['version']??null) || $_POST['version']!==($anterior['updated_at']??'')) { $pdo->rollBack(); header("Location: medico_paciente.php?id=$pacienteId&error=conflicto"); exit; }
    $stmt=$pdo->prepare('INSERT INTO informacion_medica_paciente(paciente_id,tipo_sangre,alergias,enfermedades_cronicas,medicamentos,antecedentes) VALUES(:paciente,:tipo_sangre,:alergias,:enfermedades_cronicas,:medicamentos,:antecedentes) ON CONFLICT(paciente_id) DO UPDATE SET tipo_sangre=EXCLUDED.tipo_sangre,alergias=EXCLUDED.alergias,enfermedades_cronicas=EXCLUDED.enfermedades_cronicas,medicamentos=EXCLUDED.medicamentos,antecedentes=EXCLUDED.antecedentes,updated_at=clock_timestamp()');
    $stmt->execute(['paciente'=>$pacienteId]+$datos);
    $stmt=$pdo->prepare('INSERT INTO historial_informacion_medica(paciente_id,usuario_id,datos_anteriores,datos_nuevos) VALUES(:p,:u,:anterior,:nuevo)');
    $stmt->execute(['p'=>$pacienteId,'u'=>$_SESSION['usuario_id'],'anterior'=>$anterior?json_encode($anterior,JSON_THROW_ON_ERROR):null,'nuevo'=>json_encode($datos,JSON_THROW_ON_ERROR)]);
    $pdo->commit();
    header("Location: medico_paciente.php?id=$pacienteId&guardado=1"); exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage()); header("Location: medico_paciente.php?id=$pacienteId&error=servidor"); exit;
}
