<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login_moderno.php");
    exit;
}

$rolId = (int) ($_SESSION["rol_id"] ?? 0);
if (!in_array($rolId, [1, 2, 4], true)) {
    http_response_code(403);
    exit("No tienes permiso.");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: citas_moderno.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pdo = conectarBD();
$stmt = $pdo->prepare("SELECT * FROM citas WHERE id = :id");
$stmt->execute(["id" => $id]);
$cita = $stmt->fetch();
if (!$cita) {
    header("Location: citas_moderno.php");
    exit;
}
if ($rolId === 4) {
    $propietario = $pdo->prepare('SELECT id FROM pacientes WHERE usuario_id=:usuario_id AND activo=TRUE');
    $propietario->execute(['usuario_id'=>$_SESSION['usuario_id']]);
    $paciente = $propietario->fetch();
    if (!$paciente || (int)$paciente['id'] !== (int)$cita['paciente_id']) { http_response_code(403); exit('No tienes permiso para cancelar esta cita.'); }
}
if (strtotime($cita['inicio']) <= time()) {
    header("Location: ver_cita.php?id=$id&error=no_cancelar");
    exit;
}
if (!in_array($cita["estado"], ["solicitada", "confirmada"], true)) {
    header("Location: ver_cita.php?id=$id&error=no_cancelar");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Solicitud no válida.");
    }
    $motivo = trim($_POST["motivo_cancelacion"] ?? "");
    if ($motivo === "" || mb_strlen($motivo) > 500) {
        header("Location: cancelar_cita.php?id=$id&error=datos");
        exit;
    }
    try {
        $pdo->beginTransaction();
        establecerActorAuditoria($pdo, (int)$_SESSION['usuario_id']);
        $stmtActual = $pdo->prepare("SELECT estado FROM citas WHERE id = :id FOR UPDATE");
        $stmtActual->execute(["id" => $id]);
        $citaActual = $stmtActual->fetch();
        if (!$citaActual || !in_array($citaActual["estado"], ["solicitada", "confirmada"], true)) {
            $pdo->rollBack();
            header("Location: ver_cita.php?id=$id&error=no_cancelar");
            exit;
        }
        $upd = $pdo->prepare("UPDATE citas SET estado = 'cancelada', motivo_cancelacion = :mot, updated_at = now() WHERE id = :id");
        $upd->execute(["mot" => $motivo, "id" => $id]);
        $pdo->commit();
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        header("Location: ver_cita.php?id=$id&cancelada=1");
        exit;
    } catch (Throwable $e) {
        if (isset($pdo)) { try { $pdo->rollBack(); } catch (Throwable $e2) {} }
        error_log($e->getMessage());
        header("Location: cancelar_cita.php?id=$id&error=servidor");
        exit;
    }
}

require_once __DIR__ . "/includes/layout.php";
renderSidebar($rolId === 4 ? "mis_citas" : "citas");
?>
<div style="max-width: 700px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700;">Cancelar Cita</h1>
            <p style="color: var(--color-text-light);">Código: <?= htmlspecialchars($cita["codigo_cita"]) ?></p>
        </div>
        <a href="ver_cita.php?id=<?= (int)$id ?>" class="btn btn-secondary">Volver</a>
    </div>

    <?php if (isset($_GET["error"])): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
            <?php
            $e = ["datos" => "Debes indicar el motivo de cancelación.", "servidor" => "No se pudo cancelar la cita."];
            echo htmlspecialchars($e[$_GET["error"]] ?? "Error al cancelar.");
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; padding: 16px; border-radius: 10px; margin-bottom: 24px; color: #991B1B;">
                <strong>Atención:</strong> Esta acción cambiará el estado de la cita a cancelada. El registro se conservará y quedará registrado en el historial.
            </div>
            <form method="POST" action="cancelar_cita.php?id=<?= (int)$id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">
                <div class="form-group">
                    <label class="form-label required">Motivo de cancelación</label>
                    <textarea name="motivo_cancelacion" class="form-textarea" rows="4" maxlength="500" required placeholder="Describe el motivo de la cancelación"></textarea>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn" style="background: #DC2626; color: white;">Confirmar cancelación</button>
                    <a href="ver_cita.php?id=<?= (int)$id ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php renderFooter(); ?>

