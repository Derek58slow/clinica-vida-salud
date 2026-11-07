<?php
declare(strict_types=1);

/** Datos consolidados del panel administrativo. */
function obtenerPanelAdministrador(PDO $pdo): array
{
    $resumen = $pdo->query("SELECT
        (SELECT COUNT(*) FROM pacientes WHERE activo=TRUE) pacientes,
        (SELECT COUNT(*) FROM medicos WHERE activo=TRUE) medicos,
        (SELECT COUNT(*) FROM citas WHERE inicio::date=CURRENT_DATE) citas_hoy,
        (SELECT COUNT(*) FROM citas WHERE estado='solicitada' AND inicio>=CURRENT_TIMESTAMP) por_confirmar,
        (SELECT COUNT(*) FROM citas WHERE estado IN ('solicitada','confirmada') AND inicio>=CURRENT_TIMESTAMP) proximas,
        (SELECT COALESCE(SUM(monto),0) FROM pagos WHERE estado='pagado' AND pagado_at::date=CURRENT_DATE) cobrado_hoy,
        (SELECT COUNT(*) FROM citas c LEFT JOIN pagos pg ON pg.cita_id=c.id AND pg.estado='pagado'
          WHERE c.estado<>'cancelada' AND c.inicio>=CURRENT_DATE AND pg.id IS NULL) pagos_pendientes")->fetch() ?: [];

    $citas = $pdo->query("SELECT c.id,c.inicio,c.estado,p.nombres||' '||p.apellidos paciente,
        m.nombres||' '||m.apellidos medico,e.nombre especialidad
        FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN medicos m ON m.id=c.medico_id
        JOIN especialidades e ON e.id=c.especialidad_id
        WHERE c.inicio>=CURRENT_TIMESTAMP AND c.estado IN ('solicitada','confirmada')
        ORDER BY c.inicio LIMIT 6")->fetchAll();

    $actividad = $pdo->query("SELECT h.created_at,h.accion,h.estado_anterior,h.estado_nuevo,h.comentario,
        c.id cita_id,c.codigo_cita,p.nombres||' '||p.apellidos paciente,COALESCE(u.nombre,'Sistema') actor
        FROM historial_citas h JOIN citas c ON c.id=h.cita_id JOIN pacientes p ON p.id=c.paciente_id
        LEFT JOIN usuarios u ON u.id=h.usuario_id ORDER BY h.created_at DESC,h.id DESC LIMIT 6")->fetchAll();

    return ['resumen'=>$resumen,'citas'=>$citas,'actividad'=>$actividad];
}
