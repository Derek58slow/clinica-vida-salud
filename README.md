# Clínica Vida Salud

Sistema web académico para administrar pacientes, médicos, especialidades,
horarios y citas médicas. El proyecto corresponde al Ciclo 1 de la materia
Sistemas de Información I.

## Funcionalidades implementadas

- Inicio y cierre de sesión con acceso según rol.
- Registro, consulta, edición y desactivación de pacientes.
- Administración de médicos, especialidades y horarios.
- Reserva de citas con validación de disponibilidad.
- Agenda médica con filtros.
- Reprogramación y cancelación con historial de cambios.
- Control de estados de las citas y reportes administrativos.
- Protección CSRF en operaciones sensibles y consultas PDO preparadas.

## Tecnologías

- PHP 8.2 y PDO.
- PostgreSQL.
- HTML5, CSS3 y JavaScript.
- Apache, incluido por ejemplo en XAMPP.

## Estructura del repositorio

```text
config/       Configuración de conexión a PostgreSQL
database/     Script de creación de la base de datos
docs/         Documentación académica del proyecto
public/       Páginas y recursos accesibles desde el navegador
src/          Estructura reservada para la evolución por capas
tests/        Comprobaciones de regresión
```

## Instalación local

1. Instalar PHP 8.2 o superior, Apache y PostgreSQL.
2. Crear una base de datos vacía llamada `clinica_vida_salud_v2`.
3. Ejecutar en esa base el archivo
   `database/migrations/clinica_vida_salud_completa.sql`.
4. Configurar las variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y
   `DB_PASSWORD` tomando `.env.example` como referencia. La aplicación lee
   estas variables del entorno; no carga automáticamente un archivo `.env`.
5. Crear un usuario inicial con una contraseña generada mediante
   `password_hash()` de PHP. No se incluyen credenciales predeterminadas.
6. Colocar el proyecto dentro del directorio público de Apache y abrir
   `http://localhost/clinica-vida-salud/public/`.

Ejemplo para generar un hash seguro desde una terminal con PHP:

```bash
php -r "echo password_hash('CAMBIAR_ESTA_CLAVE', PASSWORD_DEFAULT), PHP_EOL;"
```

Luego se puede insertar un administrador reemplazando `HASH_GENERADO`:

```sql
INSERT INTO usuarios
    (rol_id, nombre_usuario, nombre, correo, contrasena_hash)
SELECT id, 'admin', 'Administrador', 'admin@clinica.local', 'HASH_GENERADO'
FROM roles
WHERE nombre = 'administrador';
```

## Pruebas

Comprobación de sintaxis de un archivo:

```bash
php -l public/index.php
```

Comprobaciones de regresión del módulo de citas:

```bash
php tests/citas_regression.php
```

## Seguridad y datos

- Las credenciales locales deben mantenerse fuera de Git mediante `.env` o
  variables del sistema.
- No deben utilizarse datos reales de pacientes en demostraciones o pruebas.
- El script SQL crea la estructura y los roles, pero no publica contraseñas.

## Estado

Avance funcional del Ciclo 1. El sistema se ejecuta localmente y sus módulos
principales de citas, agenda, reprogramación, cancelación y reportes están
disponibles para demostración académica.
