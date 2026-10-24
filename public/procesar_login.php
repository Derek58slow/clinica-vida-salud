
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if ($usuario === '' || $contrasena === '') {
    header('Location: login.php?error=1');
    exit;
}

try {
    $pdo = conectarBD();

    $sql = "SELECT id, rol_id, nombre,
                   contrasena_hash
            FROM usuarios
            WHERE nombre_usuario = :usuario
              AND activo = TRUE
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'usuario' => $usuario
    ]);

    $datos = $stmt->fetch();

    if (
        !$datos ||
        !password_verify(
            $contrasena,
            $datos['contrasena_hash']
        )
    ) {
        header('Location: login.php?error=1');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $datos['id'];
    $_SESSION['rol_id'] = $datos['rol_id'];
    $_SESSION['nombre'] = $datos['nombre'];

    header('Location: panel.php');
    exit;

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'Ocurrió un error al iniciar sesión.';
}
