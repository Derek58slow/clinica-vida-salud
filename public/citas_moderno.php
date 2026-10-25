<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para gestionar citas.');
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = conectarBD();
    
    $stmt = $pdo->query("SELECT c.id, c.codigo_cita, c.inicio, c.fin, c.estado, c.modalidad, c.motivo,
                                p.nombres as pn, p.apellidos as pa,
                                m.nombres as mn, m.apellidos as ma,
                                e.nombre as especialidad
                         FROM citas c
                         JOIN pacientes p ON p.id = c.paciente_id
                         JOIN medicos m ON m.id = c.medico_id
                         JOIN especialidades e ON e.id = c.especialidad_id
                         ORDER BY c.inicio DESC
                         LIMIT 100");
    $citas = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $citas = [];
    $error = 'No se pudo consultar las citas.';
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('citas');
?>

<div style="margin-bottom: 28px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Citas médicas</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Registro, consulta y seguimiento de citas</p>
        </div>
        <a href="nueva_cita.php" class="btn btn-primary" style="padding: 11px 22px; font-weight: 600;">Nueva cita</a>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (isset($_GET['registrada'])): ?><div class="alert alert-success">Cita registrada correctamente</div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($citas)): ?>
            <div class="empty-state" style="padding: 60px 24px;">
                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="empty-state-title" style="font-size: 18px;">No hay citas registradas</div>
                <div class="empty-state-text">Agenda la primera cita médica</div>
                <a href="nueva_cita.php" class="btn btn-primary">Nueva cita</a>
            </div>
        <?php else: ?>
            <div class="table-container" style="border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding: 14px 20px;">Código</th>
                            <th style="padding: 14px 20px;">Paciente</th>
                            <th style="padding: 14px 20px;">Médico</th>
                            <th style="padding: 14px 20px;">Especialidad</th>
                            <th style="padding: 14px 20px;">Fecha/Hora</th>
                            <th style="padding: 14px 20px;">Estado</th>
                            <th style="padding: 14px 20px;">Modalidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas as $c): ?>
                            <tr>
                                <td style="padding: 16px 20px; font-weight: 600;"><?= htmlspecialchars($c['codigo_cita']) ?></td>
                                <td style="padding: 16px 20px;"><?= htmlspecialchars($c['pn'].' '.$c['pa']) ?></td>
                                <td style="padding: 16px 20px;">Dr(a). <?= htmlspecialchars($c['mn'].' '.$c['ma']) ?></td>
                                <td style="padding: 16px 20px;"><?= htmlspecialchars($c['especialidad']) ?></td>
                                <td style="padding: 16px 20px;"><?= date('d/m/Y H:i', strtotime($c['inicio'])) ?></td>
                                <td style="padding: 16px 20px;">
                                    <?php
                                    $map = ['solicitada'=>'warning','confirmada'=>'success','completada'=>'info','cancelada'=>'danger','no_asistio'=>'danger'];
                                    $cls = $map[$c['estado']] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $cls ?>"><?= ucfirst(str_replace('_',' ', $c['estado'])) ?></span>
                                </td>
                                <td style="padding: 16px 20px;"><?= ucfirst($c['modalidad']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php renderFooter(); ?>
