<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login_moderno.php");
    exit;
}

$rolId = (int) ($_SESSION["rol_id"] ?? 0);
if (!in_array($rolId, [1, 2, 3], true)) {
    http_response_code(403);
    exit("No tienes permiso.");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$nuevoEstado = $_GET["estado"] ?? "";
if (!$id || !$nuevoEstado) {
    header("Location: citas_moderno.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
$pdo = conectarBD();

$estadosValidos = ["solicitada", "confirmada", "completada", "cancelada", "no_asistio"];
if (!in_array($nuevoEstado, $estadosValidos, true)) {
    header("Location: ver_cita.php?id=$id&error=estado");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM citas WHERE id = :id");
$stmt->execute(["id" => $id]);
$cita = $stmt->fetch();
if (!$cita) {
    header("Location: citas_moderno.php");
    exit;
}

$estadoActual = $cita["estado"];
$transiciones = [
    "solicitada" => ["confirmada", "cancelada"],
    "confirmada" => ["completada", "no_asistio", "cancelada"],
    "completada" => [],
    "cancelada" => [],
    "no_asistio" => []
];
if (!in_array($nuevoEstado, $transiciones[$estadoActual] ?? [], true)) {
    header("Location: ver_cita.php?id=$id&error=transicion");
    exit;
}

if ($rolId === 3) {
    $stm = $pdo->prepare("SELECT id FROM medicos WHERE usuario_id = :u");
    $stm->execute(["u" => $_SESSION["usuario_id"]]);
    $mp = $stm->fetch();
    if (!$mp || (int)$mp["id"] !== (int)$cita["medico_id"]) {
        http_response_code(403);
        exit("No tienes permiso.");
    }
}

try {
    $pdo->beginTransaction();
    $upd = $pdo->prepare("UPDATE citas SET estado = :est, updated_at = now() WHERE id = :id");
    $upd->execute(["est" => $nuevoEstado, "id" => $id]);
    $pdo->commit();
    header("Location: ver_cita.php?id=$id&estado_ok=1");
    exit;
} catch (Throwable $e) {
    if (isset($pdo)) { try { $pdo->rollBack(); } catch (Throwable $e2) {} }
    error_log($e->getMessage());
    header("Location: ver_cita.php?id=$id&error=servidor");
    exit;
}

