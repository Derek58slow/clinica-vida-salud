<?php
declare(strict_types=1);
$raiz=dirname(__DIR__);
$leer=static fn(string $ruta): string => file_get_contents($raiz.'/'.$ruta) ?: '';
$verificar=static function(bool $ok,string $mensaje): void{if(!$ok)throw new RuntimeException($mensaje);};
$guardar=$leer('public/guardar_invitacion.php');$activar=$leer('public/activar_cuenta.php');$sql=$leer('database/migrations/20261030_invitaciones_usuario.sql');
$verificar(str_contains($sql,'token_hash CHAR(64)'),'La migración debe almacenar solamente el hash del token.');
$verificar(str_contains($guardar,'hash(\'sha256\',$token)'),'La invitación debe proteger el token antes de almacenarlo.');
$verificar(str_contains($guardar,"interval '72 hours'"),'La invitación debe caducar.');
$verificar(str_contains($guardar,'activo) VALUES')&&str_contains($guardar,'FALSE'),'La cuenta invitada debe permanecer inactiva.');
$verificar(str_contains($activar,'password_hash($clave,PASSWORD_DEFAULT)'),'La activación debe guardar una contraseña segura.');
$verificar(str_contains($activar,'usado_at=now()'),'El enlace debe quedar inutilizado después de activarse.');
echo "Comprobaciones de invitaciones: OK".PHP_EOL;
