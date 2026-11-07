<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$rol = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rol, [1, 2], true)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registrar_paciente.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Formulario no válido. Vuelve a abrirlo.');
}

require_once __DIR__ . '/../config/database.php';

function opcional(string $campo): ?string {
    $valor = trim((string) ($_POST[$campo] ?? ''));
    return $valor === '' ? null : $valor;
}

$nombres = trim((string) ($_POST['nombres'] ?? ''));
$apellidos = trim((string) ($_POST['apellidos'] ?? ''));
$documento = opcional('documento_identidad');
$fecha = opcional('fecha_nacimiento');
$sexo = opcional('sexo');

$sexosPermitidos = [
    'masculino',
    'femenino',
    'otro',
    'no_especificado'
];

if ($sexo !== null && !in_array($sexo, $sexosPermitidos, true)) {
    header('Location: registrar_paciente.php?error=datos');
    exit;
}

$telefono = opcional('telefono');
$correo = opcional('correo');
$direccion = opcional('direccion');

if ($nombres === '' || $apellidos === '') {
    header('Location: registrar_paciente.php?error=datos');
    exit;
}

if (
    ($correo !== null && !filter_var($correo, FILTER_VALIDATE_EMAIL)) ||
    ($fecha !== null && (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ||
        !checkdate(
            (int) substr($fecha, 5, 2),
            (int) substr($fecha, 8, 2),
            (int) substr($fecha, 0, 4)
        ) ||
        $fecha > date('Y-m-d')
    ))
) {
    header('Location: registrar_paciente.php?error=datos');
    exit;
}

try {
    $pdo = conectarBD();

    // Evitar documentos duplicados.
    if ($documento !== null) {
        $verificar = $pdo->prepare(
            "SELECT id FROM pacientes
             WHERE documento_identidad = :documento
             LIMIT 1"
        );

        $verificar->execute([
            'documento' => $documento
        ]);

        if ($verificar->fetch()) {
            header('Location: registrar_paciente.php?error=duplicado');
            exit;
        }
    }

    $sql = "INSERT INTO pacientes (
                nombres,
                apellidos,
                documento_identidad,
                fecha_nacimiento,
                sexo,
                telefono,
                correo,
                direccion
            ) VALUES (
                :nombres,
                :apellidos,
                :documento,
                :fecha,
                :sexo,
                :telefono,
                :correo,
                :direccion
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'documento' => $documento,
        'fecha' => $fecha,
        'sexo' => $sexo,
        'telefono' => $telefono,
        'correo' => $correo,
        'direccion' => $direccion
    ]);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: pacientes.php?registrado=1');
    exit;

} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: registrar_paciente.php?error=servidor');
    exit;
}
