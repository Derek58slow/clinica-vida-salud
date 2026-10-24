
<?php
session_start();

// Proteger la página.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Solo administrador y recepcionista.
$rol = (int) ($_SESSION['rol_id'] ?? 0);

if (!in_array($rol, [1, 2], true)) {
    http_response_code(403);
    exit('No tienes permiso para ver pacientes.');
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = conectarBD();

    $sql = "SELECT
                id,
                nombres,
                apellidos,
                documento_identidad,
                telefono,
                correo,
                activo
            FROM pacientes
            ORDER BY id DESC";

    $stmt = $pdo->query($sql);
    $pacientes = $stmt->fetchAll();

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo consultar la lista de pacientes.');
}

function escapar($valor): string
{
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes - Clínica Vida Salud</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f9;
            margin: 0;
            padding: 30px;
        }

        .contenedor {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        h1 {
            color: #176b78;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #176b78;
            color: white;
        }

        a {
            color: #176b78;
        }
    </style>
</head>
<body>
    <main class="contenedor">
        <h1>Gestión de pacientes</h1>
        <p>Clínica Vida Salud</p>

        <a href="panel.php">← Volver al panel</a>


    <p>
        <a href="registrar_paciente.php">
            + Nuevo paciente
        </a>
    </p>


        <h2>Pacientes registrados</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre completo</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pacientes)): ?>
                    <tr>
                        <td colspan="7">
                            Todavía no hay pacientes registrados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pacientes as $paciente): ?>
                        <tr>
                            <td><?= escapar($paciente['id']) ?></td>
                            <td>
                                <?= escapar($paciente['nombres']) ?>
                                <?= escapar($paciente['apellidos']) ?>
                            </td>
                            <td><?= escapar($paciente['documento_identidad']) ?></td>
                            <td><?= escapar($paciente['telefono']) ?></td>
                            <td><?= escapar($paciente['correo']) ?></td>
                            <td>
                                <?= $paciente['activo'] ? 'Activo' : 'Inactivo' ?>

                            </td>
                            <td>
                                <a href="editar_paciente.php?id=<?= (int) $paciente['id'] ?>">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>