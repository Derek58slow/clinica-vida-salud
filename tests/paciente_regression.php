<?php
$base = dirname(__DIR__) . '/public/';
$archivos = [
    'paciente_perfil.php' => ['exigirRol([4])', 'paciente_actualizar_perfil.php', 'csrf_token'],
    'paciente_actualizar_perfil.php' => ['exigirRol([4])', 'hash_equals', 'exigirPacienteActivo', 'usuario_id=:usuario_id'],
    'cancelar_cita.php' => ['[1, 2, 4]', "usuario_id=:usuario_id", "paciente_id"],
    'reprogramar_cita.php' => ['[1, 2, 4]', "usuario_id=:usuario_id", "paciente_id"],
    'ver_cita.php' => ['[1,2,4]', "pacientePropio"],
    'nueva_cita.php' => ['[1, 2, 4]', 'usuario_id', 'booking-steps'],
    'guardar_cita.php' => ['[1, 2, 4]', "usuario_id=:usuario_id", "pacienteId = (int)\$pacientePropio['id']"],
    'mis_citas.php' => ['obtenerPacienteSesion', 'patient-appointment-list', 'patient-history-filters'],
    'comprobante_cita.php' => ['exigirRol([1,2,3,4])', 'paciente_usuario', 'application/pdf'],
    'notificaciones.php' => ['notificacion_id', 'sin_leer', 'usuario_id=:u'],
];
foreach ($archivos as $archivo => $fragmentos) {
    $contenido = file_get_contents($base . $archivo);
    if ($contenido === false) { fwrite(STDERR, "No se pudo leer $archivo\n"); exit(1); }
    foreach ($fragmentos as $fragmento) {
        if (!str_contains($contenido, $fragmento)) { fwrite(STDERR, "Falta protección esperada en $archivo: $fragmento\n"); exit(1); }
    }
}
echo "Comprobaciones de la vista paciente: OK\n";
