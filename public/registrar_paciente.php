
<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$rol = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rol, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para registrar pacientes.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


function escapar($valor): string {
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

$errores = [
    'datos' => 'Revisa los datos del formulario.',
    'duplicado' => 'Ya existe un paciente con ese documento de identidad.',
    'servidor' => 'No se pudo guardar el paciente. Inténtalo nuevamente.'
];

$mensaje = $errores[$_GET['error'] ?? ''] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar paciente - Clínica Vida Salud</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f9;
            padding: 25px;
        }
        .contenedor {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }
        h1 { color: #176b78; }
        label {
            display: block;
            margin-top: 14px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }
        button {
            background: #176b78;
            color: white;
            border: none;
            padding: 12px 20px;
            margin-top: 20px;
            cursor: pointer;
            border-radius: 6px;
        }
        .error { color: #b42318; }
    </style>
</head>
<body>
    <main class="contenedor">
        <h1>Registrar paciente</h1>
        <p>Clínica Vida Salud</p>

        <a href="pacientes.php">← Volver al listado</a>

        <?php if ($mensaje !== ''): ?>
            <p class="error"><?= escapar($mensaje) ?></p>
        <?php endif; ?>

        <form action="guardar_paciente.php" method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapar($_SESSION['csrf_token'] ?? '') ?>"
            >

            <label>Nombres *</label>
            <input type="text" name="nombres"
                   maxlength="120" required>

            <label>Apellidos *</label>
            <input type="text" name="apellidos"
                   maxlength="120" required>

            <label>Documento de identidad</label>
            <input type="text" name="documento_identidad"
                   maxlength="30">

            <label>Fecha de nacimiento</label>
            <input type="date" name="fecha_nacimiento"
                   max="<?= date('Y-m-d') ?>">

            
            <label>Sexo</label>
            <select name="sexo">
                <option value="">Seleccionar</option>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
                <option value="otro">Otro</option>
                <option value="no_especificado">No especificado</option>
            </select>


            <label>Teléfono</label>
            <input type="text" name="telefono"
                   maxlength="30">

            <label>Correo electrónico</label>
            <input type="email" name="correo"
                   maxlength="150">

            <label>Dirección</label>
            <textarea name="direccion" rows="3"></textarea>

            <button type="submit">Guardar paciente</button>
        </form>
    </main>
</body>
</html>
