<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/reportes.php';
require_once __DIR__ . '/../src/Services/pdf_reporte.php';
$pdo=conectarBD();$f=filtrosReporte(['desde'=>'2026-01-01','hasta'=>'2026-12-31']);$c=obtenerCatalogosReporte($pdo);$r=obtenerReporteCitas($pdo,$f);
$salida=dirname(__DIR__).'/output/pdf/reporte-citas-muestra.pdf';if(!is_dir(dirname($salida)))mkdir(dirname($salida),0777,true);
file_put_contents($salida,generarReporteCitasPdf($r['detalle'],$r,descripcionFiltrosReporte($f,$c)));echo $salida,PHP_EOL;
