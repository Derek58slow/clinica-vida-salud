<?php
declare(strict_types=1);

$raiz = dirname(__DIR__);
$leer = static function (string $archivo) use ($raiz): string {
    $contenido = file_get_contents($raiz . '/' . $archivo);
    if ($contenido === false) throw new RuntimeException("No se pudo leer $archivo");
    return $contenido;
};
$verificar = static function (bool $condicion, string $mensaje): void {
    if (!$condicion) throw new RuntimeException($mensaje);
};

$router = $leer('public/panel_moderno.php');
$layout = $leer('public/includes/layout.php');
$helpers = $leer('public/includes/panel_helpers.php');
$medico = $leer('public/panel_medico.php');
$medicoPacientes = $leer('public/medico_pacientes.php');
$medicoPaciente = $leer('public/medico_paciente.php');
$paciente = $leer('public/panel_paciente.php');
$misCitas = $leer('public/mis_citas.php');
$detalle = $leer('public/ver_cita.php');

foreach ([1=>'panel_administrador.php',2=>'panel_recepcionista.php',3=>'panel_medico.php',4=>'panel_paciente.php'] as $rol=>$panel) {
    $verificar(str_contains($router, "$rol=>'$panel'"), "Falta el panel del rol $rol en el enrutador.");
    $verificar(str_contains($layout, "$rol => '$panel'"), "Falta el enlace del rol $rol en el menú.");
}
$verificar(str_contains($medico, 'obtenerMedicoSesion') && str_contains($helpers, 'WHERE m.usuario_id = :usuario_id'), 'El panel médico debe usar el perfil vinculado al usuario.');
$verificar(str_contains($medicoPacientes, 'c.medico_id=:medico'), 'La lista médica debe mostrar solo pacientes relacionados.');
$verificar(str_contains($medicoPaciente, 'c.medico_id=:m'), 'La ficha médica debe impedir el acceso a pacientes ajenos.');
$verificar(str_contains($paciente, 'WHERE usuario_id=:u'), 'El panel paciente debe usar la ficha vinculada al usuario.');
$verificar(str_contains($misCitas, 'WHERE c.paciente_id=:p'), 'Mis citas debe filtrar por el paciente autenticado.');
$verificar(str_contains($detalle, '$rolId === 4') && str_contains($detalle, 'cita["paciente_id"]'), 'El detalle debe impedir que un paciente vea citas ajenas.');

echo "Comprobaciones de vistas por rol: OK" . PHP_EOL;
