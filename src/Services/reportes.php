<?php
declare(strict_types=1);

require_once __DIR__ . '/clinica.php';

/**
 * Normaliza los filtros compartidos por la pantalla y las exportaciones.
 * Las fechas representan la zona horaria local configurada para la clínica.
 */
function filtrosReporte(array $entrada): array
{
    $desde = trim((string)($entrada['desde'] ?? date('Y-m-01')));
    $hasta = trim((string)($entrada['hasta'] ?? date('Y-m-d')));
    if (!fechaClinicaValida($desde)) $desde = date('Y-m-01');
    if (!fechaClinicaValida($hasta)) $hasta = date('Y-m-d');
    if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

    $medico = filter_var($entrada['medico_id'] ?? null, FILTER_VALIDATE_INT);
    $especialidad = filter_var($entrada['especialidad_id'] ?? null, FILTER_VALIDATE_INT);
    $estado = (string)($entrada['estado'] ?? '');
    $estados = ['', 'solicitada', 'confirmada', 'completada', 'cancelada', 'no_asistio'];
    if (!in_array($estado, $estados, true)) $estado = '';

    return [
        'desde' => $desde,
        'hasta' => $hasta,
        'medico_id' => $medico !== false && $medico > 0 ? (int)$medico : null,
        'especialidad_id' => $especialidad !== false && $especialidad > 0 ? (int)$especialidad : null,
        'estado' => $estado,
    ];
}

function condicionReporte(array $filtros): array
{
    $condiciones = ['c.inicio BETWEEN :desde AND :hasta'];
    $parametros = [
        'desde' => $filtros['desde'] . ' 00:00:00',
        'hasta' => $filtros['hasta'] . ' 23:59:59',
    ];
    if ($filtros['medico_id']) {
        $condiciones[] = 'c.medico_id = :medico';
        $parametros['medico'] = $filtros['medico_id'];
    }
    if ($filtros['especialidad_id']) {
        $condiciones[] = 'c.especialidad_id = :especialidad';
        $parametros['especialidad'] = $filtros['especialidad_id'];
    }
    if ($filtros['estado'] !== '') {
        $condiciones[] = 'c.estado = :estado';
        $parametros['estado'] = $filtros['estado'];
    }
    return [implode(' AND ', $condiciones), $parametros];
}

function obtenerCatalogosReporte(PDO $pdo): array
{
    return [
        'medicos' => $pdo->query("SELECT id, apellidos || ', ' || nombres nombre FROM medicos ORDER BY apellidos, nombres")->fetchAll(),
        'especialidades' => $pdo->query('SELECT id, nombre FROM especialidades ORDER BY nombre')->fetchAll(),
    ];
}

/** Obtiene el resumen y el detalle usando exactamente los mismos filtros. */
function obtenerReporteCitas(PDO $pdo, array $filtros): array
{
    [$where, $params] = condicionReporte($filtros);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas c WHERE $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT c.estado, COUNT(*) cantidad FROM citas c WHERE $where GROUP BY c.estado");
    $stmt->execute($params);
    $porEstado = [];
    foreach ($stmt->fetchAll() as $fila) $porEstado[$fila['estado']] = (int)$fila['cantidad'];

    $stmt = $pdo->prepare("SELECT m.id, m.apellidos, m.nombres, COUNT(*) cantidad
        FROM citas c JOIN medicos m ON m.id = c.medico_id
        WHERE $where GROUP BY m.id, m.apellidos, m.nombres ORDER BY cantidad DESC, m.apellidos");
    $stmt->execute($params);
    $porMedico = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT e.id, e.nombre, COUNT(*) cantidad
        FROM citas c JOIN especialidades e ON e.id = c.especialidad_id
        WHERE $where GROUP BY e.id, e.nombre ORDER BY cantidad DESC, e.nombre");
    $stmt->execute($params);
    $porEspecialidad = $stmt->fetchAll();

    $pacientes = $pdo->prepare('SELECT COUNT(*) FROM pacientes WHERE created_at BETWEEN :desde AND :hasta');
    $pacientes->execute(['desde' => $params['desde'], 'hasta' => $params['hasta']]);

    $detalle = $pdo->prepare("SELECT c.codigo_cita, c.inicio, c.fin, c.modalidad, c.estado,
            p.nombres || ' ' || p.apellidos paciente,
            m.apellidos || ', ' || m.nombres medico,
            e.nombre especialidad, COALESCE(m.consultorio, '') consultorio,
            pg.codigo_pago, pg.monto, pg.metodo metodo_pago, pg.estado estado_pago
        FROM citas c
        JOIN pacientes p ON p.id = c.paciente_id
        JOIN medicos m ON m.id = c.medico_id
        JOIN especialidades e ON e.id = c.especialidad_id
        LEFT JOIN LATERAL (
            SELECT codigo_pago, monto, metodo, estado
            FROM pagos WHERE cita_id = c.id
            ORDER BY (estado = 'pagado') DESC, pagado_at DESC, id DESC LIMIT 1
        ) pg ON TRUE
        WHERE $where ORDER BY c.inicio, m.apellidos, p.apellidos");
    $detalle->execute($params);

    $pagos = $pdo->prepare("SELECT
            COALESCE(SUM(pg.monto) FILTER (WHERE pg.estado = 'pagado'), 0) cobrado,
            COUNT(pg.id) FILTER (WHERE pg.estado = 'pagado') pagos,
            COUNT(c.id) FILTER (WHERE pg.id IS NULL AND c.estado <> 'cancelada') sin_pago
        FROM citas c LEFT JOIN pagos pg ON pg.cita_id = c.id AND pg.estado = 'pagado'
        WHERE $where");
    $pagos->execute($params);
    $resumenPagos = $pagos->fetch() ?: ['cobrado' => 0, 'pagos' => 0, 'sin_pago' => 0];

    return [
        'total' => $total,
        'pacientes_registrados' => (int)$pacientes->fetchColumn(),
        'por_estado' => $porEstado,
        'por_medico' => $porMedico,
        'por_especialidad' => $porEspecialidad,
        'detalle' => $detalle->fetchAll(),
        'pagos' => $resumenPagos,
    ];
}

function descripcionFiltrosReporte(array $filtros, array $catalogos): string
{
    $partes = ['Del ' . date('d/m/Y', strtotime($filtros['desde'])) . ' al ' . date('d/m/Y', strtotime($filtros['hasta']))];
    if ($filtros['medico_id']) {
        foreach ($catalogos['medicos'] as $m) if ((int)$m['id'] === $filtros['medico_id']) $partes[] = 'Médico: ' . $m['nombre'];
    }
    if ($filtros['especialidad_id']) {
        foreach ($catalogos['especialidades'] as $e) if ((int)$e['id'] === $filtros['especialidad_id']) $partes[] = 'Especialidad: ' . $e['nombre'];
    }
    if ($filtros['estado'] !== '') $partes[] = 'Estado: ' . ucfirst(str_replace('_', ' ', $filtros['estado']));
    return implode(' | ', $partes);
}
