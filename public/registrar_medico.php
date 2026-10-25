<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para registrar médicos.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = conectarBD();
    $stmt = $pdo->query("SELECT id, nombre FROM especialidades WHERE activo = TRUE ORDER BY nombre");
    $especialidades = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $especialidades = [];
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('medicos');
?>

<div style="margin-bottom: 24px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Nuevo médico</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Registra un médico y asigna sus especialidades</p>
        </div>
        <a href="medicos_moderno.php" class="btn btn-secondary">Volver al listado</a>
    </div>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto; box-shadow: var(--shadow-lg);">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $e = [
                    'datos' => 'Completa los campos obligatorios correctamente.',
                    'licencia' => 'Ya existe un médico con ese número de licencia.',
                    'correo' => 'Ya existe un médico con ese correo.',
                    'servidor' => 'No se pudo registrar el médico.'
                ];
                echo htmlspecialchars($e[$_GET['error']] ?? 'Error al guardar.');
                ?>
            </div>
        <?php endif; ?>
        
        <form action="guardar_medico.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div style="margin-bottom: 32px;">
                <h3 style="font-size: 17px; font-weight: 600; margin-bottom: 16px; color: var(--color-text);">Datos personales</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Nombres</label>
                        <input type="text" name="nombres" class="form-input" style="height: 44px;" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Apellidos</label>
                        <input type="text" name="apellidos" class="form-input" style="height: 44px;" maxlength="100" required>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">Número de licencia</label>
                        <input type="text" name="numero_licencia" class="form-input" style="height: 44px;" maxlength="80" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consultorio</label>
                        <input type="text" name="consultorio" class="form-input" style="height: 44px;" maxlength="100">
                    </div>
                </div>
            </div>
            
            <div style="padding-top: 28px; border-top: 1px solid var(--color-border); margin-bottom: 32px;">
                <h3 style="font-size: 17px; font-weight: 600; margin-bottom: 16px; color: var(--color-text);">Contacto</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-input" style="height: 44px;" maxlength="30">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" class="form-input" style="height: 44px;" maxlength="255">
                    </div>
                </div>
            </div>
            
            <div style="padding-top: 28px; border-top: 1px solid var(--color-border);">
                <h3 style="font-size: 17px; font-weight: 600; margin-bottom: 12px; color: var(--color-text);">Especialidades</h3>
                <p style="color: var(--color-text-light); font-size: 13px; margin-bottom: 16px;">Selecciona una o varias especialidades para este médico</p>
                <?php if (empty($especialidades)): ?>
                    <div class="alert alert-warning">No hay especialidades activas. <a href="registrar_especialidad.php">Crear especialidad</a></div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
                        <?php foreach ($especialidades as $esp): ?>
                            <label style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid var(--color-border); border-radius: var(--radius); cursor: pointer; transition: all 0.2s;">
                                <input type="checkbox" name="especialidades[]" value="<?= (int)$esp['id'] ?>" style="width: 18px; height: 18px;">
                                <span style="color: var(--color-text);"><?= htmlspecialchars($esp['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 36px; padding-top: 28px; border-top: 1px solid var(--color-border);">
                <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">Guardar médico</button>
                <a href="medicos_moderno.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
