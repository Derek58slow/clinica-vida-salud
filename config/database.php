<?php

require_once __DIR__ . '/../src/Services/clinica.php';

function conectarBD(): PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $puerto = getenv('DB_PORT') ?: '5432';
    $baseDatos = getenv('DB_NAME') ?: 'clinica_vida_salud_v2';
    $usuario = getenv('DB_USER') ?: 'postgres';

    // La contraseña nunca se guarda en el repositorio.
    $contrasena = getenv('DB_PASSWORD');

    if ($contrasena === false || $contrasena === '') {
        throw new RuntimeException(
            'Falta configurar DB_PASSWORD.'
        );
    }

    $dsn = "pgsql:host=$host;port=$puerto;dbname=$baseDatos";

    $pdo = new PDO(
        $dsn,
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    $stmt = $pdo->prepare("SELECT set_config('TimeZone', :zona, false)");
    $stmt->execute(['zona' => date_default_timezone_get()]);
    return $pdo;
}
