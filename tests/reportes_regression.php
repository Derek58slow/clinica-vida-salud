<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/reportes.php';
require_once __DIR__ . '/../src/Services/pdf_reporte.php';

$pdo = conectarBD();
$filtros = filtrosReporte(['desde' => date('Y-m-01'), 'hasta' => date('Y-m-t')]);
$catalogos = obtenerCatalogosReporte($pdo);
$reporte = obtenerReporteCitas($pdo, $filtros);

foreach (['total','pacientes_registrados','por_estado','por_medico','por_especialidad','detalle','pagos'] as $clave) {
    if (!array_key_exists($clave, $reporte)) throw new RuntimeException("Falta $clave en el reporte.");
}
if ($reporte['total'] !== count($reporte['detalle'])) throw new RuntimeException('El resumen y el detalle no coinciden.');
$pdf = generarReporteCitasPdf($reporte['detalle'], $reporte, descripcionFiltrosReporte($filtros, $catalogos));
if (!str_starts_with($pdf, '%PDF-1.4') || !str_contains($pdf, '%%EOF')) throw new RuntimeException('El PDF generado no es válido.');
foreach (['public/exportar_reporte_pdf.php','public/exportar_reporte_csv.php'] as $archivo) {
    if (!is_file(dirname(__DIR__) . '/' . $archivo)) throw new RuntimeException("Falta $archivo.");
}
echo "Reportes PDF/CSV, filtros y detalle: OK\n";
