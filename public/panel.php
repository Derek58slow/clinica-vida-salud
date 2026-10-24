
<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$nombre = htmlspecialchars(
    $_SESSION['nombre'] ?? 'Usuario',
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel - Clínica Vida Salud</title>
</head>
<body>
    <h1>Clínica Vida Salud</h1>
    <h2>Bienvenido, <?= $nombre ?></h2>
    <p>Has iniciado sesión correctamente.</p>
</body>
</html>
