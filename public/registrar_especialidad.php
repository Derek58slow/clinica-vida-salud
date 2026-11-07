<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if ($rolId !== 1) {
    http_response_code(403);
    exit('No tienes permiso para registrar especialidades.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('especialidades');
?>

<div style="margin-bottom: 24px; max-width: 700px; margin-left: auto; margin-right: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Nueva especialidad</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Registra una nueva especialidad médica</p>
        </div>
        <a href="especialidades_moderno.php" class="btn btn-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Volver al listado
        </a>
    </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto; box-shadow: var(--shadow-lg);">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php
                $e = [
                    'datos' => 'Completa el nombre de la especialidad.',
                    'duplicado' => 'Ya existe una especialidad con ese nombre.',
                    'servidor' => 'No se pudo guardar la especialidad.'
                ];
                echo htmlspecialchars($e[$_GET['error']] ?? 'Error al guardar.');
                ?>
            </div>
        <?php endif; ?>
        
        <form action="guardar_especialidad.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label class="form-label required">Nombre de la especialidad</label>
                <input type="text" name="nombre" class="form-input" style="height: 44px;" maxlength="100" required placeholder="Ej: Medicina General, Pediatría, Ginecología">
            </div>
            
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-textarea" rows="4" placeholder="Descripción breve de la especialidad (opcional)"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--color-border);">
                <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">Guardar especialidad</button>
                <a href="especialidades_moderno.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
