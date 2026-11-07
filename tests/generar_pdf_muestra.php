<?php
require_once dirname(__DIR__) . '/src/Services/pdf_comprobante.php';
$salida = dirname(__DIR__) . '/output/pdf/comprobante-cita-muestra.pdf';
if (!is_dir(dirname($salida))) mkdir(dirname($salida), 0777, true);
$datos = [
    'codigo_cita'=>'CITA-DEMO-20261104','estado'=>'confirmada','paciente'=>'Paciente de Demostración',
    'documento_identidad'=>'12345678','especialidad'=>'Medicina general','medico'=>'Andrea Salvatierra',
    'inicio'=>'2026-11-04 10:00:00','fin'=>'2026-11-04 10:30:00','modalidad'=>'presencial',
    'consultorio'=>'Consultorio 204','sede'=>'Clínica Vida Salud - La Paz','motivo'=>'Consulta médica de seguimiento y control general.',
];
file_put_contents($salida, generarComprobanteCitaPdf($datos));
echo $salida;
