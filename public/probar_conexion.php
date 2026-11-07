<?php
require_once __DIR__.'/includes/panel_helpers.php';
exigirRol([1]);

require_once __DIR__ . '/../config/database.php';

try {
    $conexion = conectarBD();

    $consulta = $conexion->query(
        "SELECT current_database() AS nombre"
    );

    $resultado = $consulta->fetch();

    echo "Conexion exitosa con PostgreSQL.";
    echo "\nBase de datos: " . htmlspecialchars(
        $resultado['nombre'],
        ENT_QUOTES,
        'UTF-8'
    );

} catch (Throwable $error) {
    http_response_code(500);
    echo "No se pudo conectar a PostgreSQL.";
}
