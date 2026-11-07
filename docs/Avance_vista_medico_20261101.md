# Avance de la vista médica y organización visual

Fecha: 1 de noviembre de 2026.

## Implementado

- Panel médico centrado en próxima consulta, agenda del día y actividad real
  del historial, con métricas identificadas por periodo.
- Familia propia de iconos SVG para inicio, agenda, pacientes, perfil,
  asistencia, ausencias y notificaciones; isotipo de la clínica y favicon.
- Navegación médica consistente y regreso desde el detalle conservando filtros.
- Agenda obligatoriamente limitada al profesional, incluso si falta una ficha
  activa o se manipulan parámetros de URL.
- Pacientes relacionados con búsqueda y paginación; fichas inactivas disponibles
  para consulta histórica, con edición deshabilitada.
- Actualización de sangre, alergias, enfermedades crónicas, medicamentos y
  antecedentes con verificación de relación, CSRF, control de cambios simultáneos
  y auditoría con usuario/datos anteriores/datos nuevos.
- Perfil con disponibilidad semanal y bloqueos próximos.
- Configuración de médicos, especialidades, horarios y ausencias solo para
  administración; acceso directo y escrituras comprobados además del menú.
- Validación de fecha/hora completa en reservas y reprogramación; no se permite
  registrar asistencia o ausencia antes del inicio de una cita confirmada.
- Actor real en nuevos cambios de citas; se conserva el creador original.
- Notificaciones internas al crear o modificar citas, con estado de lectura
  independiente del estado técnico de envío.
- Gestión administrativa de bloqueos sin permitir periodos con citas activas;
  protección de horarios que tienen citas pendientes y contra turnos superpuestos.
- Zona horaria compartida entre PHP y PostgreSQL, configurable con `APP_TIMEZONE`.
- Corrección de salida previa a cabeceras en configuración y rutas heredadas
  utilizadas; antiguas pantallas de pacientes redirigen a sus versiones modernas.
- Páginas de diagnóstico de conexión/tablas restringidas a administración.
- Login con enlace de regreso en flujo normal, separado de la marca y formulario;
  activación de cuenta con estructura y espaciado consistentes.
- Márgenes, separaciones, foco y botones compartidos; ajuste de recepción y
  paciente para errores de carga, iconos y diseño móvil.
- Selectores con etiquetado, navegación por teclado y foco; confirmación al
  registrar inasistencia.

## Base de datos

Se aplicó `database/migrations/20261101_flujo_medico.sql` en el entorno local.
Es incremental y puede volver a ejecutarse. Añade lectura de notificaciones,
auditoría de información médica y funciones/triggers de historial, avisos y
protección de disponibilidad. No cambia retrospectivamente la autoría anterior.

Instalaciones adicionales deben ejecutar:

```bash
php database/aplicar_migraciones.php
```

## Verificación

- Sintaxis PHP y JavaScript.
- Regresiones existentes de citas, roles e invitaciones.
- Pruebas de fechas, transiciones y ausencia de salida en configuración.
- Pruebas HTTP y Chromium con datos ficticios en un esquema PostgreSQL aislado.
- Recorrido de siete pantallas médicas en 360, 768 y 1366 px.
- Comprobación de login, portada, activación y formularios administrativos en móvil.
- Inspección de capturas de login, panel y perfil en escritorio y móvil.
- Verificación de falta de desbordamiento y elementos principales fuera de pantalla.
- Comprobación de que un médico no cambia citas o fichas ajenas, ni modifica
  disponibilidad; las cuentas sin perfil o inactivas no reciben agendas globales.
- Comprobación de que completar una cita registra al médico como actor y
  mantiene a recepción como creador original.

## Siguientes etapas

El trabajo médico corresponde a la gestión de citas y datos clínicos de apoyo.
Diagnósticos por consulta, evolución, recetas y signos vitales requieren definir
su alcance antes de incorporarlos como historia clínica completa.

Continúan como siguientes módulos el autoservicio del paciente (reserva,
cancelación/reprogramación según reglas acordadas), la recuperación de contraseña,
el mantenimiento de contactos de emergencia y una ampliación de reportes.
El envío de correo o push es independiente de los avisos internos implementados.
