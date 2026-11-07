<?php
function pdfTexto(string $texto): string {
    $convertido = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto);
    return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)',' ',' '], $convertido !== false ? $convertido : $texto);
}
function generarComprobanteCitaPdf(array $cita): string {
    $contenido=[];
    $rect=static function(float $x,float $y,float $w,float $h,string $color)use(&$contenido):void{$contenido[]="$color rg $x $y $w $h re f";};
    $linea=static function(float $x1,float $y1,float $x2,float $y2,string $color,float $ancho=1)use(&$contenido):void{$contenido[]="$color RG $ancho w $x1 $y1 m $x2 $y2 l S";};
    $texto=static function(string $valor,float $x,float $y,float $tam=11,bool $negrita=false,string $color='0.12 0.18 0.24')use(&$contenido):void{$fuente=$negrita?'F2':'F1';$contenido[]="BT /$fuente $tam Tf $color rg $x $y Td (".pdfTexto($valor).") Tj ET";};
    $estado=ucfirst(str_replace('_',' ',(string)$cita['estado']));
    $rect(0,0,595,842,'1 1 1');$rect(0,700,595,142,'0.055 0.43 0.47');$rect(0,700,595,8,'0.12 0.66 0.55');
    $rect(42,748,54,54,'1 1 1');$rect(61,758,16,34,'0.055 0.43 0.47');$rect(52,767,34,16,'0.055 0.43 0.47');
    $texto('CLINICA VIDA SALUD',116,783,20,true,'1 1 1');$texto('Sistema de gestion de citas medicas',116,760,11,false,'0.84 0.95 0.95');
    $texto('COMPROBANTE DE CITA',42,664,22,true,'0.055 0.43 0.47');$texto('Presenta este documento al llegar a la clinica.',42,644,10,false,'0.35 0.43 0.48');
    $rect(420,640,133,48,'0.91 0.97 0.96');$texto('ESTADO',434,673,8,true,'0.22 0.48 0.47');$texto($estado,434,653,13,true,'0.055 0.43 0.47');
    $rect(42,590,511,34,'0.94 0.97 0.98');$texto('CODIGO DE CITA',56,612,8,true,'0.35 0.43 0.48');$texto((string)$cita['codigo_cita'],180,607,13,true);
    $texto('DATOS DE LA ATENCION',42,557,12,true,'0.055 0.43 0.47');$linea(42,548,553,548,'0.78 0.86 0.88');
    $campos=[['Paciente',$cita['paciente']],['Documento',$cita['documento_identidad']?:'No registrado'],['Especialidad',$cita['especialidad']],['Profesional','Dr(a). '.$cita['medico']],['Fecha',date('d/m/Y',strtotime($cita['inicio']))],['Horario',date('H:i',strtotime($cita['inicio'])).' - '.date('H:i',strtotime($cita['fin']))],['Modalidad',ucfirst($cita['modalidad'])],['Consultorio',$cita['consultorio']?:($cita['modalidad']==='virtual'?'Atencion virtual':'Por confirmar')],['Sede',$cita['sede']?:'Clinica Vida Salud']];
    $y=520;foreach($campos as $i=>$campo){if($i===8){$x=42;$w=511;}else{$x=$i%2===0?42:304;$w=249;}$rect($x,$y-31,$w,44,'0.975 0.985 0.99');$texto($campo[0],$x+12,$y,8,true,'0.38 0.46 0.51');$texto((string)$campo[1],$x+12,$y-18,11,true);if($i%2===1)$y-=55;}
    $texto('MOTIVO DE CONSULTA',42,250,9,true,'0.055 0.43 0.47');$rect(42,205,511,30,'0.975 0.985 0.99');$motivo=trim((string)($cita['motivo']??''));if($motivo==='')$motivo='Sin motivo registrado';if(mb_strlen($motivo)>82)$motivo=mb_substr($motivo,0,79).'...';$texto($motivo,54,217,10);
    $rect(42,90,511,90,'0.91 0.97 0.96');$texto('RECOMENDACIONES',56,157,10,true,'0.055 0.43 0.47');$texto('- Presentate 15 minutos antes de la hora programada.',56,136,10);$texto('- Lleva tu documento de identidad y este comprobante.',56,119,10);$texto('- Si no puedes asistir, cancela o reprograma desde tu cuenta.',56,102,10);
    $linea(42,65,553,65,'0.78 0.86 0.88');$texto('Emitido el '.date('d/m/Y H:i').' | Clinica Vida Salud',42,44,9,false,'0.38 0.46 0.51');$texto('Documento generado electronicamente',381,44,9,false,'0.38 0.46 0.51');
    $stream=implode("\n",$contenido);$objetos=[1=>'<< /Type /Catalog /Pages 2 0 R >>',2=>'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',3=>'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',5=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',6=>'<< /Length '.strlen($stream)." >>\nstream\n$stream\nendstream"];
    $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=[0];foreach($objetos as $numero=>$objeto){$offsets[$numero]=strlen($pdf);$pdf.="$numero 0 obj\n$objeto\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 7\n0000000000 65535 f \n";for($i=1;$i<=6;$i++)$pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";$pdf.="trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";return $pdf;
}

function generarReciboPagoPdf(array $pago): string {
    $c=[];$rect=static function($x,$y,$w,$h,$color)use(&$c){$c[]="$color rg $x $y $w $h re f";};$line=static function($x1,$y1,$x2,$y2,$color)use(&$c){$c[]="$color RG 1 w $x1 $y1 m $x2 $y2 l S";};
    $txt=static function($v,$x,$y,$s=11,$b=false,$color='0.12 0.18 0.24')use(&$c){$f=$b?'F2':'F1';$c[]="BT /$f $s Tf $color rg $x $y Td (".pdfTexto((string)$v).") Tj ET";};
    $rect(0,0,595,842,'1 1 1');$rect(0,700,595,142,'0.055 0.43 0.47');$rect(0,700,595,8,'0.12 0.66 0.55');$rect(42,748,54,54,'1 1 1');$rect(61,758,16,34,'0.055 0.43 0.47');$rect(52,767,34,16,'0.055 0.43 0.47');
    $txt('CLINICA VIDA SALUD',116,783,20,true,'1 1 1');$txt('Comprobante interno de pago',116,760,11,false,'0.84 0.95 0.95');$txt('RECIBO DE PAGO',42,655,22,true,'0.055 0.43 0.47');
    $rect(390,625,163,65,$pago['estado']==='pagado'?'0.91 0.97 0.96':'1 0.92 0.92');$txt('TOTAL PAGADO',405,669,8,true,'0.22 0.48 0.47');$txt('Bs '.number_format((float)$pago['monto'],2),405,644,20,true,$pago['estado']==='pagado'?'0.055 0.43 0.47':'0.68 0.17 0.17');
    $rect(42,585,511,32,'0.94 0.97 0.98');$txt('COMPROBANTE',56,596,8,true,'0.35 0.43 0.48');$txt($pago['codigo_pago'],180,596,12,true);$txt('DETALLE DEL PAGO',42,550,12,true,'0.055 0.43 0.47');$line(42,541,553,541,'0.78 0.86 0.88');
    $campos=[['Paciente',$pago['paciente']],['Documento',$pago['documento_identidad']?:'No registrado'],['Codigo de cita',$pago['codigo_cita']],['Especialidad',$pago['especialidad']],['Fecha de cita',date('d/m/Y H:i',strtotime($pago['inicio']))],['Fecha de pago',date('d/m/Y H:i',strtotime($pago['pagado_at']))],['Metodo',ucfirst($pago['metodo'])],['Referencia',$pago['referencia']?:'No aplica'],['Registrado por',$pago['registrado_por_nombre']]];
    $y=510;foreach($campos as $i=>$campo){$x=$i===8?42:($i%2===0?42:304);$w=$i===8?511:249;$rect($x,$y-31,$w,44,'0.975 0.985 0.99');$txt($campo[0],$x+12,$y,8,true,'0.38 0.46 0.51');$valor=(string)$campo[1];if(mb_strlen($valor)>37)$valor=mb_substr($valor,0,34).'...';$txt($valor,$x+12,$y-18,10,true);if($i%2===1)$y-=55;}
    $rect(42,145,511,88,'0.91 0.97 0.96');$txt('INFORMACION',56,210,10,true,'0.055 0.43 0.47');$txt('Este documento acredita un pago interno registrado en Clinica Vida Salud.',56,188,10);$txt('No constituye factura fiscal ni reemplaza documentos tributarios del SIN.',56,169,10);$txt('Estado: '.ucfirst($pago['estado']),56,151,10,true);$line(42,105,553,105,'0.78 0.86 0.88');$txt('Emitido el '.date('d/m/Y H:i').' | Clinica Vida Salud',42,83,9,false,'0.38 0.46 0.51');$txt('Documento generado electronicamente',381,83,9,false,'0.38 0.46 0.51');
    $stream=implode("\n",$c);$objs=[1=>'<< /Type /Catalog /Pages 2 0 R >>',2=>'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',3=>'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',5=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',6=>'<< /Length '.strlen($stream)." >>\nstream\n$stream\nendstream"];
    $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$off=[0];foreach($objs as $n=>$o){$off[$n]=strlen($pdf);$pdf.="$n 0 obj\n$o\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 7\n0000000000 65535 f \n";for($i=1;$i<=6;$i++)$pdf.=sprintf('%010d 00000 n ',$off[$i])."\n";$pdf.="trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";return $pdf;
}
