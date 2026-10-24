# Gestión de vacaciones

## Compatibilidad con los registros históricos

La migración `2026_10_23_000000_add_dates_to_vacations_table.php` agrega `start_date` y `end_date` como columnas anulables y los índices de consulta. No elimina filas, no inventa fechas y no actualiza `scheduled_days` ni `pending_days` en los datos existentes. La migración de aprobación agrega `status`, `rejection_reason` y `reviewed_at`, y marca las filas existentes como **Aprobada** para conservar su saldo y comportamiento anterior. Las filas que mantienen ambas fechas nulas conservan ese estado aprobado, se muestran con el período **Sin fechas** y un aviso de **Pendiente de regularización**; sus `scheduled_days` siguen consumiendo el cupo anual.

Para regularizar una fila histórica se usa la edición normal, pero el año no puede cambiar y el rango elegido debe tener exactamente la cantidad de `scheduled_days` que ya posee la fila. Así se reemplaza la reserva sin sumar un segundo período ni duplicar días. Estos registros no se pueden eliminar mientras carezcan de fechas: primero deben regularizarse.

La creación de una vacación nueva requiere que el empleado esté activo y tenga actualmente un contrato Permanente o Nombrado vigente, activo y sin anulación. La regularización histórica se valida de forma independiente: se permite aunque el trabajador o su contrato ya no estén activos, siempre que un contrato Permanente o Nombrado haya cubierto todo el rango elegido. Para comprobarlo se respetan tanto `start_date`/`end_date` del contrato como `cancelled_at`; el período no puede extenderse más allá de ninguna de esas fechas.

## Estados y flujo de revisión

Una solicitud nueva se crea como **Pendiente**. Solo una solicitud **Aprobada** descuenta días del saldo anual y bloquea la disponibilidad del trabajador; las solicitudes **Pendiente** y **Rechazada** son visibles, pero no reservan saldo ni fechas. Aprobar vuelve a validar la elegibilidad contractual, el rango, el solapamiento y el máximo anual de 30 días dentro de una transacción con bloqueo del empleado y de sus filas anuales. Por eso dos solicitudes incompatibles no pueden aprobarse simultáneamente.

Una solicitud **Pendiente** puede editarse o eliminarse con las validaciones habituales. Las fechas de una aprobada no se pueden modificar libremente (solo se permite completar una fila histórica sin fechas conservando exactamente sus `scheduled_days`). Una solicitud rechazada no puede aprobarse ni convertirse en otra solicitud. Rechazar exige un motivo obligatorio, que se guarda en `rejection_reason` y se muestra en el detalle.

Las solicitudes ordinarias no pueden comenzar en una fecha pasada. La regularización histórica es la excepción: puede realizarse aunque el empleado o el contrato estén anulados actualmente, si un contrato Permanente o Nombrado cubrió todo el rango y se respetan sus fechas de vigencia y anulación.

## Semántica de `pending_days`

`scheduled_days` pertenece al período individual. En filas con fechas se calcula en el servidor como días calendario inclusivos (`start_date` y `end_date` cuentan). En filas históricas conserva el valor existente hasta su regularización.

`pending_days` es un valor derivado y duplicado en todas las filas del mismo empleado/año:

```text
pending_days = max(0, 30 - SUM(scheduled_days de solicitudes Aprobadas del empleado/año))
```

La migración no normaliza este valor, por lo que conserva literalmente los valores históricos existentes. El servicio de vacaciones lo vuelve a calcular dentro de la misma transacción cuando una operación de alta, edición, regularización, aprobación, rechazo o eliminación afecta al empleado/año. El listado y el detalle calculan siempre el saldo desde la suma anual de solicitudes aprobadas, por lo que no dependen de un valor histórico potencialmente desactualizado. Las filas históricas conservan su `scheduled_days` original; al regularizarlas se asignan fechas sin sumar días adicionales.

## Concurrencia y consulta de disponibilidad

Las escrituras bloquean primero la fila del empleado y luego los períodos anuales antes de validar el máximo y los solapamientos. Esto serializa dos solicitudes simultáneas del mismo trabajador en motores que admiten `SELECT ... FOR UPDATE`.

La consulta autenticada `GET /admin/vacations/check?employee_id={id}&date=YYYY-MM-DD` responde `on_vacation` y, cuando corresponde, el período encontrado. Los históricos sin fechas consumen saldo, pero no pueden bloquear una fecha concreta.

El endpoint usado por el formulario para proyectar el saldo solo admite `excluding_id` cuando la fila excluida pertenece al mismo empleado y al mismo año consultados.
