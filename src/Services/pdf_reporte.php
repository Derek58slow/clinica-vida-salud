<?php
declare(strict_types=1);

require_once __DIR__ . '/pdf_comprobante.php';

/** Genera un reporte A4 horizontal y paginado sin dependencias externas. */
function generarReporteCitasPdf(array $filas, array $resumen, string $descripcion): string
{
    $porPagina = 22;
    $paginasDatos = array_chunk($filas, $porPagina);
    if (!$paginasDatos) $paginasDatos = [[]];
    $streams = [];

    foreach ($paginasDatos as $indice => $pagina) {
        $c = [];
        $rect = static function(float $x,float $y,float $w,float $h,string $color) use (&$c): void { $c[]="$color rg $x $y $w $h re f"; };
        $line = static function(float $x1,float $y1,float $x2,float $y2,string $color,float $ancho=1) use (&$c): void { $c[]="$color RG $ancho w $x1 $y1 m $x2 $y2 l S"; };
        $txt = static function(string $v,float $x,float $y,float $s=9,bool $b=false,string $color='0.12 0.18 0.24') use (&$c): void {
            $f=$b?'F2':'F1'; $c[]="BT /$f $s Tf $color rg $x $y Td (".pdfTexto($v).") Tj ET";
        };
        $cortar = static function(?string $v, int $max): string {
            $v = trim((string)$v);
            return mb_strlen($v) > $max ? mb_substr($v, 0, $max - 3) . '...' : $v;
        };

        $rect(0,0,842,595,'1 1 1');
        $rect(0,510,842,85,'0.055 0.43 0.47');
        $rect(0,510,842,7,'0.12 0.66 0.55');
        $rect(30,530,42,42,'1 1 1'); $rect(46,536,10,30,'0.055 0.43 0.47'); $rect(36,546,30,10,'0.055 0.43 0.47');
        $txt('CLINICA VIDA SALUD',88,558,17,true,'1 1 1');
        $txt('REPORTE DETALLADO DE CITAS',88,537,10,false,'0.84 0.95 0.95');
        $txt('Pagina '.($indice+1).' de '.count($paginasDatos),731,550,9,true,'1 1 1');
        $txt($cortar($descripcion,125),30,492,8,false,'0.32 0.40 0.45');

        if ($indice === 0) {
            $datos = [
                ['Total citas',(string)$resumen['total']],
                ['Solicitadas',(string)($resumen['por_estado']['solicitada']??0)],
                ['Confirmadas',(string)($resumen['por_estado']['confirmada']??0)],
                ['Atendidas',(string)($resumen['por_estado']['completada']??0)],
                ['Canceladas',(string)($resumen['por_estado']['cancelada']??0)],
                ['Cobrado','Bs '.number_format((float)$resumen['pagos']['cobrado'],2)],
            ];
            $x=30;
            foreach($datos as [$etiqueta,$valor]){
                $rect($x,440,124,38,'0.94 0.97 0.98'); $txt($etiqueta,$x+8,465,7,true,'0.35 0.43 0.48'); $txt($valor,$x+8,449,11,true,'0.055 0.43 0.47'); $x+=130;
            }
            $tablaY=420;
        } else {
            $tablaY=468;
        }

        $columnas = [
            ['Fecha',30,76],['Código',106,90],['Paciente',196,135],['Médico',331,125],
            ['Especialidad',456,112],['Estado',568,75],['Modalidad',643,68],['Pago',711,101],
        ];
        $rect(30,$tablaY-22,782,22,'0.055 0.43 0.47');
        foreach($columnas as [$nombre,$x,$w]) $txt($nombre,$x+5,$tablaY-14,7,true,'1 1 1');
        $y=$tablaY-43;
        foreach($pagina as $n=>$fila){
            if($n%2===0)$rect(30,$y-5,782,19,'0.965 0.98 0.985');
            $pago = $fila['estado_pago']==='pagado' ? 'Bs '.number_format((float)$fila['monto'],2) : ($fila['estado_pago']==='anulado'?'Anulado':'Pendiente');
            $valores=[date('d/m/Y H:i',strtotime($fila['inicio'])),$fila['codigo_cita'],$fila['paciente'],$fila['medico'],$fila['especialidad'],ucfirst(str_replace('_',' ',$fila['estado'])),ucfirst($fila['modalidad']),$pago];
            $maximos=[16,15,24,22,19,13,11,16];
            foreach($columnas as $i=>[$nombre,$x,$w]) $txt($cortar((string)$valores[$i],$maximos[$i]),$x+5,$y,7,false);
            $line(30,$y-6,812,$y-6,'0.88 0.91 0.93',0.5); $y-=19;
        }
        if(!$pagina)$txt('No existen citas para los filtros seleccionados.',30,$tablaY-50,10,false,'0.35 0.43 0.48');
        $line(30,34,812,34,'0.78 0.86 0.88');
        $txt('Generado el '.date('d/m/Y H:i').' | Clinica Vida Salud',30,18,8,false,'0.38 0.46 0.51');
        $txt('Documento generado electronicamente',650,18,8,false,'0.38 0.46 0.51');
        $streams[] = implode("\n",$c);
    }

    $objetos=[];
    $pageIds=[]; $contentIds=[];
    $next=5;
    foreach($streams as $_){$pageIds[]=$next++;$contentIds[]=$next++;}
    $objetos[1]='<< /Type /Catalog /Pages 2 0 R >>';
    $objetos[2]='<< /Type /Pages /Kids ['.implode(' ',array_map(fn($id)=>$id.' 0 R',$pageIds)).'] /Count '.count($pageIds).' >>';
    $objetos[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objetos[4]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    foreach($streams as $i=>$stream){
        $objetos[$pageIds[$i]]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentIds[$i].' 0 R >>';
        $objetos[$contentIds[$i]]='<< /Length '.strlen($stream)." >>\nstream\n$stream\nendstream";
    }
    ksort($objetos); $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0];
    foreach($objetos as $n=>$obj){$offsets[$n]=strlen($pdf);$pdf.="$n 0 obj\n$obj\nendobj\n";}
    $xref=strlen($pdf); $size=max(array_keys($objetos))+1; $pdf.="xref\n0 $size\n0000000000 65535 f \n";
    for($i=1;$i<$size;$i++)$pdf.=isset($offsets[$i])?sprintf('%010d 00000 n ',$offsets[$i])."\n":"0000000000 00000 f \n";
    return $pdf."trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
}
