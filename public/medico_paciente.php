<?php
require_once __DIR__ . '/includes/panel_helpers.php';
exigirRol([3]);
require_once __DIR__ . '/../config/database.php';
$pacienteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pacienteId) { header('Location: medico_pacientes.php'); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$medico=$paciente=$info=null;
$contactos=$citas=[];
$error=null;
$pagina=max(1,(int)($_GET['pagina']??1));
$total=0;
try {
    $pdo=conectarBD(); $medico=obtenerMedicoSesion($pdo);
    if ($medico) {
        $stmt=$pdo->prepare('SELECT p.* FROM pacientes p WHERE p.id=:p AND EXISTS(SELECT 1 FROM citas c WHERE c.paciente_id=p.id AND c.medico_id=:m)');
        $stmt->execute(['p'=>$pacienteId,'m'=>$medico['id']]); $paciente=$stmt->fetch() ?: null;
        if (!$paciente) { http_response_code(403); $error='No tienes una relación de atención con este paciente.'; }
        else {
            $stmt=$pdo->prepare('SELECT * FROM informacion_medica_paciente WHERE paciente_id=:p'); $stmt->execute(['p'=>$pacienteId]); $info=$stmt->fetch() ?: null;
            $stmt=$pdo->prepare('SELECT nombre,parentesco,telefono FROM contactos_emergencia WHERE paciente_id=:p ORDER BY id'); $stmt->execute(['p'=>$pacienteId]); $contactos=$stmt->fetchAll();
            $stmt=$pdo->prepare('SELECT COUNT(*) FROM citas c WHERE c.paciente_id=:p AND c.medico_id=:m'); $stmt->execute(['p'=>$pacienteId,'m'=>$medico['id']]); $total=(int)$stmt->fetchColumn();
            $pagina=min($pagina,max(1,(int)ceil($total/10))); $offset=($pagina-1)*10;
            $stmt=$pdo->prepare("SELECT c.id,c.codigo_cita,c.inicio,c.estado,c.motivo,e.nombre especialidad FROM citas c JOIN especialidades e ON e.id=c.especialidad_id WHERE c.paciente_id=:p AND c.medico_id=:m ORDER BY c.inicio DESC,c.id DESC LIMIT 10 OFFSET $offset");
            $stmt->execute(['p'=>$pacienteId,'m'=>$medico['id']]); $citas=$stmt->fetchAll();
        }
    }
} catch (Throwable $e) { error_log($e->getMessage()); $error='No se pudo cargar la ficha del paciente. Inténtalo nuevamente.'; }
require_once __DIR__.'/includes/layout.php'; renderSidebar('medico_pacientes');
?>
<div class="doctor-page-heading"><div class="section-title"><span class="page-emblem coral"><?= icono('pacientes') ?></span><div><span class="doctor-eyebrow">Ficha del paciente</span><h1><?= $paciente ? h($paciente['nombres'].' '.$paciente['apellidos']) : 'Ficha relacionada' ?></h1><p>Información para las atenciones asignadas a tu perfil.</p></div></div><a class="btn btn-secondary" href="medico_pacientes.php"><?= icono('volver') ?> Mis pacientes</a></div>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div>
<?php elseif (!$medico): ?><div class="alert alert-warning">Tu cuenta no tiene un perfil médico activo.</div>
<?php elseif ($paciente): ?>
<?php if (!$paciente['activo']): ?><div class="alert alert-warning">Ficha inactiva. Se conserva su información para consulta histórica; la edición está deshabilitada.</div><?php endif; ?>
<?php if (isset($_GET['guardado'])): ?><div class="alert alert-success" role="status">Información clínica actualizada correctamente.</div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="alert alert-error" role="alert"><?= h(['datos'=>'Revisa los valores y la longitud de los campos.','conflicto'=>'Otro profesional actualizó esta información. Revisa los datos actuales antes de volver a guardar.','servidor'=>'No se pudo guardar la información. Inténtalo nuevamente.'][$_GET['error']] ?? 'No se pudo actualizar la ficha.') ?></div><?php endif; ?>
<div class="patient-summary-grid">
    <section class="card"><div class="card-header"><div class="section-title"><span class="section-icon blue"><?= icono('perfil') ?></span><h2 class="card-title">Datos del paciente</h2></div></div><div class="card-body profile-data patient-data">
        <?php foreach (['documento_identidad'=>'Documento','fecha_nacimiento'=>'Fecha de nacimiento','sexo'=>'Sexo','telefono'=>'Teléfono','correo'=>'Correo'] as $campo=>$nombre): ?><div><span><?= $nombre ?></span><strong><?= h($paciente[$campo] ? ($campo==='fecha_nacimiento' ? date('d/m/Y',strtotime($paciente[$campo])) : ucfirst(str_replace('_',' ',$paciente[$campo]))) : 'No registrado') ?></strong></div><?php endforeach; ?>
    </div></section>
    <section class="card medical-info-card"><div class="card-header"><div class="section-title"><span class="section-icon coral"><?= icono('medico') ?></span><div><h2 class="card-title">Información clínica</h2><p class="card-subtitle"><?= $info ? 'Actualizada el '.date('d/m/Y H:i',strtotime($info['updated_at'])) : 'Aún no se ha registrado información clínica' ?></p></div></div><?php if($paciente['activo']): ?><a class="btn btn-sm btn-secondary" href="#editar-informacion"><?= icono('editar') ?> Actualizar</a><?php endif; ?></div><div class="card-body profile-data patient-data">
        <?php foreach (['tipo_sangre'=>'Tipo de sangre','alergias'=>'Alergias','enfermedades_cronicas'=>'Enfermedades crónicas','medicamentos'=>'Medicamentos','antecedentes'=>'Antecedentes'] as $campo=>$nombre): ?><div><span><?= $nombre ?></span><strong><?= nl2br(h(($info[$campo]??'') ?: 'No registrado')) ?></strong></div><?php endforeach; ?>
    </div></section>
</div>
<?php if ($contactos): ?><section class="card"><div class="card-header"><h2 class="card-title">Contactos de emergencia</h2></div><div class="card-body contact-list"><?php foreach ($contactos as $contacto): ?><div><span class="patient-avatar small"><?= icono('pacientes') ?></span><span><strong><?= h($contacto['nombre']) ?></strong><small><?= h($contacto['parentesco'] ?: 'Contacto') ?> · <?= h($contacto['telefono']) ?></small></span></div><?php endforeach; ?></div></section><?php endif; ?>
<?php if ($paciente['activo']): ?>
<details class="card clinical-editor" id="editar-informacion"><summary><?= icono('editar') ?><span><strong>Actualizar información clínica</strong><small>Alergias, antecedentes y datos relevantes para la atención</small></span></summary><div class="card-body"><p class="form-hint">Un campo vacío significa “no registrado”. Escribe “Sin alergias conocidas” únicamente si lo confirmaste con el paciente. Los cambios quedan asociados a tu usuario.</p>
    <form method="POST" action="medico_guardar_informacion.php" class="stack"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="paciente_id" value="<?= $pacienteId ?>"><input type="hidden" name="version" value="<?= h($info['updated_at']??'') ?>">
        <div class="form-group"><label class="form-label" for="tipo_sangre">Tipo de sangre</label><select class="form-select" name="tipo_sangre" id="tipo_sangre"><option value="">No registrado</option><?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $tipo): ?><option <?= ($info['tipo_sangre']??'')===$tipo?'selected':'' ?>><?= $tipo ?></option><?php endforeach; ?></select></div>
        <div class="form-grid"><?php foreach (['alergias'=>'Alergias','enfermedades_cronicas'=>'Enfermedades crónicas','medicamentos'=>'Medicamentos actuales','antecedentes'=>'Antecedentes relevantes'] as $campo=>$nombre): ?><div class="form-group"><label class="form-label" for="<?= $campo ?>"><?= $nombre ?></label><textarea id="<?= $campo ?>" name="<?= $campo ?>" class="form-textarea" rows="3" maxlength="4000"><?= h($info[$campo]??'') ?></textarea></div><?php endforeach; ?></div>
        <div class="action-row"><button class="btn btn-primary" type="submit"><?= icono('atendida') ?> Guardar información</button><span class="form-hint">No modifica los datos personales ni el estado de las citas.</span></div>
    </form>
</div></details>
<?php endif; ?>
<section class="card"><div class="card-header"><div><h2 class="card-title">Citas conmigo</h2><p class="card-subtitle"><?= $total ?> cita(s). Solo se muestran las relacionadas con tu perfil.</p></div></div><div class="card-body flush"><div class="table-container"><table class="table"><thead><tr><th>Fecha</th><th>Especialidad</th><th>Motivo</th><th>Estado</th><th>Detalle</th></tr></thead><tbody><?php foreach ($citas as $cita): ?><tr><td><?= date('d/m/Y H:i',strtotime($cita['inicio'])) ?></td><td><?= h($cita['especialidad']) ?></td><td><?= h($cita['motivo'] ?: 'Sin motivo') ?></td><td><span class="badge badge-<?= estadoBadge($cita['estado']) ?>"><?= nombreEstado($cita['estado']) ?></span></td><td><a class="btn btn-sm btn-secondary" href="ver_cita.php?id=<?= (int)$cita['id'] ?>&amp;fecha=<?= date('Y-m-d',strtotime($cita['inicio'])) ?>">Ver cita</a></td></tr><?php endforeach; ?></tbody></table></div></div></section>
<?php if ($total>10): ?><nav class="pagination" aria-label="Páginas de citas"><?php if($pagina>1): ?><a class="btn btn-secondary" href="?id=<?= $pacienteId ?>&amp;pagina=<?= $pagina-1 ?>">← Anterior</a><?php endif; ?><span>Página <?= $pagina ?> de <?= (int)ceil($total/10) ?></span><?php if($pagina*10<$total): ?><a class="btn btn-secondary" href="?id=<?= $pacienteId ?>&amp;pagina=<?= $pagina+1 ?>">Siguiente →</a><?php endif; ?></nav><?php endif; ?>
<?php endif; renderFooter(); ?>
