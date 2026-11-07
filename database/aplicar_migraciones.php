<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
try {
    $pdo=conectarBD();
    foreach (['20261030_invitaciones_usuario.sql','20261101_flujo_medico.sql','20261103_pagos_recepcion.sql','20261103_metodos_pago_demo.sql'] as $archivo) {
        $sql=file_get_contents(__DIR__.'/migrations/'.$archivo);
        if ($sql===false) throw new RuntimeException('No se pudo leer la migración.');
        $pdo->exec($sql);
        echo $archivo, ': OK', PHP_EOL;
    }
} catch(Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1);
}
