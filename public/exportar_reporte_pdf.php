<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([1, 2]);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/reportes.php';
require_once __DIR__ . '/../src/Services/pdf_reporte.php';

try {
    $pdo = conectarBD();
    $filtros = filtrosReporte($_GET);
    $catalogos = obtenerCatalogosReporte($pdo);
    $reporte = obtenerReporteCitas($pdo, $filtros);
    $pdf = generarReporteCitasPdf($reporte['detalle'], $reporte, descripcionFiltrosReporte($filtros, $catalogos));
    $nombre = 'reporte-citas-' . $filtros['desde'] . '-a-' . $filtros['hasta'] . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nombre . '"');
    header('Content-Length: ' . strlen($pdf));
    header('X-Content-Type-Options: nosniff');
    echo $pdf;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo generar el reporte PDF.');
}
