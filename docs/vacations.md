# Gestión de vacaciones

## Compatibilidad con los registros históricos

La migración `2026_10_23_000000_add_dates_to_vacations_table.php` agrega `start_date` y `end_date` como columnas anulables y los índices de consulta. No elimina filas, no inventa fechas y no actualiza `scheduled_days` ni `pending_days` en los datos existentes. Las filas que mantienen ambas fechas nulas se muestran como **Pendiente de regularización** y sus `scheduled_days` siguen consumiendo el cupo anual.

Para regularizar una fila histórica se usa la edición normal, pero el año no puede cambiar y el rango elegido debe tener exactamente la cantidad de `scheduled_days` que ya posee la fila. Así se reemplaza la reserva sin sumar un segundo período ni duplicar días. Estos registros no se pueden eliminar mientras carezcan de fechas: primero deben regularizarse.

La creación de una vacación nueva requiere que el empleado esté activo y tenga actualmente un contrato Permanente o Nombrado vigente, activo y sin anulación. La regularización histórica se valida de forma independiente: se permite aunque el trabajador o su contrato ya no estén activos, siempre que un contrato Permanente o Nombrado haya cubierto todo el rango elegido. Para comprobarlo se respetan tanto `start_date`/`end_date` del contrato como `cancelled_at`; el período no puede extenderse más allá de ninguna de esas fechas.

## Semántica de `pending_days`

`scheduled_days` pertenece al período individual. En filas con fechas se calcula en el servidor como días calendario inclusivos (`start_date` y `end_date` cuentan). En filas históricas conserva el valor existente hasta su regularización.

`pending_days` es un valor derivado y duplicado en todas las filas del mismo empleado/año:

```text
pending_days = max(0, 30 - SUM(scheduled_days del empleado/año))
```

La migración no normaliza este valor, por lo que conserva literalmente los valores históricos existentes. El servicio de vacaciones lo vuelve a calcular dentro de la misma transacción cuando una operación de alta, edición, regularización o eliminación afecta al empleado/año. El listado y el detalle calculan siempre el saldo desde la suma anual, por lo que no dependen de un valor histórico potencialmente desactualizado.

## Concurrencia y consulta de disponibilidad

Las escrituras bloquean primero la fila del empleado y luego los períodos anuales antes de validar el máximo y los solapamientos. Esto serializa dos solicitudes simultáneas del mismo trabajador en motores que admiten `SELECT ... FOR UPDATE`.

La consulta autenticada `GET /admin/vacations/check?employee_id={id}&date=YYYY-MM-DD` responde `on_vacation` y, cuando corresponde, el período encontrado. Los históricos sin fechas consumen saldo, pero no pueden bloquear una fecha concreta.

El endpoint usado por el formulario para proyectar el saldo solo admite `excluding_id` cuando la fila excluida pertenece al mismo empleado y al mismo año consultados.
