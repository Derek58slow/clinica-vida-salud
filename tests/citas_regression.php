<?php

declare(strict_types=1);

$raiz = dirname(__DIR__);

function contenido(string $ruta): string
{
    $valor = file_get_contents($ruta);
    if ($valor === false) {
        throw new RuntimeException("No se pudo leer: $ruta");
    }

    return $valor;
}

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$reserva = contenido($raiz . '/public/guardar_cita.php');
$reprogramacion = contenido($raiz . '/public/reprogramar_cita.php');
$cancelacion = contenido($raiz . '/public/cancelar_cita.php');
$cambioEstado = contenido($raiz . '/public/cambiar_estado_cita.php');
$detalle = contenido($raiz . '/public/ver_cita.php');

verificar(str_contains($reserva, "tsrange(inicio, fin, '[)')"), 'La reserva debe conservar la validación de superposición.');
verificar(str_contains($reserva, "estado IN ('solicitada','confirmada')"), 'La reserva debe considerar como ocupadas las citas activas.');
verificar(str_contains($reprogramacion, "tsrange(inicio, fin, '[)')"), 'La reprogramación debe usar rangos PostgreSQL válidos.');
verificar(!str_contains($reprogramacion, 'estado IN (\\"'), 'La reprogramación no debe usar comillas dobles para valores SQL.');
verificar(str_contains($reprogramacion, 'FOR UPDATE'), 'La reprogramación debe bloquear la cita durante la transacción.');
verificar(str_contains($cancelacion, "SET estado = 'cancelada'"), 'La cancelación debe usar un literal PostgreSQL válido.');
verificar(str_contains($cancelacion, 'FOR UPDATE'), 'La cancelación debe bloquear la cita durante la transacción.');
verificar(str_contains($cambioEstado, 'REQUEST_METHOD') && str_contains($cambioEstado, 'POST'), 'El cambio de estado debe aceptar únicamente POST.');
verificar(str_contains($cambioEstado, 'hash_equals'), 'El cambio de estado debe validar el token CSRF.');
verificar(!str_contains($detalle, 'cambiar_estado_cita.php?id='), 'El detalle no debe cambiar estados mediante enlaces GET.');

echo "Comprobaciones del módulo de citas: OK" . PHP_EOL;
