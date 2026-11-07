# Revisión técnica y plan de mejoras — Clínica Vida Salud

Fecha: 1 de noviembre de 2026.

> **Actualización posterior:** se implementó la fase médica y los ajustes
> visuales descritos en `docs/Avance_vista_medico_20261101.md`. Este documento
> conserva el diagnóstico inicial; sus hallazgos no representan por sí solos
> el estado actual del código.

## 1. Conclusión

El proyecto tiene una base funcional coherente para gestionar citas médicas:
autenticación, cuatro roles, pacientes, médicos, especialidades, horarios,
reservas, reprogramación, cancelación e historial. La vista médica ya cuenta
con panel, agenda, pacientes relacionados y perfil profesional.

Conviene avanzar cerrando primero el flujo del médico, con este orden:

1. Corregir autorización, fechas y trazabilidad.
2. Simplificar y unificar las pantallas de trabajo diario del médico.
3. Verificar ese flujo con pruebas de comportamiento.
4. Completar la reserva del paciente.
5. Optimizar recepción y la integración entre los tres roles.

La revisión identifica problemas y propone trabajo; los hallazgos descritos
siguen pendientes de implementación.

## 2. Alcance y comprobaciones

Se revisaron la estructura, el README, el esquema y la migración incremental,
autenticación, layout, controles JavaScript, estilos, paneles por rol y los
flujos principales de pacientes, médicos, horarios y citas.

Resultados ejecutados:

- Sintaxis: los 63 archivos PHP presentes pasan `php -l`.
- `php tests/citas_regression.php`: OK.
- `php tests/roles_regression.php`: OK.
- `php tests/invitaciones_regression.php`: OK.
- Conexión real a PostgreSQL 18.6: disponible.
- Consulta dentro de una transacción de solo lectura: 16 tablas, incluida
  `invitaciones_usuario`, y los tres triggers esperados de citas presentes.
- Las cuentas médicas y de paciente activas consultadas tienen su ficha activa
  vinculada. Esto no elimina los fallos de autorización para otros estados.
- Zona horaria de PHP CLI: `Europe/Berlin`; PostgreSQL: `GMT`.
- La función de historial instalada usa `NEW.registrado_por` como actor.

Las pruebas existentes son comprobaciones estáticas mediante búsquedas de
texto. No ejecutan reservas concurrentes ni peticiones autenticadas por rol.
No se realizó una inspección visual en navegador, ni una comparación completa
con los documentos Word de requisitos. La transcripción de clase orienta el
alcance, pero no sustituye una especificación validada.

## 3. Aspectos bien encaminados

- Uso de PDO y consultas preparadas en los flujos revisados.
- Contraseñas verificadas con `password_verify()` y renovación del identificador
  de sesión al iniciar sesión.
- Operaciones de escritura protegidas mediante POST y tokens CSRF en los
  controladores revisados.
- Separación de paneles y navegación según el rol.
- Relación explícita entre usuario y ficha médica o de paciente.
- Restricción PostgreSQL contra superposición de citas activas por médico.
- Validaciones de disponibilidad y bloqueos también en la base de datos.
- Transacciones y bloqueo de filas para reprogramar, cancelar y cambiar estados.
- Invitaciones con token aleatorio, hash, vencimiento y uso único.
- Identidad visual compartida mediante variables CSS, tarjetas y menú adaptable.

## 4. Hallazgos prioritarios

### P0 — Agenda sin filtro para médico sin perfil activo

Referencia: `public/agenda.php:28-59`.

Si no se encuentra un médico activo vinculado, se asigna `0` a
`$medicoFiltro`. Después, el filtro SQL solo se añade si ese valor es verdadero.
Como resultado, la consulta del día puede devolver citas de todos los médicos.

Corrección: resolver obligatoriamente la identidad médica y detener la consulta
si no existe un perfil activo. Para el rol médico, el filtro de pertenencia
debe ser obligatorio, independientemente de los parámetros GET.

Aceptación: médico sin ficha o con ficha inactiva nunca recibe citas ajenas;
un médico vinculado tampoco puede cambiar su alcance manipulando `medico_id`.

### P0 — Gestión de horarios ajenos accesible por URL

Referencias: `public/horarios_moderno.php:9-25`,
`public/guardar_horario.php:9-14`, `public/actualizar_horario.php:9-14` y
`public/cambiar_estado_horario.php:9-14`.

Estas rutas admiten al rol médico y las escrituras no verifican que el horario
pertenezca al profesional autenticado. Se aceptan identificadores enviados por
el cliente. La vista `medico_perfil.php`, en cambio, indica que los cambios se
solicitan a administración. El menú no refleja lo que permiten las rutas.

Corrección propuesta, consistente con el perfil actual: dejar al médico la
consulta de sus horarios y reservar la configuración a administración.
Formalizar si recepción tendrá también ese permiso.

Aceptación: probar acceso directo y POST con identificadores ajenos; ocultar
enlaces no cuenta como control de acceso.

### P1 — Historial con actor incorrecto

Referencias: `database/migrations/clinica_vida_salud_completa.sql:260-281` y
`public/cambiar_estado_cita.php:82-84`.

El trigger registra `NEW.registrado_por`, que identifica al creador original.
Los controladores de actualización no transmiten quién realiza cada cambio.
Una atención marcada por el médico puede quedar atribuida a quien reservó.
Se confirmó esta lógica en la función instalada.

Corrección: transmitir el actor de cada operación al mecanismo de auditoría
dentro de la misma transacción, conservando al creador original de la cita.
Aplicar también a cancelaciones y reprogramaciones mediante migración incremental.

Aceptación: una cita creada en recepción y atendida por un médico muestra a
cada persona en su evento correspondiente.

### P1 — Fechas y horas sin una política común

Referencias: `config/database.php`, `public/guardar_cita.php:43-46`,
`public/reprogramar_cita.php:95-100` y `public/cambiar_estado_cita.php:53-80`.

PHP CLI y PostgreSQL tienen zonas diferentes. Las páginas combinan `date()` y
`time()` con `CURRENT_DATE` y `CURRENT_TIMESTAMP`. La configuración de Apache
también debe comprobarse, pues puede diferir de CLI.

Además, los guardados comparan la fecha con el día actual, pero no rechazan de
forma explícita una hora ya transcurrida del mismo día. La interfaz sí oculta
esos turnos; una petición directa puede eludir esa parte de la interfaz.
El cambio de estado permite marcar como atendida o ausente una cita futura.

Corrección: acordar la zona de la clínica y aplicarla a PHP y a la conexión
PostgreSQL; validar fecha/hora completa en servidor y definir cuándo se permite
marcar atención o inasistencia.

Aceptación: revisar límite de medianoche, horas pasadas del mismo día y citas
futuras, incluyendo peticiones directas.

### P1 — Salida antes de cabeceras

Referencias: `config/database.php:1`, `public/procesar_login.php:1` y varios
archivos heredados con una línea anterior a `<?php`.

Se verificó que incluir la configuración emite 2 bytes. Sin buffering, esa
salida puede interferir con redirecciones o cabeceras; en páginas que emiten
salida antes de `session_start()`, también puede afectar a la sesión.

Corrección: archivos PHP de lógica deben comenzar directamente con `<?php`,
sin BOM ni espacios anteriores. Verificar redirecciones con buffering desactivado.

## 5. Mejoras funcionales y de coherencia

| Área | Situación observada | Mejora propuesta |
| --- | --- | --- |
| Panel médico | “Actividad reciente” ordena por fecha de cita descendente, incluyendo futuras. | Usar eventos de historial o renombrar la sección como últimas citas registradas según el criterio real. |
| Indicadores | “Citas de hoy” cuenta activas, pero la agenda del panel incluye todos los estados. “Pacientes asignados” cuenta cualquier relación histórica. | Definir y explicar cada métrica; usar “Pendientes de hoy” y “Pacientes relacionados” si se conserva esa lógica. |
| Detalle | Un médico con cita solicitada puede encontrar una sección de cambio de estado sin acciones; `no_asistio` también deja una sección vacía. | Renderizar solo las acciones válidas y explicar cuando se espera confirmación de recepción. |
| Navegación médica | `historial_paciente.php:75-78` enlaza a listado y edición de pacientes reservados a otros roles. | Volver a “Mis pacientes” y abrir la ficha relacionada para el médico. |
| Pacientes médicos | Lista sin paginación; incluye inactivos, pero la ficha exige paciente activo. | Definir política de consulta histórica y alinear lista/ficha; paginar y ordenar por próxima cita relevante. |
| Ficha médica | Hay lectura de alergias, antecedentes y contactos, pero no se encontró un flujo PHP para mantenerlos. | Definir responsable de carga y edición; no presentar estos datos como un módulo clínico terminado. |
| Notificaciones | Existe bandeja, no se encontró productor de avisos en PHP ni en el esquema revisado; la tabla consultada está vacía. | Generar avisos al reservar, confirmar, reprogramar y cancelar; definir lectura y destinatarios. |
| Horarios y bloqueos | La base soporta bloqueos, pero no hay interfaz de gestión localizada. | Añadir gestión autorizada de ausencias; impedir cambios de disponibilidad que dejen citas activas sin atención. |
| Paciente | Puede consultar citas; reserva, reprogramación y cancelación solo admiten administración/recepción. | Completar autoservicio según requisitos, derivando siempre el paciente de la sesión. |
| Panel paciente | La tabla de próximas citas no filtra estado activo. | Excluir canceladas y estados finales del listado de próximas citas. |
| Recepción | Menú oculta configuración de médicos, pero varias rutas permiten rol 2. | Alinear permisos de servidor con la matriz de responsabilidades. |
| Errores | Varios paneles capturan excepciones y muestran ceros o ausencia de vínculo. | Diferenciar error de carga, falta de perfil y ausencia real de datos. |

## 6. Propuesta para cerrar primero la vista médica

### Pantalla 1 — Inicio del médico

- Encabezado compacto con nombre, especialidad, consultorio y fecha.
- Indicadores del día: pendientes, atendidas y ausencias; totales históricos
  claramente diferenciados si se conservan.
- Próxima consulta destacada con paciente, hora, motivo y botón “Ver cita”.
- Agenda de hoy como bloque principal; estados finales visualmente secundarios.
- Accesos a agenda, pacientes y horario sin repetir demasiadas tablas.
- Avisos útiles cuando falte perfil, disponibilidad o información de carga.

### Pantalla 2 — Mi agenda

- Vista diaria clara; navegación anterior/hoy/siguiente y filtros pertinentes.
- Especialidades limitadas a las del profesional cuando corresponda.
- Hora, duración, paciente, motivo y estado con jerarquía consistente.
- Acceso al detalle preservando la fecha y filtros de regreso.
- Estado vacío distinto de error y de falta de vinculación.

### Pantalla 3 — Detalle de cita

- Resumen de cita y paciente; acceso directo a la ficha relacionada.
- Estado y acciones permitidas visibles sin desplazamiento innecesario.
- Confirmación comprensible antes de registrar inasistencia.
- Historial con actor real y fecha del cambio.
- Usar “Ver cita” o “Detalle de cita” de forma consistente: actualmente no
  existe un registro de consulta con diagnóstico, evolución o receta.

### Pantalla 4 — Mis pacientes y ficha relacionada

- Buscador con etiqueta accesible y resultados paginados.
- Documento, próxima cita y atenciones realizadas en cada resultado.
- Ficha organizada en datos básicos, alertas médicas y citas con el profesional.
- Distinguir datos no registrados de ausencia expresamente confirmada de alergias.

### Pantalla 5 — Perfil y horarios

- Datos profesionales y disponibilidad semanal de consulta.
- Mostrar claramente quién administra los cambios.
- Representar ausencias/bloqueos cuando se complete su gestión.

### Criterios visuales comunes

Conservar la identidad verde/azul existente y unificar agenda/detalle con los
componentes médicos. Reducir estilos inline; usar iconos SVG consistentes,
etiquetas vinculadas a sus controles, foco visible y títulos descriptivos.
Revisar los selectores personalizados con teclado y lector de pantalla:
actualmente no implementan la navegación completa por flechas de un listbox.

Validar en navegador a 360, 768 y 1366 píxeles: menú, filtros, botones, tablas,
nombres largos y estados vacíos. Las reglas CSS adaptables existentes son una
base, no una comprobación visual terminada.

## 7. Organización técnica para continuar

- `src/` está preparado por carpetas, pero la lógica revisada reside en las
  páginas de `public/`. Extraer progresivamente autenticación, autorización,
  disponibilidad y transiciones a componentes compartidos.
- Priorizar un único control de pertenencia de médico/paciente, usado por
  páginas y controladores de escritura.
- Definir si la autorización usará roles fijos o `roles_permisos`: las tablas
  existen, pero no se encontró su consulta desde PHP.
- Evitar duplicar reglas de disponibilidad entre nueva cita y reprogramación.
- Consolidar las vistas antiguas/modernas de pacientes y sus redirecciones:
  los guardados actuales regresan a las páginas antiguas.
- Añadir paginación donde hoy se carga el historial/listado completo.
- Restringir `public/ver_tablas.php` y `public/probar_conexion.php`, que no
  exigen sesión y exponen detalles internos si se publican.
- Configurar el servidor para que el directorio público sea `public/`.
- Actualizar README con la migración de invitaciones y las tres pruebas.
- Alinear casos de uso, interfaces, permisos y diagramas con lo implementado,
  tal como enfatiza la transcripción de clase.

## 8. Plan de entregas y aceptación

### Entrega 1 — Base médica consistente

Corregir P0 y P1, matriz de permisos, enlaces médicos y estados vacíos.

Pruebas necesarias: dos médicos con citas distintas, cuenta sin perfil,
perfil inactivo, acceso directo a rutas y POST con identificadores ajenos.
Verificar también el actor de una actualización realizada por otro usuario.

### Entrega 2 — Experiencia médica terminada

Aplicar las cinco pantallas propuestas y verificar el recorrido:

`Inicio → Agenda → Detalle → Ficha relacionada → Registrar resultado de asistencia`.

Se considera cerrada cuando los datos y estados son coherentes, el médico
solo opera dentro de su alcance, las acciones registran el actor correcto y
la navegación funciona en escritorio y móvil.

### Entrega 3 — Paciente

Completar reserva web para su propia ficha, próximas citas, historial y avisos.
Definir reglas de cancelación/reprogramación y qué especialidades admiten
reserva web. Probar dos pacientes y reserva simultánea del mismo turno.

### Entrega 4 — Recepción e integración

Optimizar búsqueda de pacientes, nueva cita, confirmación y seguimiento diario;
añadir accesos al detalle desde el panel y un diseño de columnas adaptable.
Probar el recorrido completo:

`Recepción registra/vincula → Paciente reserva → Recepción confirma → Médico atiende → Paciente consulta estado`.

No hace falta incorporar una historia clínica completa para cerrar un sistema
de gestión de citas. Diagnósticos, recetas, evolución y signos vitales deben
añadirse solo si forman parte de los requisitos acordados.
