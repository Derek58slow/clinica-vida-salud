<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rolId, [1, 2, 3], true)) {
    http_response_code(403);
    exit('No tienes permiso.');
}

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: horarios_moderno.php');
    exit;
}

try {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT h.*, m.nombres, m.apellidos FROM horarios_atencion h JOIN medicos m ON m.id = h.medico_id WHERE h.id = :id");
    $stmt->execute(['id' => $id]);
    $horario = $stmt->fetch();
    if (!$horario) {
        header('Location: horarios_moderno.php');
        exit;
    }
    $medicos = $pdo->query("SELECT id, nombres, apellidos FROM medicos WHERE activo = TRUE ORDER BY apellidos, nombres")->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: horarios_moderno.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('horarios');
?>

<div style="margin-bottom: 24px; max-width: 700px; margin-left: auto; margin-right: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Editar horario</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Actualizar horario de atención</p>
        </div>
        <a href="horarios_moderno.php" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto; box-shadow: var(--shadow-lg);">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $e = ['datos'=>'Revisa los datos.','rango'=>'La hora fin debe ser posterior a la hora inicio.','superpuesto'=>'Ya existe un horario superpuesto para este médico en el mismo día.','servidor'=>'No se pudo actualizar.'];
                echo htmlspecialchars($e[$_GET['error']] ?? 'Error al guardar.');
                ?>
            </div>
        <?php endif; ?>
        
        <form action="actualizar_horario.php" method="POST" novalidate>
            <input type="hidden" name="id" value="<?= (int)$horario['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label class="form-label required">Médico</label>
                <select name="medico_id" class="form-select" style="height: 44px;" required>
                    <?php foreach ($medicos as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= ($horario['medico_id'] == $m['id']) ? 'selected' : '' ?>>Dr(a). <?= htmlspecialchars($m['nombres'].' '.$m['apellidos']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label required">Día de la semana</label>
                    <select name="dia_semana" class="form-select" style="height: 44px;" required>
                        <option value="1" <?= $horario['dia_semana']==1?'selected':'' ?>>Lunes</option>
                        <option value="2" <?= $horario['dia_semana']==2?'selected':'' ?>>Martes</option>
                        <option value="3" <?= $horario['dia_semana']==3?'selected':'' ?>>Miércoles</option>
                        <option value="4" <?= $horario['dia_semana']==4?'selected':'' ?>>Jueves</option>
                        <option value="5" <?= $horario['dia_semana']==5?'selected':'' ?>>Viernes</option>
                        <option value="6" <?= $horario['dia_semana']==6?'selected':'' ?>>Sábado</option>
                        <option value="7" <?= $horario['dia_semana']==7?'selected':'' ?>>Domingo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">Intervalo (minutos)</label>
                    <select name="intervalo_minutos" class="form-select" style="height: 44px;" required>
                        <option value="15" <?= $horario['intervalo_minutos']==15?'selected':'' ?>>15</option>
                        <option value="20" <?= $horario['intervalo_minutos']==20?'selected':'' ?>>20</option>
                        <option value="30" <?= $horario['intervalo_minutos']==30?'selected':'' ?>>30</option>
                        <option value="45" <?= $horario['intervalo_minutos']==45?'selected':'' ?>>45</option>
                        <option value="60" <?= $horario['intervalo_minutos']==60?'selected':'' ?>>60</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label required">Hora inicio</label>
                    <input type="time" name="hora_inicio" class="form-input" style="height: 44px;" value="<?= htmlspecialchars($horario['hora_inicio']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Hora fin</label>
                    <input type="time" name="hora_fin" class="form-input" style="height: 44px;" value="<?= htmlspecialchars($horario['hora_fin']) ?>" required>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--color-border);">
                <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">Guardar cambios</button>
                <a href="horarios_moderno.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
