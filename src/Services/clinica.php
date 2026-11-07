<?php
declare(strict_types=1);

// Todas las fechas sin zona representan la hora local de la clínica.
$zonaClinica = getenv('APP_TIMEZONE') ?: 'America/La_Paz';
if (!in_array($zonaClinica, DateTimeZone::listIdentifiers(), true)) {
    throw new RuntimeException('APP_TIMEZONE no es una zona horaria válida.');
}
date_default_timezone_set($zonaClinica);

function fechaClinicaValida(string $fecha): bool
{
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $valor !== false && $valor->format('Y-m-d') === $fecha;
}

function inicioCitaValido(string $fecha, string $hora): ?DateTimeImmutable
{
    if (!fechaClinicaValida($fecha) || !preg_match('/^\d{2}:\d{2}$/D', $hora)) return null;
    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$fecha $hora");
    return $inicio !== false && $inicio->format('Y-m-d H:i') === "$fecha $hora" ? $inicio : null;
}

function establecerActorAuditoria(PDO $pdo, int $usuarioId): void
{
    if (!$pdo->inTransaction() || $usuarioId < 1) throw new LogicException('La auditoría requiere una transacción y un usuario.');
    $stmt = $pdo->prepare("SELECT set_config('app.usuario_id', :usuario, true)");
    $stmt->execute(['usuario' => (string)$usuarioId]);
}

function transicionCitaPermitida(string $actual, string $nuevo, int $rol, DateTimeImmutable $inicio, ?DateTimeImmutable $ahora = null): bool
{
    if (!in_array($rol, [1, 2, 3], true)) return false;
    if ($actual === 'solicitada' && $nuevo === 'confirmada') return $rol !== 3;
    return $actual === 'confirmada' && in_array($nuevo, ['completada', 'no_asistio'], true)
        && $inicio <= ($ahora ?? new DateTimeImmutable());
}

function h(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
