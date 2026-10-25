# Interfaz de Programaciones

La ruta autenticada `GET /admin/programming` (`programming.index`) usa
`ProgrammingController@index` para mostrar la vista. No consulta modelos ni
guarda información en el servidor.

## Alcance

- Listado con filtros por fechas, zona y turno, búsqueda y paginación.
- Exportación de las filas filtradas a Excel/PDF, impresión y selección de columnas.
- Modal de creación por rango de fechas y días de trabajo; selección de grupo
  con búsqueda por nombre, zona o turno y personal editable.
- Disponibilidad simulada, con errores, sugerencias y estado correcto.
- Detalle, edición de una ocurrencia, eliminación, historial y finalización
  locales. Todos los cambios se pierden al recargar.
- Programación masiva pendiente de especificación; el botón muestra un aviso.

Los catálogos y registros de ejemplo están en `public/js/programming.js`.
Los estilos específicos se mantienen en `resources/css/programming.css` y su
copia pública, reutilizando `admin-indexes.css` y `modal-theme.css`.

## Revisión manual

1. Entrar con sesión y abrir Programación → Programación.
2. Filtrar por Zona Centro: deben aparecer dos registros iniciales.
3. Crear una programación con Grupo A del 26 al 31 de octubre de 2026.
   La validación debe mostrar vacaciones del conductor y contrato del ayudante;
   Guardar permanece bloqueado.
4. Cambiar el conductor a Carlos Guerrero y el segundo ayudante a Juan Martínez.
   Validar: se habilita Guardar para tres días de trabajo.
5. Cambiar un dato: Guardar se bloquea hasta revalidar.
6. Guardar: aparecen las nuevas filas solo en la sesión actual del navegador.
7. Revisar los estados en modo claro/oscuro y el desplazamiento en pantalla pequeña.

Pruebas de acceso: `php vendor/phpunit/phpunit/phpunit --filter ProgrammingInterfaceTest`.
