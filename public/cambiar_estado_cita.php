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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: citas_moderno.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";
if (!is_string($token) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
    http_response_code(403);
    exit("Solicitud no válida.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$nuevoEstado = $_POST["estado"] ?? "";
if (!$id || !$nuevoEstado) {
    header("Location: citas_moderno.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/includes/panel_helpers.php';
$pdo = conectarBD();

$estadosValidos = ["confirmada", "completada", "no_asistio"];
if (!in_array($nuevoEstado, $estadosValidos, true)) {
    header("Location: ver_cita.php?id=$id&error=estado");
    exit;
}

try {
    $pdo->beginTransaction();
    establecerActorAuditoria($pdo, (int)$_SESSION['usuario_id']);
    $stmt = $pdo->prepare("SELECT * FROM citas WHERE id = :id FOR UPDATE");
    $stmt->execute(["id" => $id]);
    $cita = $stmt->fetch();
    if (!$cita) {
        $pdo->rollBack();
        header("Location: citas_moderno.php");
        exit;
    }

    $transiciones = [
        "solicitada" => ["confirmada"],
        "confirmada" => ["completada", "no_asistio"],
        "completada" => [],
        "cancelada" => [],
        "no_asistio" => []
    ];
    if (!in_array($nuevoEstado, $transiciones[$cita["estado"]] ?? [], true)) {
        $pdo->rollBack();
        header("Location: ver_cita.php?id=$id&error=transicion");
        exit;
    }

    if ($rolId === 3) {
        if (!in_array($nuevoEstado, ["completada", "no_asistio"], true)) {
            $pdo->rollBack();
            http_response_code(403);
            exit("No tienes permiso.");
        }
        $stm = $pdo->prepare("SELECT id FROM medicos WHERE usuario_id = :u AND activo = TRUE");
        $stm->execute(["u" => $_SESSION["usuario_id"]]);
        $mp = $stm->fetch();
        if (!$mp || (int)$mp["id"] !== (int)$cita["medico_id"]) {
            $pdo->rollBack();
            http_response_code(403);
            exit("No tienes permiso.");
        }
    }

    if (!transicionCitaPermitida($cita['estado'], $nuevoEstado, $rolId, new DateTimeImmutable($cita['inicio']))) {
        $pdo->rollBack();
        header("Location: ver_cita.php?id=$id&error=fecha");
        exit;
    }
    $upd = $pdo->prepare("UPDATE citas SET estado = :est, updated_at = now() WHERE id = :id");
    $upd->execute(["est" => $nuevoEstado, "id" => $id]);
    $pdo->commit();
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    $contexto = contextoAgenda($_POST);
    header("Location: ver_cita.php?id=$id&estado_ok=1&$contexto");
    exit;
} catch (Throwable $e) {
    if (isset($pdo)) { try { $pdo->rollBack(); } catch (Throwable $e2) {} }
    error_log($e->getMessage());
    header("Location: ver_cita.php?id=$id&error=servidor");
    exit;
}

