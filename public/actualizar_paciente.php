<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if (!in_array((int) ($_SESSION['rol_id'] ?? 0), [1, 2], true)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pacientes.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

require_once __DIR__ . '/../config/database.php';

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Paciente no válido.');
}

function opcional(string $campo): ?string {
    $valor = trim((string) ($_POST[$campo] ?? ''));
    return $valor === '' ? null : $valor;
}

$nombres = trim((string) ($_POST['nombres'] ?? ''));
$apellidos = trim((string) ($_POST['apellidos'] ?? ''));
$documento = opcional('documento_identidad');
$fecha = opcional('fecha_nacimiento');
$sexo = opcional('sexo');
$telefono = opcional('telefono');
$correo = opcional('correo');
$direccion = opcional('direccion');

$sexosPermitidos = [
    'masculino',
    'femenino',
    'otro',
    'no_especificado'
];

$fechaValida = $fecha === null || (
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) &&
    checkdate(
        (int) substr($fecha, 5, 2),
        (int) substr($fecha, 8, 2),
        (int) substr($fecha, 0, 4)
    ) &&
    $fecha <= date('Y-m-d')
);

if (
    $nombres === '' ||
    $apellidos === '' ||
    !$fechaValida ||
    ($sexo !== null && !in_array($sexo, $sexosPermitidos, true)) ||
    ($correo !== null && !filter_var($correo, FILTER_VALIDATE_EMAIL))
) {
    header('Location: editar_paciente.php?id=' . $id . '&error=datos');
    exit;
}

try {
    $pdo = conectarBD();

    // Verificar si el documento pertenece a otro paciente.
    if ($documento !== null) {
        $verificar = $pdo->prepare(
            "SELECT id FROM pacientes
             WHERE documento_identidad = :documento
               AND id <> :id
             LIMIT 1"
        );

        $verificar->execute([
            'documento' => $documento,
            'id' => $id
        ]);

        if ($verificar->fetch()) {
            header('Location: editar_paciente.php?id=' . $id . '&error=duplicado');
            exit;
        }
    }

    $sql = "UPDATE pacientes
            SET nombres = :nombres,
                apellidos = :apellidos,
                documento_identidad = :documento,
                fecha_nacimiento = :fecha,
                sexo = :sexo,
                telefono = :telefono,
                correo = :correo,
                direccion = :direccion,
                updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'documento' => $documento,
        'fecha' => $fecha,
        'sexo' => $sexo,
        'telefono' => $telefono,
        'correo' => $correo,
        'direccion' => $direccion,
        'id' => $id
    ]);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: pacientes.php?actualizado=1');
    exit;

} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: editar_paciente.php?id=' . $id . '&error=servidor');
    exit;
}
