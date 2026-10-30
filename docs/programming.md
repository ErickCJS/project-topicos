# Programaciones de recorridos

## Estructura y datos reales

`ProgrammingController` sigue el flujo Laravel/Blade, Eloquent, AJAX y transacciones
utilizado por `StaffGroupsController`. Los catálogos de grupos, personal, zonas y
turnos proceden de la base de datos. JavaScript solo controla las interacciones y
presenta las respuestas del servidor; no crea catálogos ni recorridos simulados.

Se reutilizan `StaffGroup`, sus relaciones `days`, `helpers`, `driver`, `vehicle`,
`zone` y `shift`. Se conservan las convenciones de staff_group: días ISO 1-7
(lunes-domingo), conductor con `employee_type_id = 1` y ayudantes con tipo distinto
al de conductor. Solo se aceptan grupos y empleados activos.

Para disponer del módulo en otra máquina: actualizar el código y ejecutar
`php artisan migrate`. No es necesario ni se ejecuta un seeder de Programaciones.
Debe existir al menos un grupo real configurado en Grupos de Personal.

## Operaciones

- `GET /admin/programming`: vista o listado JSON con filtros por período, zona y
  turno. Sin filtros muestra el día actual en la zona horaria de la aplicación.
- `GET /admin/programming/groups/{id}`: configuración real del grupo seleccionado.
- `GET /admin/programming/holidays`: feriados activos del período que coinciden
  con los días de trabajo elegidos, consultados desde la base de datos.
- `POST /admin/programming/check`: validación de fechas, días y equipo; informa
  disponibilidad, reemplazos y fechas omitidas.
- `POST /admin/programming`: generación individual por rango de fechas.
- `GET /admin/programming/{id}`: detalle persistido del recorrido diario.
- `PUT /admin/programming/{id}`: modificación de una sola ocurrencia pendiente,
  con validación previa y comprobación nuevamente al guardar.
- `GET /admin/programming/{id}/history`: historial, usuario, fecha y valores
  anteriores/posteriores, incluso cuando el recorrido fue eliminado lógicamente.
- `POST /admin/programming/{id}/finish`: finalización del recorrido diario,
  registrando usuario, fecha y canal web.
- `DELETE /admin/programming/{id}`: eliminación lógica de un recorrido pendiente,
  conservando historial y liberando las reservas de vehículo y personal.

Todas las rutas utilizan el middleware `web` y `auth` del panel existente, y las
mutaciones AJAX incluyen el token CSRF. No se introduce un sistema de roles nuevo.

## Generación y disponibilidad

Se recorren las fechas reales del calendario. Los feriados activos se omiten por
defecto; el responsable puede marcar fechas específicas con “Trabajar este día”.
Las excepciones solo afectan a esa programación y exigen la misma validación de
contratos, vacaciones, capacidad y cruces. La selección queda asociada al token de
validación y se comprueba otra vez al guardar. Se conserva en el snapshot de cada
recorrido la fecha, el nombre del feriado, quién autorizó y cuándo; se muestra en
detalle e historial sin modificar el catálogo global. Repetir la generación con
un feriado habilitado permite completar esa fecha si antes se había omitido.
Al editar un recorrido y cambiar su fecha, se selecciona automáticamente el día
semanal correspondiente y se consultan sus feriados. Un feriado sin habilitar se
informa sin autorizar el guardado; al habilitarlo se revalidan las reservas de
grupo, vehículo y personal, excluyendo únicamente el recorrido que se edita.
En edición, las reservas de la fecha destino se comprueban incluso si todavía
no se marcó el feriado. Los avisos identifican el recorrido existente y, si hay
autorización registrada, quién habilitó el trabajo en ese feriado. Una fecha ya
programada tiene prioridad sobre el aviso de feriado pendiente de habilitar.
La creación y modificación exigen una fecha inicial igual o posterior a hoy en
America/Lima, tanto en el formulario como al validar y guardar en el servidor.
Los filtros del listado permiten fechas pasadas para consultar el historial.
La identidad para evitar duplicados es grupo + fecha: repetir una generación
conserva lo existente; añadir días semanales permite completar fechas faltantes.
Solo se admiten los días semanales configurados en el grupo. El formulario los
precarga y bloquea los demás; el servidor comprueba esta restricción al validar
y guardar. Para agregar otro día primero debe actualizarse el grupo. La edición
de un recorrido también exige una fecha cuyo día esté permitido por ese grupo;
habilitar un feriado no permite saltarse esa configuración.
Cambiar la plantilla no modifica automáticamente recorridos ya generados.
Si todas las fechas están programadas u omitidas, la validación no habilita el
guardado ni emite un token. El servidor también rechaza guardar una operación
sin fechas nuevas; el formulario muestra una advertencia con las fechas omitidas.

Cada conductor y ayudante necesita contrato activo no anulado que cubra cada
fecha. Solo las vacaciones aprobadas bloquean disponibilidad. No se admite
personal ni vehículo asignado en la misma fecha y turno; otro turno sí se permite.
Los reemplazos sugeridos proceden de empleados reales del cargo correspondiente,
con disponibilidad para todas las fechas nuevas de la operación.

La capacidad del vehículo es el máximo de ocupantes, incluido un conductor. Las
plazas opcionales pueden quedar vacías; las personas no pueden repetirse dentro
del equipo. El formulario adapta las plazas de ayudantes a esa capacidad.

Una inconsistencia bloquea el lote completo. Una validación satisfactoria genera
un token de sesión válido por 15 minutos, asociado a los datos del formulario y
la configuración del grupo. Cambiar los datos exige revalidar. Al guardar siempre
se consultan otra vez los contratos, vacaciones y ocupaciones actuales.

La operación usa transacciones, bloqueos de recursos y claves únicas para las
reservas de grupo/vehículo y personal por fecha y turno. Los recorridos guardan
copias de los nombres y horarios usados, y sus empleados asignados, para conservar
el contexto histórico aunque luego cambie el grupo. No se puede editar ni
eliminar un recorrido finalizado. El rango máximo por operación es de 366 días.

## Interfaz y reportes

Se conserva la línea gráfica, los modales, los estilos compartidos y las
exportaciones a Excel/PDF e impresión. Los reportes usan los datos reales del
listado filtrado, todas sus páginas y las columnas visibles. Al guardar se muestra
el período generado para que los nuevos registros puedan revisarse.

Programación masiva queda deshabilitada e identificada como siguiente avance,
según el alcance del ingeniero. Los cruces se evalúan por la misma fecha y el
mismo turno; reglas entre turnos diferentes con horarios superpuestos o que
atraviesan medianoche requieren una definición adicional del equipo.

## Verificación

`php vendor/phpunit/phpunit/phpunit --filter Programming`

Las pruebas requieren PDO SQLite habilitado y utilizan exclusivamente SQLite
en memoria, con una comprobación explícita para evitar usar la base MySQL local.
Cubren persistencia, catálogos reales, feriados, fechas duplicadas, ampliaciones,
contratos, vacaciones de ambos cargos, sugerencias, cruces de personal/vehículo,
capacidad, tokens de validación, cambios concurrentes de disponibilidad, edición,
finalización, copias históricas y eliminación lógica con liberación de reservas.
