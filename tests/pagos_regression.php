<?php
$base=dirname(__DIR__);$checks=[
 'public/pagos.php'=>['exigirRol([1,2])','Caja de hoy','registrar_pago.php'],
 'public/registrar_pago.php'=>['exigirRol([1,2])','FOR UPDATE','historial_pagos','csrf_token'],
 'public/anular_pago.php'=>['exigirRol([1])',"estado='anulado'",'historial_pagos'],
 'public/recibo_pago.php'=>['exigirRol([1,2,4])','paciente_usuario','application/pdf'],
 'database/migrations/20261103_pagos_recepcion.sql'=>['CREATE TABLE IF NOT EXISTS pagos','CREATE TABLE IF NOT EXISTS historial_pagos','uq_pago_activo_por_cita']
 ,'public/configurar_metodos_pago.php'=>['exigirRol([1])','move_uploaded_file','FILEINFO_MIME_TYPE','2*1024*1024']
 ,'public/metodos_pago.php'=>['exigirRol([4])','data-payment-method="card"','data-payment-method="paypal"','data-payment-method="wallet"','data-payment-method="qr"','no se realizó ningún cargo ni se guardaron datos financieros']
 ,'database/migrations/20261103_metodos_pago_demo.sql'=>['CREATE TABLE IF NOT EXISTS configuracion_pagos','CHECK (id = 1)']
];foreach($checks as $file=>$parts){$content=file_get_contents($base.'/'.$file);if($content===false){fwrite(STDERR,"No se pudo leer $file\n");exit(1);}foreach($parts as $part)if(!str_contains($content,$part)){fwrite(STDERR,"Falta $part en $file\n");exit(1);}}echo "Comprobaciones del módulo de pagos: OK\n";
