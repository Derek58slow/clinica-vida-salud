<?php
declare(strict_types=1);
require_once __DIR__.'/../src/Services/clinica.php';
function comprobar(bool $condicion,string $mensaje): void { if(!$condicion) throw new RuntimeException($mensaje); }
comprobar(fechaClinicaValida('2028-02-29'),'Debe aceptar un día bisiesto.');
comprobar(!fechaClinicaValida('2026-02-29'),'Debe rechazar fechas inexistentes.');
comprobar(inicioCitaValido('2026-11-01','25:00')===null,'Debe rechazar horas inválidas.');
comprobar(inicioCitaValido('2026-11-01','09:30')?->format('H:i')==='09:30','Debe conservar la hora local.');
$ahora=new DateTimeImmutable('2026-11-01 10:00');
$pasado=$ahora->modify('-1 minute'); $futuro=$ahora->modify('+1 minute');
comprobar(transicionCitaPermitida('confirmada','completada',3,$pasado,$ahora),'El médico puede completar una cita iniciada.');
comprobar(!transicionCitaPermitida('confirmada','completada',3,$futuro,$ahora),'No debe atender una cita futura.');
comprobar(!transicionCitaPermitida('confirmada','no_asistio',3,$futuro,$ahora),'No debe cerrar por ausencia una cita futura.');
comprobar(!transicionCitaPermitida('solicitada','confirmada',3,$pasado,$ahora),'El médico no confirma reservas.');
comprobar(transicionCitaPermitida('solicitada','confirmada',2,$futuro,$ahora),'Recepción puede confirmar una reserva futura.');
comprobar(!transicionCitaPermitida('no_asistio','completada',3,$pasado,$ahora),'Un estado final no puede reabrirse.');
comprobar(!transicionCitaPermitida('confirmada','completada',4,$pasado,$ahora),'El paciente no registra asistencia.');
ob_start(); require_once __DIR__.'/../config/database.php'; $salida=ob_get_clean();
comprobar($salida==='','La configuración no debe emitir contenido antes de las cabeceras.');
echo 'Fechas, transiciones y cabeceras: OK',PHP_EOL;
