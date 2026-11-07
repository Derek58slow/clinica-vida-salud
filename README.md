# Clínica Vida Salud

Sistema web académico para administrar pacientes, médicos, especialidades,
horarios, citas médicas, pagos, reportes y auditoría. El proyecto se encuentra
en el Ciclo 2 de la materia Sistemas de Información I.

## Funcionalidades implementadas

- Inicio y cierre de sesión con acceso según rol.
- Registro, consulta, edición y desactivación de pacientes.
- Administración de médicos, especialidades y horarios.
- Reserva de citas con validación de disponibilidad.
- Agenda médica con filtros.
- Reprogramación y cancelación con historial de cambios.
- Control de estados de las citas y reportes administrativos.
- Protección CSRF en operaciones sensibles y consultas PDO preparadas.
- Espacio médico con próxima consulta, agenda propia, pacientes relacionados,
  ficha clínica editable con auditoría, perfil y disponibilidad.
- Avisos internos de reservas, reprogramaciones y cambios de estado.
- Gestión administrativa de ausencias y protección de horarios con citas activas.
- Espacio del paciente con reserva, reprogramación, cancelación, comprobante PDF
  y consulta de métodos de pago.
- Gestión de recepción con pagos, caja y recibos PDF.
- Reportes filtrados con exportación PDF y CSV compatible con Excel.
- Auditoría de creación, reprogramación y cambios de estado de las citas.

## Tecnologías

- PHP 8.2, PDO PostgreSQL y extensión `mbstring`.
- PostgreSQL.
- HTML5, CSS3 y JavaScript.
- Apache, incluido por ejemplo en XAMPP.

## Estructura del repositorio

```text
config/       Configuración de conexión a PostgreSQL
database/     Script de creación de la base de datos
docs/         Documentación académica del proyecto
public/       Páginas y recursos accesibles desde el navegador
src/          Servicios reutilizables para citas, reportes, auditoría y paneles
tests/        Comprobaciones de regresión
```

## Instalación local

1. Instalar PHP 8.2 o superior, Apache y PostgreSQL.
2. Crear una base de datos vacía llamada `clinica_vida_salud_v2`.
3. Ejecutar en esa base el archivo
   `database/migrations/clinica_vida_salud_completa.sql`.
4. Configurar las variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
   `DB_PASSWORD` y `APP_TIMEZONE` tomando `.env.example` como referencia. La aplicación lee
   estas variables del entorno; no carga automáticamente un archivo `.env`.
5. Ejecutar las migraciones incrementales:
   `php database/aplicar_migraciones.php`. Este comando también puede ejecutarse
   sobre una instalación existente y no elimina los registros actuales.
6. Crear un usuario inicial con una contraseña generada mediante
   `password_hash()` de PHP. No se incluyen credenciales predeterminadas.
7. Configurar Apache para servir `public/` como directorio público. Para una
   prueba local también se puede ejecutar `php -S 127.0.0.1:8000 -t public`
   y abrir `http://127.0.0.1:8000/`.

La zona predeterminada es `America/La_Paz`. `APP_TIMEZONE` permite cambiarla
por la zona IANA de la clínica y se aplica tanto a PHP como a cada conexión
PostgreSQL. Las fechas de las citas representan la hora local de la clínica.

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
php tests/roles_regression.php
php tests/invitaciones_regression.php
php tests/medico_regression.php
```

### Prueba funcional en navegador

`tests/medico_browser.cjs` utiliza Playwright y Chromium. Crea un esquema
PostgreSQL `test_medico_*` con datos ficticios, inicia un servidor PHP de prueba
y elimina el esquema al terminar. Necesita las variables de conexión y permisos
para crear esquemas; no modifica las tablas de la aplicación en `public`.

Con Playwright instalado y su navegador Chromium disponible:

```bash
node tests/medico_browser.cjs
```

Si Playwright está instalado fuera del proyecto, indicar su ruta mediante
`PLAYWRIGHT_MODULE`. `TEST_ARTIFACT_DIR` permite guardar capturas en un directorio
existente. La prueba comprueba acceso con dos médicos, cuentas sin perfil o con
perfil inactivo, bloqueo de operaciones ajenas, estados futuros, edición clínica,
autoría del historial, avisos, disponibilidad y pantallas de 360, 768 y 1366 px.

## Responsabilidades por rol

- **Administración:** usuarios e invitaciones, médicos, especialidades, horarios,
  ausencias y gestión general de citas.
- **Recepción:** pacientes, reservas, confirmación, reprogramación, cancelación
  y reportes. No configura médicos, especialidades ni horarios.
- **Médico:** su agenda y citas, pacientes relacionados, actualización de
  información clínica y registro de atención/inasistencia desde el inicio de la cita.
- **Paciente:** consulta su información y sus propias citas; puede reservar,
  reprogramar, cancelar y descargar su comprobante PDF.

Los nuevos eventos de citas guardan el actor real sin sustituir al creador
original. La migración no reconstruye la autoría de eventos históricos anteriores.
Los avisos son internos: no se incluye envío de correo ni notificaciones push.

## Seguridad y datos

- Las credenciales locales deben mantenerse fuera de Git mediante `.env` o
  variables del sistema.
- No deben utilizarse datos reales de pacientes en demostraciones o pruebas.
- El script SQL crea la estructura y los roles, pero no publica contraseñas.

## Estado

Avance funcional del Ciclo 2. El sistema se ejecuta localmente y sus módulos
principales de citas, agenda, pagos, reportes, auditoría y vistas por rol están
disponibles para demostración académica.
