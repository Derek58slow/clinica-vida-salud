
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

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Identificador de paciente no válido.');
}

try {
    $pdo = conectarBD();

    $stmt = $pdo->prepare(
        "SELECT * FROM pacientes WHERE id = :id"
    );
    $stmt->execute(['id' => $id]);

    $paciente = $stmt->fetch();

    if (!$paciente) {
        http_response_code(404);
        exit('Paciente no encontrado.');
    }

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo consultar el paciente.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($valor): string {
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar paciente - Clínica Vida Salud</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f9;
            padding: 25px;
        }
        main {
            max-width: 650px;
            margin: auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
        }
        h1 { color: #176b78; }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, select, textarea {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 5px;
        }
        button {
            margin-top: 20px;
            padding: 12px 20px;
            background: #176b78;
            color: white;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }
        .error { color: #b42318; }
    </style>
</head>
<body>
<main>
    <h1>Editar paciente</h1>
    <a href="pacientes.php">← Volver al listado</a>

    <?php if (isset($_GET['error'])): ?>
        <p class="error">No se pudieron guardar los cambios. Revisa los datos.</p>
    <?php endif; ?>

    <form action="actualizar_paciente.php" method="POST">
        <input type="hidden" name="id" value="<?= (int) $paciente['id'] ?>">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <label>Nombres *</label>
        <input type="text" name="nombres" required
               maxlength="120"
               value="<?= e($paciente['nombres']) ?>">

        <label>Apellidos *</label>
        <input type="text" name="apellidos" required
               maxlength="120"
               value="<?= e($paciente['apellidos']) ?>">

        <label>Documento de identidad</label>
        <input type="text" name="documento_identidad"
               maxlength="30"
               value="<?= e($paciente['documento_identidad']) ?>">

        <label>Fecha de nacimiento</label>
        <input type="date" name="fecha_nacimiento"
               max="<?= date('Y-m-d') ?>"
               value="<?= e($paciente['fecha_nacimiento']) ?>">

        <label>Sexo</label>
        <select name="sexo">
            <?php
            $opciones = [
                '' => 'Seleccionar',
                'masculino' => 'Masculino',
                'femenino' => 'Femenino',
                'otro' => 'Otro',
                'no_especificado' => 'No especificado'
            ];

            foreach ($opciones as $valor => $texto):
            ?>
                <option value="<?= e($valor) ?>"
                    <?= ($paciente['sexo'] ?? '') === $valor ? 'selected' : '' ?>>
                    <?= e($texto) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Teléfono</label>
        <input type="text" name="telefono" maxlength="30"
               value="<?= e($paciente['telefono']) ?>">

        <label>Correo</label>
        <input type="email" name="correo" maxlength="150"
               value="<?= e($paciente['correo']) ?>">

        <label>Dirección</label>
        <textarea name="direccion" rows="3"><?= e($paciente['direccion']) ?></textarea>

        <button type="submit">Guardar cambios</button>
    </form>
</main>
</body>
</html>
