
<?php

function conectarBD(): PDO
{
    $host = 'localhost';
    $puerto = '5432';
    $baseDatos = 'clinica_vida_salud_v2';
    $usuario = 'postgres';

    // Para esta prueba local, la contraseña se recibe
    // mediante una variable de entorno.
    $contrasena = getenv('DB_PASSWORD');

    if ($contrasena === false || $contrasena === '') {
        throw new RuntimeException(
            'Falta configurar DB_PASSWORD.'
        );
    }

    $dsn = "pgsql:host=$host;port=$puerto;dbname=$baseDatos";

    return new PDO(
        $dsn,
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
}
