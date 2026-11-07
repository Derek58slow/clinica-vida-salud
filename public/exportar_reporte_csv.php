<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([1, 2]);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/reportes.php';

try {
    $pdo = conectarBD();
    $filtros = filtrosReporte($_GET);
    $reporte = obtenerReporteCitas($pdo, $filtros);
    $nombre = 'reporte-citas-' . $filtros['desde'] . '-a-' . $filtros['hasta'] . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('X-Content-Type-Options: nosniff');
    $salida = fopen('php://output', 'wb');
    fwrite($salida, "\xEF\xBB\xBF"); // BOM para conservar acentos al abrir en Excel.
    fputcsv($salida, ['Código', 'Fecha', 'Hora inicio', 'Hora fin', 'Paciente', 'Médico', 'Especialidad', 'Modalidad', 'Consultorio', 'Estado', 'Código pago', 'Método pago', 'Monto (Bs)', 'Estado pago'], ';');
    foreach ($reporte['detalle'] as $fila) {
        fputcsv($salida, [
            $fila['codigo_cita'], date('d/m/Y', strtotime($fila['inicio'])), date('H:i', strtotime($fila['inicio'])),
            date('H:i', strtotime($fila['fin'])), $fila['paciente'], $fila['medico'], $fila['especialidad'],
            ucfirst($fila['modalidad']), $fila['consultorio'], ucfirst(str_replace('_', ' ', $fila['estado'])),
            $fila['codigo_pago'] ?? '', $fila['metodo_pago'] ? ucfirst($fila['metodo_pago']) : '',
            $fila['monto'] !== null ? number_format((float)$fila['monto'], 2, '.', '') : '',
            $fila['estado_pago'] ? ucfirst($fila['estado_pago']) : 'Pendiente',
        ], ';');
    }
    fclose($salida);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo generar el archivo para Excel.');
}
