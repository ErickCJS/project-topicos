# Turnos

El catálogo usa las rutas `shifts.*`, `ShiftController`, el modelo `Shift` y
formularios Blade enviados mediante AJAX, igual que los demás módulos del panel.
Los registros y las operaciones proceden de la base de datos.

- Nombre obligatorio, único y de hasta 50 caracteres.
- Horas obligatorias en formato de 24 horas, con precisión de minutos.
- Inicio y término deben ser distintos. Se permiten turnos nocturnos: un término
  anterior al inicio se interpreta y se identifica como el día siguiente.
- Descripción opcional de hasta 1000 caracteres, visible en listado y detalle.
- Crear, consultar y editar mediante modales, con búsqueda y paginación.
- Solo se pueden eliminar turnos sin grupos ni recorridos asociados. Los
  recorridos eliminados lógicamente también conservan su referencia histórica.

La migración `2026_10_30_130000_add_description_to_shifts_table` agrega la columna
opcional sin reemplazar ni borrar los turnos existentes. Ejecutar
`php artisan migrate` al actualizar el proyecto en otra máquina.

## Diseño

Se reutilizan `admin-indexes.css` y `modal-theme.css`. `shifts.css` contiene solo
los ajustes del módulo y se mantiene en `resources/css` y `public/css`.

Nuevo turno y Guardar usan verde institucional. Cancelar y Cerrar usan el estilo
neutro; las acciones de tabla usan botones de borde e iconos con tamaño uniforme:
consulta verde, edición ámbar y eliminación roja. Incluye estados de interacción,
foco, tema oscuro y desplazamiento horizontal para tablas en pantallas pequeñas.

## Verificación

`php vendor/phpunit/phpunit/phpunit --filter 'ShiftsCrud|Programming'`

Las pruebas se ejecutan exclusivamente con SQLite en memoria y necesitan la
extensión PDO SQLite habilitada. Cubren CRUD, descripción, horarios nocturnos,
edición de horas almacenadas con segundos y protección de turnos utilizados.
