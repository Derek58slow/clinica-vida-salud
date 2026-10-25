<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login_moderno.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/includes/layout.php";

$pdo = conectarBD();
$stmt = $pdo->prepare("SELECT n.*, c.codigo_cita FROM notificaciones n LEFT JOIN citas c ON c.id = n.cita_id WHERE n.usuario_id = :u ORDER BY n.created_at DESC LIMIT 50");
$stmt->execute(["u" => $_SESSION["usuario_id"]]);
$notificaciones = $stmt->fetchAll();

renderSidebar("");
?>
<div style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 26px; font-weight: 700;">Notificaciones</h1>
        <p style="color: var(--color-text-light);">Notificaciones internas del sistema</p>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <?php if (empty($notificaciones)): ?>
                <div style="padding: 32px; text-align: center; color: var(--color-text-light);">No hay notificaciones</div>
            <?php else: ?>
                <?php foreach ($notificaciones as $n): ?>
                    <div style="padding: 16px 20px; border-bottom: 1px solid var(--color-border); display: flex; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #EFF6FF; color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">??</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 500; margin-bottom: 4px;"><?= htmlspecialchars($n["mensaje"]) ?></div>
                            <div style="color: var(--color-text-light); font-size: 13px;">
                                <?= date("d/m/Y H:i", strtotime($n["created_at"])) ?>
                                <?php if ($n["codigo_cita"]): ?> • <?= htmlspecialchars($n["codigo_cita"]) ?><?php endif; ?>
                                • Estado: <?= htmlspecialchars($n["estado"]) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php renderFooter(); ?>

