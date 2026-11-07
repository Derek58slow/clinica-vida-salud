<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_moderno.php');
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);

if ($rolId !== 1) {
    http_response_code(403);
    exit('No tienes permiso.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = conectarBD();
    $stmt = $pdo->query("SELECT id, nombres, apellidos FROM medicos WHERE activo = TRUE ORDER BY apellidos, nombres");
    $medicos = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $medicos = [];
}

require_once __DIR__ . '/includes/layout.php';
renderSidebar('horarios');
?>

<div style="margin-bottom: 24px; max-width: 700px; margin-left: auto; margin-right: auto;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h1 style="font-size: 26px; font-weight: 700; color: var(--color-text); margin-bottom: 6px;">Nuevo horario de atención</h1>
            <p style="color: var(--color-text-light); font-size: 14px;">Define día, hora y duración de consulta</p>
        </div>
        <a href="horarios_moderno.php" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto; box-shadow: var(--shadow-lg);">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $e = ['datos'=>'Revisa los datos del formulario.','rango'=>'La hora fin debe ser posterior a la hora inicio.','superpuesto'=>'Ya existe un horario superpuesto para este médico en el mismo día. Puedes registrar turnos separados (ej. 08:00-12:00 y 14:00-18:00).','servidor'=>'No se pudo guardar el horario.'];
                echo htmlspecialchars($e[$_GET['error']] ?? 'Error al guardar.');
                ?>
            </div>
        <?php endif; ?>
        
        <form action="guardar_horario.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label class="form-label required">Médico</label>
                <select name="medico_id" class="form-select" style="height: 44px;" required>
                    <option value="">Seleccionar médico</option>
                    <?php foreach ($medicos as $m): ?>
                        <option value="<?= (int)$m['id'] ?>">Dr(a). <?= htmlspecialchars($m['nombres'].' '.$m['apellidos']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label required">Día de la semana</label>
                    <select name="dia_semana" class="form-select" style="height: 44px;" required>
                        <option value="">Seleccionar</option>
                        <option value="1">Lunes</option>
                        <option value="2">Martes</option>
                        <option value="3">Miércoles</option>
                        <option value="4">Jueves</option>
                        <option value="5">Viernes</option>
                        <option value="6">Sábado</option>
                        <option value="7">Domingo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">Intervalo de cita (minutos)</label>
                    <select name="intervalo_minutos" class="form-select" style="height: 44px;" required>
                        <option value="15">15 minutos</option>
                        <option value="20">20 minutos</option>
                        <option value="30" selected>30 minutos</option>
                        <option value="45">45 minutos</option>
                        <option value="60">60 minutos</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label required">Hora de inicio</label>
                    <input type="time" name="hora_inicio" class="form-input" style="height: 44px;" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">Hora de finalización</label>
                    <input type="time" name="hora_fin" class="form-input" style="height: 44px;" required>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--color-border);">
                <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">Guardar horario</button>
                <a href="horarios_moderno.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
