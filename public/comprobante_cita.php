<?php
require_once __DIR__.'/includes/panel_helpers.php';
$rolId=exigirRol([1,2,3,4]);
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../src/Services/pdf_comprobante.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){http_response_code(404);exit('Cita no encontrada.');}
try{
    $pdo=conectarBD();
    $stmt=$pdo->prepare("SELECT c.*,p.nombres||' '||p.apellidos paciente,p.documento_identidad,p.usuario_id paciente_usuario,m.nombres||' '||m.apellidos medico,m.consultorio,m.sede,m.usuario_id medico_usuario,e.nombre especialidad FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN medicos m ON m.id=c.medico_id JOIN especialidades e ON e.id=c.especialidad_id WHERE c.id=:id");
    $stmt->execute(['id'=>$id]);$cita=$stmt->fetch();if(!$cita){http_response_code(404);exit('Cita no encontrada.');}
    $usuarioId=(int)$_SESSION['usuario_id'];if(($rolId===4&&(int)$cita['paciente_usuario']!==$usuarioId)||($rolId===3&&(int)$cita['medico_usuario']!==$usuarioId)){http_response_code(403);exit('No tienes permiso para descargar este comprobante.');}
    $pdf=generarComprobanteCitaPdf($cita);$nombre='comprobante-'.preg_replace('/[^A-Za-z0-9_-]/','-',$cita['codigo_cita']).'.pdf';
    header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.$nombre.'"');header('Content-Length: '.strlen($pdf));header('X-Content-Type-Options: nosniff');echo $pdf;
}catch(Throwable $e){error_log($e->getMessage());http_response_code(500);exit('No se pudo generar el comprobante.');}
