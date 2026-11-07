<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_moderno.php');
    exit;
}

$usuario = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
$contrasena = is_string($_POST['contrasena'] ?? null) ? $_POST['contrasena'] : '';
$csrfToken = $_POST['csrf_token'] ?? '';
$ahora = time();
$bloqueadoHasta = (int) ($_SESSION['login_bloqueado_hasta'] ?? 0);

if ($bloqueadoHasta > $ahora) {
    header('Location: login_moderno.php?error=bloqueado');
    exit;
}

if ($bloqueadoHasta > 0) {
    unset($_SESSION['login_bloqueado_hasta'], $_SESSION['login_intentos']);
}

if (!is_string($csrfToken) || !isset($_SESSION['login_csrf']) || !hash_equals($_SESSION['login_csrf'], $csrfToken)) {
    header('Location: login_moderno.php?error=sesion');
    exit;
}

$_SESSION['login_usuario_intento'] = $usuario;

$registrarFallo = static function (): void {
    $intentos = (int) ($_SESSION['login_intentos'] ?? 0) + 1;
    $_SESSION['login_intentos'] = $intentos;

    if ($intentos >= 5) {
        $_SESSION['login_bloqueado_hasta'] = time() + 300;
        header('Location: login_moderno.php?error=bloqueado');
        exit;
    }

    header('Location: login_moderno.php?error=credenciales');
    exit;
};

if ($usuario === '' || $contrasena === '') {
    $registrarFallo();
}

try {
    $pdo = conectarBD();

    $sql = "SELECT id, rol_id, nombre,
                   contrasena_hash
            FROM usuarios
            WHERE (nombre_usuario = :usuario OR lower(correo) = lower(:correo))
              AND activo = TRUE
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'usuario' => $usuario,
        'correo' => $usuario
    ]);

    $datos = $stmt->fetch();

    if (
        !$datos ||
        !password_verify(
            $contrasena,
            $datos['contrasena_hash']
        )
    ) {
        $registrarFallo();
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $datos['id'];
    $_SESSION['rol_id'] = $datos['rol_id'];
    $_SESSION['nombre'] = $datos['nombre'];
    unset(
        $_SESSION['login_intentos'],
        $_SESSION['login_bloqueado_hasta'],
        $_SESSION['login_usuario_intento'],
        $_SESSION['login_csrf']
    );

    header('Location: panel_moderno.php');
    exit;

} catch (Throwable $e) {
    error_log($e->getMessage());
    header('Location: login_moderno.php?error=servidor');
    exit;
}
