<?php
require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([4]);
require_once __DIR__ . '/../config/database.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$paciente = null;
$error = false;
try {
    $pdo = conectarBD();
    $paciente = obtenerPacienteSesion($pdo);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = true;
}
require_once __DIR__ . '/includes/layout.php';
renderSidebar('paciente_perfil');
?>
<div class="doctor-page-heading"><div class="section-title"><span class="page-emblem lilac"><?= icono('perfil') ?></span><div><span class="doctor-eyebrow">Información personal</span><h1>Mi perfil</h1><p>Consulta tus datos y mantén actualizada tu información de contacto.</p></div></div><a class="btn btn-secondary" href="mis_citas.php"><?= icono('agenda') ?> Mis citas</a></div>
<?php if ($error): ?><div class="alert alert-error">No se pudo cargar tu perfil. Inténtalo nuevamente.</div>
<?php elseif (!$paciente): ?><div class="alert alert-warning">Tu cuenta no está vinculada con una ficha de paciente activa.</div>
<?php else: ?>
<?php if (($_GET['actualizado'] ?? '') === '1'): ?><div class="alert alert-success" role="status">Tus datos de contacto se actualizaron correctamente.</div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="alert alert-error" role="alert"><?= ($_GET['error'] === 'correo') ? 'Ingresa un correo electrónico válido.' : (($_GET['error'] === 'datos') ? 'Revisa los datos ingresados.' : 'No se pudo actualizar el perfil.') ?></div><?php endif; ?>
<div class="patient-profile-layout">
    <section class="card"><div class="doctor-profile-cover patient-profile-cover"><span class="profile-cover-art"><?= icono('pacientes') ?></span><span class="doctor-profile-avatar"><?= icono('pacientes') ?></span><span class="profile-cover-label">Paciente Vida Salud</span></div><div class="card-body doctor-profile-body"><span class="doctor-eyebrow">Ficha activa</span><h2><?= h($paciente['nombres'].' '.$paciente['apellidos']) ?></h2><p>Datos de identificación protegidos por la clínica</p><div class="profile-data"><?php foreach (['documento_identidad'=>'Documento de identidad','fecha_nacimiento'=>'Fecha de nacimiento','sexo'=>'Sexo'] as $campo=>$etiqueta): ?><div><span><?= $etiqueta ?></span><strong><?= h($paciente[$campo] ? ($campo === 'fecha_nacimiento' ? date('d/m/Y', strtotime($paciente[$campo])) : ucfirst(str_replace('_', ' ', $paciente[$campo]))) : 'No registrado') ?></strong></div><?php endforeach; ?></div><div class="alert alert-info profile-note"><?= icono('escudo') ?><div><strong>Identidad protegida</strong><p>Solicita a recepción cualquier corrección de nombre, documento o fecha de nacimiento.</p></div></div></div></section>
    <section class="card" id="editar-contacto"><div class="card-header"><div class="section-title"><span class="section-icon green"><?= icono('editar') ?></span><div><h2 class="card-title">Datos de contacto</h2><p class="card-subtitle">La clínica utilizará estos datos para comunicarse contigo</p></div></div></div><div class="card-body"><form method="POST" action="paciente_actualizar_perfil.php" class="stack"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><div class="form-grid"><div class="form-group"><label class="form-label" for="paciente-correo">Correo electrónico</label><input class="form-input" id="paciente-correo" type="email" name="correo" maxlength="255" value="<?= h($paciente['correo'] ?? '') ?>" autocomplete="email" placeholder="nombre@correo.com"></div><div class="form-group"><label class="form-label" for="paciente-telefono">Teléfono</label><input class="form-input" id="paciente-telefono" type="tel" name="telefono" maxlength="30" value="<?= h($paciente['telefono'] ?? '') ?>" autocomplete="tel" pattern="[0-9+() .-]{6,30}" placeholder="Ej.: 76543210"></div><div class="form-group form-span-2"><label class="form-label" for="paciente-direccion">Dirección</label><textarea class="form-textarea" id="paciente-direccion" name="direccion" maxlength="500" rows="4" placeholder="Zona, calle y número de domicilio"><?= h($paciente['direccion'] ?? '') ?></textarea></div></div><div class="profile-edit-actions"><button class="btn btn-primary" type="submit"><?= icono('atendida') ?> Guardar cambios</button><span><?= icono('escudo') ?> Tus datos se envían de forma segura.</span></div></form></div></section>
</div>
<?php endif; renderFooter(); ?>
