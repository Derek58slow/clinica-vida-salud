<?php
declare(strict_types=1);

require_once __DIR__.'/clinica.php';

function filtrosAuditoria(array $entrada): array
{
    $desde=(string)($entrada['desde']??date('Y-m-01'));$hasta=(string)($entrada['hasta']??date('Y-m-d'));
    if(!fechaClinicaValida($desde))$desde=date('Y-m-01');if(!fechaClinicaValida($hasta))$hasta=date('Y-m-d');
    if($desde>$hasta)[$desde,$hasta]=[$hasta,$desde];
    $accion=(string)($entrada['accion']??'');$acciones=['','creacion','cambio_estado','reprogramacion','actualizacion'];
    if(!in_array($accion,$acciones,true))$accion='';
    return ['desde'=>$desde,'hasta'=>$hasta,'accion'=>$accion,'q'=>trim((string)($entrada['q']??''))];
}

function obtenerEventosAuditoria(PDO $pdo,array $filtros): array
{
    $cond=['h.created_at BETWEEN :desde AND :hasta'];
    $params=['desde'=>$filtros['desde'].' 00:00:00','hasta'=>$filtros['hasta'].' 23:59:59'];
    if($filtros['accion']!==''){$cond[]='h.accion=:accion';$params['accion']=$filtros['accion'];}
    if($filtros['q']!==''){$cond[]="(LOWER(c.codigo_cita) LIKE :q OR LOWER(p.nombres||' '||p.apellidos) LIKE :q OR LOWER(COALESCE(u.nombre,'')) LIKE :q)";$params['q']='%'.mb_strtolower($filtros['q']).'%';}
    $sql="SELECT h.*,c.codigo_cita,p.nombres||' '||p.apellidos paciente,m.apellidos||', '||m.nombres medico,
        e.nombre especialidad,COALESCE(u.nombre,'Sistema') actor
        FROM historial_citas h JOIN citas c ON c.id=h.cita_id JOIN pacientes p ON p.id=c.paciente_id
        JOIN medicos m ON m.id=c.medico_id JOIN especialidades e ON e.id=c.especialidad_id
        LEFT JOIN usuarios u ON u.id=h.usuario_id WHERE ".implode(' AND ',$cond)."
        ORDER BY h.created_at DESC,h.id DESC LIMIT 300";
    $stmt=$pdo->prepare($sql);$stmt->execute($params);return $stmt->fetchAll();
}

function nombresAccionesAuditoria(): array
{
    return ['creacion'=>'Creación','cambio_estado'=>'Cambio de estado','reprogramacion'=>'Reprogramación','actualizacion'=>'Actualización'];
}
