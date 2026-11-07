<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if ($rolId !== 1) {
    http_response_code(403);
    exit('No tienes permiso para gestionar horarios.');
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = conectarBD();
    $stmt = $pdo->query("SELECT h.id, h.medico_id, h.dia_semana, h.hora_inicio, h.hora_fin, h.intervalo_minutos, h.activo,
                                m.nombres, m.apellidos
                         FROM horarios_atencion h
                         JOIN medicos m ON m.id = h.medico_id
                         ORDER BY m.apellidos, m.nombres, h.dia_semana, h.hora_inicio");
    $horarios = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $horarios = [];
    $error = 'No se pudo consultar los horarios.';
}

$dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];

require_once __DIR__ . '/includes/layout.php';
renderSidebar('horarios');
?>

<div style="margin-bottom: 28px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Horarios de atención</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Configura días, horas e intervalos por médico</p>
        </div>
        <div class="action-row"><a href="bloqueos_horario.php" class="btn btn-secondary"><?= icono('ausencia') ?> Ausencias y bloqueos</a><a href="registrar_horario.php" class="btn btn-primary"><?= icono('agenda') ?> Nuevo horario</a></div>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="alert alert-error"><?= htmlspecialchars(['citas'=>'Este horario tiene citas activas pendientes. Reprograma o cancela esas citas antes de modificar la disponibilidad.','superpuesto'=>'No se puede activar un horario que se superpone con otro turno activo.','servidor'=>'No se pudo actualizar el horario.'][$_GET['error']]??'No se pudo actualizar el horario.') ?></div><?php endif; ?>
<?php if (isset($_GET['registrado'])): ?><div class="alert alert-success">Horario registrado correctamente</div><?php endif; ?>
<?php if (isset($_GET['actualizado'])): ?><div class="alert alert-success">Horario actualizado correctamente</div><?php endif; ?>
<?php if (isset($_GET['cambiado'])): ?><div class="alert alert-success">Estado actualizado</div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($horarios)): ?>
            <div class="empty-state" style="padding: 60px 24px;">
                <div style="width: 80px; height: 80px; margin: 0 auto 20px; background: linear-gradient(135deg, #EAF6F8 0%, #F0FDFA 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                    </svg>
                </div>
                <div class="empty-state-title" style="font-size: 18px;">Sin horarios configurados</div>
                <div class="empty-state-text">Agrega los horarios de atención por médico</div>
                <a href="registrar_horario.php" class="btn btn-primary">Nuevo horario</a>
            </div>
        <?php else: ?>
            <div class="table-container" style="border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding: 14px 20px;">Médico</th>
                            <th style="padding: 14px 20px;">Día</th>
                            <th style="padding: 14px 20px;">Hora inicio</th>
                            <th style="padding: 14px 20px;">Hora fin</th>
                            <th style="padding: 14px 20px;">Intervalo (min)</th>
                            <th style="padding: 14px 20px;">Estado</th>
                            <th style="padding: 14px 20px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horarios as $h): ?>
                            <tr>
                                <td style="padding: 16px 20px; font-weight: 600;">Dr(a). <?= htmlspecialchars($h['nombres'].' '.$h['apellidos']) ?></td>
                                <td style="padding: 16px 20px;"><?= $dias[(int)$h['dia_semana']] ?? $h['dia_semana'] ?></td>
                                <td style="padding: 16px 20px;"><?= htmlspecialchars($h['hora_inicio']) ?></td>
                                <td style="padding: 16px 20px;"><?= htmlspecialchars($h['hora_fin']) ?></td>
                                <td style="padding: 16px 20px;"><?= (int)$h['intervalo_minutos'] ?></td>
                                <td style="padding: 16px 20px;">
                                    <?php if ($h['activo']): ?><span class="badge badge-success">Activo</span><?php else: ?><span class="badge badge-danger">Inactivo</span><?php endif; ?>
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="editar_horario.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <form action="cambiar_estado_horario.php" method="POST" style="display: inline;" onsubmit="return confirm('¿Cambiar estado del horario?');">
                                            <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <button type="submit" class="btn btn-sm <?= $h['activo'] ? 'btn-secondary' : 'btn-success' ?>"><?= $h['activo'] ? 'Desactivar' : 'Activar' ?></button>
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
