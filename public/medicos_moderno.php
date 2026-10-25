<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para gestionar médicos.');
}

require_once __DIR__ . '/../config/database.php';

$busqueda = trim($_GET['buscar'] ?? '');

try {
    $pdo = conectarBD();
    
    if ($busqueda !== '') {
        $sql = "SELECT m.id, m.nombres, m.apellidos, m.numero_licencia, m.telefono, m.correo, m.consultorio, m.activo,
                       string_agg(e.nombre, ', ' ORDER BY e.nombre) AS especialidades
                FROM medicos m
                LEFT JOIN medicos_especialidades me ON me.medico_id = m.id
                LEFT JOIN especialidades e ON e.id = me.especialidad_id
                WHERE LOWER(m.nombres) LIKE LOWER(:busqueda)
                   OR LOWER(m.apellidos) LIKE LOWER(:busqueda)
                   OR LOWER(m.numero_licencia) LIKE LOWER(:busqueda)
                   OR LOWER(COALESCE(e.nombre,'')) LIKE LOWER(:busqueda)
                GROUP BY m.id, m.nombres, m.apellidos, m.numero_licencia, m.telefono, m.correo, m.consultorio, m.activo
                ORDER BY m.apellidos, m.nombres";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['busqueda' => '%' . $busqueda . '%']);
    } else {
        $sql = "SELECT m.id, m.nombres, m.apellidos, m.numero_licencia, m.telefono, m.correo, m.consultorio, m.activo,
                       string_agg(e.nombre, ', ' ORDER BY e.nombre) AS especialidades
                FROM medicos m
                LEFT JOIN medicos_especialidades me ON me.medico_id = m.id
                LEFT JOIN especialidades e ON e.id = me.especialidad_id
                GROUP BY m.id, m.nombres, m.apellidos, m.numero_licencia, m.telefono, m.correo, m.consultorio, m.activo
                ORDER BY m.apellidos, m.nombres";
        $stmt = $pdo->query($sql);
    }
    $medicos = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $medicos = [];
    $error = 'No se pudo consultar los médicos.';
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('medicos');
?>

<div style="margin-bottom: 28px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Médicos</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Gestiona médicos, licencias y especialidades asignadas</p>
        </div>
        <a href="registrar_medico.php" class="btn btn-primary" style="padding: 11px 22px; font-weight: 600;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nuevo médico
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (isset($_GET['registrado'])): ?>
    <div class="alert alert-success">Médico registrado correctamente</div>
<?php endif; ?>
<?php if (isset($_GET['actualizado'])): ?>
    <div class="alert alert-success">Médico actualizado correctamente</div>
<?php endif; ?>
<?php if (isset($_GET['cambiado'])): ?>
    <div class="alert alert-success">Estado actualizado correctamente</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; margin-bottom: 24px;">
            <div style="position: relative; flex: 1; min-width: 280px; max-width: 420px;">
                <svg style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-text-light); width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" name="buscar" class="form-input" style="padding-left: 44px; height: 44px;" placeholder="Buscar por nombre, apellidos o licencia..." value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 44px;">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="medicos_moderno.php" class="btn btn-secondary" style="height: 44px;">Limpiar</a>
            <?php endif; ?>
        </form>
        
        <?php if (empty($medicos)): ?>
            <div class="empty-state" style="padding: 60px 24px;">
                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="empty-state-title" style="font-size: 18px;">No hay médicos registrados</div>
                <div class="empty-state-text">Registra médicos y asígnales sus especialidades</div>
                <a href="registrar_medico.php" class="btn btn-primary">Nuevo médico</a>
            </div>
        <?php else: ?>
            <div class="table-container" style="border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding: 14px 20px;">Médico</th>
                            <th style="padding: 14px 20px;">Licencia</th>
                            <th style="padding: 14px 20px;">Especialidades</th>
                            <th style="padding: 14px 20px;">Contacto</th>
                            <th style="padding: 14px 20px;">Consultorio</th>
                            <th style="padding: 14px 20px;">Estado</th>
                            <th style="padding: 14px 20px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicos as $m): ?>
                            <tr>
                                <td style="padding: 16px 20px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); color: var(--color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                            <?= strtoupper(substr($m['nombres'], 0, 1) . substr($m['apellidos'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;">Dr(a). <?= htmlspecialchars($m['nombres'] . ' ' . $m['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 16px 20px;"><?= htmlspecialchars($m['numero_licencia'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding: 16px 20px; color: var(--color-text-light); max-width: 280px;"><?= htmlspecialchars($m['especialidades'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding: 16px 20px; font-size: 13px;">
                                    <div><?= htmlspecialchars($m['telefono'] ?? '—') ?></div>
                                    <div style="color: var(--color-text-light);"><?= htmlspecialchars($m['correo'] ?? '') ?></div>
                                </td>
                                <td style="padding: 16px 20px;"><?= htmlspecialchars($m['consultorio'] ?? '—') ?></td>
                                <td style="padding: 16px 20px;">
                                    <?php if ($m['activo']): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="editar_medico.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <form action="cambiar_estado_medico.php" method="POST" style="display: inline;" onsubmit="return confirm('¿Cambiar el estado del médico?');">
                                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <button type="submit" class="btn btn-sm <?= $m['activo'] ? 'btn-secondary' : 'btn-success' ?>">
                                                <?= $m['activo'] ? 'Desactivar' : 'Activar' ?>
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
