<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if ($rolId !== 1) {
    http_response_code(403);
    exit('No tienes permiso para editar especialidades.');
}

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: especialidades_moderno.php');
    exit;
}

try {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT * FROM especialidades WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $esp = $stmt->fetch();
    if (!$esp) {
        header('Location: especialidades_moderno.php');
        exit;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: especialidades_moderno.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('especialidades');
?>

<div style="margin-bottom: 24px; max-width: 700px; margin-left: auto; margin-right: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Editar especialidad</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Modifica los datos de la especialidad</p>
        </div>
        <a href="especialidades_moderno.php" class="btn btn-secondary">Volver al listado</a>
    </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto; box-shadow: var(--shadow-lg);">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">No se pudieron guardar los cambios.</div>
        <?php endif; ?>
        
        <form action="actualizar_especialidad.php" method="POST" novalidate>
            <input type="hidden" name="id" value="<?= (int)$esp['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label class="form-label required">Nombre de la especialidad</label>
                <input type="text" name="nombre" class="form-input" style="height: 44px;" maxlength="100" required value="<?= htmlspecialchars($esp['nombre'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-textarea" rows="4"><?= htmlspecialchars($esp['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--color-border);">
                <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">Guardar cambios</button>
                <a href="especialidades_moderno.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
