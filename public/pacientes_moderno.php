<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para ver pacientes.');
}

require_once __DIR__ . '/../config/database.php';

$busqueda = trim($_GET['buscar'] ?? '');

try {
    $pdo = conectarBD();
    
    if ($busqueda !== '') {
        $sql = "SELECT id, nombres, apellidos, documento_identidad, telefono, correo, activo, created_at
                FROM pacientes
                WHERE LOWER(nombres) LIKE LOWER(:busqueda) 
                   OR LOWER(apellidos) LIKE LOWER(:busqueda)
                   OR LOWER(documento_identidad) LIKE LOWER(:busqueda)
                ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['busqueda' => '%' . $busqueda . '%']);
    } else {
        $sql = "SELECT id, nombres, apellidos, documento_identidad, telefono, correo, activo, created_at
                FROM pacientes
                ORDER BY id DESC";
        $stmt = $pdo->query($sql);
    }
    $pacientes = $stmt->fetchAll();
    
} catch (Throwable $e) {
    error_log($e->getMessage());
    $pacientes = [];
    $error = 'No se pudo consultar la lista de pacientes.';
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('pacientes');
?>

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

<?php if (isset($_GET['registrado']) && $_GET['registrado'] == '1'): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Paciente registrado correctamente
    </div>
<?php endif; ?>

<?php if (isset($_GET['actualizado']) && $_GET['actualizado'] == '1'): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Paciente actualizado correctamente
    </div>
<?php endif; ?>

<?php if (isset($_GET['cambiado']) && $_GET['cambiado'] == '1'): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Estado del paciente actualizado correctamente
    </div>
<?php endif; ?>

<div style="margin-bottom: 28px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin-bottom: 6px; letter-spacing: -0.02em;">Pacientes</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Gestiona el registro, búsqueda y estado de los pacientes</p>
        </div>
        <a href="registrar_paciente_moderno.php" class="btn btn-primary" style="padding: 11px 22px; font-weight: 600;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nuevo paciente
        </a>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
        <div class="stat-card" style="margin-bottom: 0;">
            <div class="stat-icon blue">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total registrados</div>
                <div class="stat-value" style="font-size: 24px;"><?= count($pacientes) ?></div>
            </div>
        </div>
        <div class="stat-card" style="margin-bottom: 0;">
            <div class="stat-icon green">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Activos</div>
                <div class="stat-value" style="font-size: 24px;"><?= count(array_filter($pacientes, fn($p) => $p['activo'])) ?></div>
            </div>
        </div>
        <div class="stat-card" style="margin-bottom: 0;">
            <div class="stat-icon red">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Inactivos</div>
                <div class="stat-value" style="font-size: 24px;"><?= count(array_filter($pacientes, fn($p) => !$p['activo'])) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px; flex-wrap: wrap;">
            <div style="position: relative; flex: 1; min-width: 280px; max-width: 500px;">
                <svg style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-text-light); width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" name="buscar" class="form-input" style="padding-left: 44px; height: 44px; font-size: 14px;" placeholder="Buscar por nombre, apellidos o documento de identidad..." value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 20px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Buscar
            </button>
            <?php if ($busqueda !== ''): ?>
                <a href="pacientes_moderno.php" class="btn btn-secondary" style="height: 44px; padding: 0 18px;">Limpiar filtros</a>
            <?php endif; ?>
        </form>
        
        <?php if (empty($pacientes)): ?>
            <div class="empty-state" style="padding: 60px 24px;">
                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="empty-state-title" style="font-size: 18px;">No se encontraron pacientes</div>
                <div class="empty-state-text" style="max-width: 400px; margin: 0 auto 24px;">
                    <?php if ($busqueda !== ''): ?>
                        No hay resultados para "<strong><?= htmlspecialchars($busqueda) ?></strong>". Intenta con otros términos de búsqueda.
                    <?php else: ?>
                        Aún no hay pacientes registrados en el sistema. Comienza agregando el primer paciente.
                    <?php endif; ?>
                </div>
                <?php if ($busqueda === ''): ?>
                    <a href="registrar_paciente_moderno.php" class="btn btn-primary" style="padding: 11px 24px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Registrar primer paciente
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container" style="border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--color-border);">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding: 14px 20px;">Paciente</th>
                            <th style="padding: 14px 20px;">Documento</th>
                            <th style="padding: 14px 20px;">Contacto</th>
                            <th style="padding: 14px 20px;">Estado</th>
                            <th style="padding: 14px 20px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pacientes as $paciente): ?>
                            <tr>
                                <td style="padding: 16px 20px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); color: var(--color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; flex-shrink: 0;">
                                            <?= strtoupper(substr($paciente['nombres'], 0, 1) . substr($paciente['apellidos'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div style="font-size: 12px; color: var(--color-text-light);">ID: <?= (int)$paciente['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 16px 20px; color: var(--color-text);">
                                    <?= htmlspecialchars($paciente['documento_identidad'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <div style="font-size: 13px; line-height: 1.8;">
                                        <?php if (!empty($paciente['telefono'])): ?>
                                            <div style="color: var(--color-text);"><?= htmlspecialchars($paciente['telefono']) ?></div>
                                        <?php else: ?>
                                            <div style="color: var(--color-text-light);">—</div>
                                        <?php endif; ?>
                                        <?php if (!empty($paciente['correo'])): ?>
                                            <div style="color: var(--color-text-light); font-size: 12px;"><?= htmlspecialchars($paciente['correo']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <?php if ($paciente['activo']): ?>
                                        <span class="badge badge-success" style="padding: 5px 12px;">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" style="padding: 5px 12px;">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="historial_paciente.php?paciente_id=<?= (int)$paciente['id'] ?>" class="btn btn-sm btn-secondary" title="Historial" style="padding: 8px 12px;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Historial
                                        </a>
                                        <a href="editar_paciente_moderno.php?id=<?= (int)$paciente['id'] ?>" class="btn btn-sm btn-secondary" title="Editar paciente" style="padding: 8px 12px;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Editar
                                        </a>
                                        <form action="cambiar_estado_paciente.php" method="POST" style="display: inline;" onsubmit="return confirm('¿Confirmas cambiar el estado de <?= htmlspecialchars(addslashes($paciente['nombres'] . ' ' . $paciente['apellidos'])) ?>?');">
                                            <input type="hidden" name="id" value="<?= (int)$paciente['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <button type="submit" class="btn btn-sm <?= $paciente['activo'] ? 'btn-secondary' : 'btn-success' ?>" title="<?= $paciente['activo'] ? 'Desactivar' : 'Activar' ?>" style="padding: 8px 12px;">
                                                <?php if ($paciente['activo']): ?>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                        <line x1="12" y1="1" x2="12" y2="3"></line>
                                                        <line x1="12" y1="21" x2="12" y2="23"></line>
                                                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                                        <line x1="1" y1="12" x2="3" y2="12"></line>
                                                        <line x1="21" y1="12" x2="23" y2="12"></line>
                                                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                                                    </svg>
                                                    Desactivar
                                                <?php else: ?>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    Activar
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; color: var(--color-text-light); font-size: 13px;">
                Total: <?= count($pacientes) ?> paciente(s)
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
renderFooter();
?>
