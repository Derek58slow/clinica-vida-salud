
<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conexion = conectarBD();

    $sql = "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_type = 'BASE TABLE'
        ORDER BY table_name
    ";

    $consulta = $conexion->query($sql);
    $tablas = $consulta->fetchAll();

    echo "<h1>Clínica Vida Salud</h1>";
    echo "<h2>Tablas de nuestra base de datos</h2>";

    echo "<p>Total de tablas: " . count($tablas) . "</p>";

    echo "<ul>";

    foreach ($tablas as $tabla) {
        echo "<li>" . htmlspecialchars(
            $tabla['table_name'],
            ENT_QUOTES,
            'UTF-8'
        ) . "</li>";
    }

    echo "</ul>";

} catch (Throwable $error) {
    http_response_code(500);
    echo "No se pudo consultar la base de datos.";
}
