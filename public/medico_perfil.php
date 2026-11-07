<?php
require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([3]);
require_once __DIR__ . '/../config/database.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$medico = null;
$horarios = $bloqueos = [];
$error = false;
try {
    $pdo = conectarBD();
    $medico = obtenerMedicoSesion($pdo);
    if ($medico) {
        $stmt = $pdo->prepare('SELECT * FROM horarios_atencion WHERE medico_id=:m AND activo=TRUE ORDER BY dia_semana,hora_inicio');
        $stmt->execute(['m'=>$medico['id']]); $horarios=$stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT inicio,fin,motivo FROM bloqueos_horario WHERE medico_id=:m AND fin>=CURRENT_TIMESTAMP ORDER BY inicio LIMIT 20');
        $stmt->execute(['m'=>$medico['id']]); $bloqueos=$stmt->fetchAll();
    }
} catch (Throwable $e) { error_log($e->getMessage()); $error=true; }
$dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
require_once __DIR__ . '/includes/layout.php'; renderSidebar('medico_perfil');
?>
<div class="doctor-page-heading"><div class="section-title"><span class="page-emblem lilac"><?= icono('perfil') ?></span><div><span class="doctor-eyebrow">Información profesional</span><h1>Mi perfil y horarios</h1><p>Tu información, disponibilidad y ausencias programadas.</p></div></div><a class="btn btn-primary" href="agenda.php"><?= icono('agenda') ?> Ver mi agenda</a></div>
<?php if ($error): ?><div class="alert alert-error">No se pudo cargar tu perfil. Inténtalo nuevamente.</div>
<?php elseif (!$medico): ?><div class="alert alert-warning">Tu cuenta no está vinculada con un perfil médico activo.</div>
<?php else: ?>
<?php if (($_GET['actualizado'] ?? '') === '1'): ?><div class="alert alert-success" role="status">Tu información de contacto se actualizó correctamente.</div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="alert alert-error" role="alert"><?= ($_GET['error'] === 'correo') ? 'Ingresa un correo electrónico válido.' : (($_GET['error'] === 'datos') ? 'Revisa los datos ingresados e inténtalo nuevamente.' : 'No se pudo actualizar el perfil. Inténtalo nuevamente.') ?></div><?php endif; ?>
<div class="doctor-profile-grid">
    <section class="card"><div class="doctor-profile-cover"><span class="profile-cover-art"><?= icono('medico') ?></span><span class="doctor-profile-avatar"><?= icono('medico') ?></span><span class="profile-cover-label">Clínica Vida Salud</span></div>
        <div class="card-body doctor-profile-body"><span class="doctor-eyebrow">Perfil profesional activo</span><h2>Dr(a). <?= h($medico['nombres'].' '.$medico['apellidos']) ?></h2><p><?= h($medico['especialidades'] ?: 'Sin especialidad asignada') ?></p>
            <div class="profile-data"><?php foreach (['numero_licencia'=>'Licencia profesional','consultorio'=>'Consultorio','sede'=>'Sede','correo'=>'Correo','telefono'=>'Teléfono'] as $campo=>$etiqueta): ?><div><span><?= $etiqueta ?></span><strong><?= h($medico[$campo] ?: 'No registrado') ?></strong></div><?php endforeach; ?></div>
            <div class="alert alert-info profile-note"><?= icono('escudo') ?><div><strong>Información profesional protegida</strong><p>Administración controla tu licencia, especialidades, sede, horarios y ausencias.</p></div></div>
        </div>
    </section>
    <div class="stack">
        <section class="card" id="editar-contacto"><div class="card-header"><div class="section-title"><span class="section-icon green"><?= icono('editar') ?></span><div><h2 class="card-title">Datos de contacto</h2><p class="card-subtitle">Mantén actualizada la información con la que la clínica puede contactarte</p></div></div></div><div class="card-body">
            <form method="POST" action="medico_actualizar_perfil.php" class="stack">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                <div class="form-grid doctor-contact-form">
                    <div class="form-group"><label class="form-label" for="medico-correo">Correo electrónico</label><input class="form-input" id="medico-correo" type="email" name="correo" maxlength="255" value="<?= h($medico['correo'] ?? '') ?>" placeholder="nombre@correo.com" autocomplete="email"><small class="form-hint">Se utilizará para comunicaciones de la clínica.</small></div>
                    <div class="form-group"><label class="form-label" for="medico-telefono">Teléfono</label><input class="form-input" id="medico-telefono" type="tel" name="telefono" maxlength="30" value="<?= h($medico['telefono'] ?? '') ?>" placeholder="Ej.: 76543210" autocomplete="tel" pattern="[0-9+() .-]{6,30}"><small class="form-hint">Puedes incluir código de país, espacios o guiones.</small></div>
                    <div class="form-group form-span-2"><label class="form-label" for="medico-consultorio">Consultorio</label><input class="form-input" id="medico-consultorio" name="consultorio" maxlength="100" value="<?= h($medico['consultorio'] ?? '') ?>" placeholder="Ej.: Consultorio 204, segundo piso"><small class="form-hint">Indica dónde deben presentarse tus pacientes.</small></div>
                </div>
                <div class="profile-edit-actions"><button class="btn btn-primary" type="submit"><?= icono('atendida') ?> Guardar cambios</button><span><?= icono('escudo') ?> Solo puedes modificar estos tres datos.</span></div>
            </form>
        </div></section>
        <section class="card"><div class="card-header"><div class="section-title"><span class="section-icon blue"><?= icono('agenda') ?></span><div><h2 class="card-title">Horario de atención</h2><p class="card-subtitle">Disponibilidad semanal · <?= h(date_default_timezone_get()) ?></p></div></div></div><div class="card-body">
            <?php if (!$horarios): ?><div class="empty-state">No tienes horarios activos. Administración debe configurar tu disponibilidad.</div><?php else: ?><div class="schedule-list"><?php foreach ($horarios as $horario): ?><div><span class="schedule-day"><?= $dias[(int)$horario['dia_semana']] ?></span><strong><?= substr($horario['hora_inicio'],0,5) ?> – <?= substr($horario['hora_fin'],0,5) ?></strong><small><?= (int)$horario['intervalo_minutos'] ?> min por cita</small></div><?php endforeach; ?></div><?php endif; ?>
        </div></section>
        <section class="card"><div class="card-header"><div class="section-title"><span class="section-icon coral"><?= icono('ausencia') ?></span><div><h2 class="card-title">Ausencias y bloqueos</h2><p class="card-subtitle">Próximos periodos sin disponibilidad</p></div></div></div><div class="card-body">
            <?php if (!$bloqueos): ?><p class="muted">No tienes bloqueos próximos registrados.</p><?php else: ?><div class="activity-list"><?php foreach ($bloqueos as $bloqueo): ?><article><strong><?= date('d/m/Y H:i',strtotime($bloqueo['inicio'])) ?> – <?= date('d/m/Y H:i',strtotime($bloqueo['fin'])) ?></strong><p><?= h($bloqueo['motivo'] ?: 'Sin motivo indicado') ?></p></article><?php endforeach; ?></div><?php endif; ?>
        </div></section>
    </div>
</div>
<?php endif; renderFooter(); ?>
