<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para gestionar especialidades.');
}

require_once __DIR__ . '/../config/database.php';

$busqueda = trim($_GET['buscar'] ?? '');

try {
    $pdo = conectarBD();
    
    if ($busqueda !== '') {
        $sql = "SELECT id, nombre, descripcion, activo FROM especialidades
                WHERE LOWER(nombre) LIKE LOWER(:busqueda) OR LOWER(COALESCE(descripcion,'')) LIKE LOWER(:busqueda)
                ORDER BY nombre ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['busqueda' => '%' . $busqueda . '%']);
    } else {
        $sql = "SELECT id, nombre, descripcion, activo FROM especialidades ORDER BY nombre ASC";
        $stmt = $pdo->query($sql);
    }
    $especialidades = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $especialidades = [];
    $error = 'No se pudo consultar las especialidades.';
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('especialidades');
?>

<div style="margin-bottom: 28px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin-bottom: 6px; letter-spacing: -0.02em;">Especialidades médicas</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Administra las especialidades disponibles en la clínica</p>
        </div>
        <a href="registrar_especialidad.php" class="btn btn-primary" style="padding: 11px 22px; font-weight: 600;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nueva especialidad
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['registrado'])): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Especialidad registrada correctamente
    </div>
<?php endif; ?>

<?php if (isset($_GET['actualizado'])): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Especialidad actualizada correctamente
    </div>
<?php endif; ?>

<?php if (isset($_GET['cambiado'])): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Estado actualizado correctamente
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; flex-wrap: wrap;">
            <div style="position: relative; flex: 1; min-width: 280px; max-width: 420px;">
                <svg style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-text-light); width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" name="buscar" class="form-input" style="padding-left: 44px; height: 44px;" placeholder="Buscar especialidad..." value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 44px;">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="especialidades_moderno.php" class="btn btn-secondary" style="height: 44px;">Limpiar</a>
            <?php endif; ?>
        </form>
        
        <?php if (empty($especialidades)): ?>
            <div class="empty-state" style="padding: 60px 24px;">
                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <div class="empty-state-title" style="font-size: 18px;">No hay especialidades registradas</div>
                <div class="empty-state-text">Agrega la primera especialidad para poder asignarla a los médicos</div>
                <a href="registrar_especialidad.php" class="btn btn-primary">Nueva especialidad</a>
            </div>
        <?php else: ?>
            <div class="table-container" style="border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding: 14px 20px;">Especialidad</th>
                            <th style="padding: 14px 20px;">Descripción</th>
                            <th style="padding: 14px 20px;">Estado</th>
                            <th style="padding: 14px 20px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($especialidades as $esp): ?>
                            <tr>
                                <td style="padding: 16px 20px;">
                                    <div style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($esp['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td style="padding: 16px 20px; color: var(--color-text-light); max-width: 400px;">
                                    <?= htmlspecialchars($esp['descripcion'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <?php if ($esp['activo']): ?>
                                        <span class="badge badge-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="editar_especialidad.php?id=<?= (int)$esp['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <form action="cambiar_estado_especialidad.php" method="POST" style="display: inline;" onsubmit="return confirm('¿Cambiar el estado de esta especialidad?');">
                                            <input type="hidden" name="id" value="<?= (int)$esp['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <button type="submit" class="btn btn-sm <?= $esp['activo'] ? 'btn-secondary' : 'btn-success' ?>">
                                                <?= $esp['activo'] ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>
