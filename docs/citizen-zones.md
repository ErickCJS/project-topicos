# Zonas del ciudadano

La detección usa el campo `zones.perimeter`, almacenado como un GeoJSON
`Polygon` con un único anillo y coordenadas `[longitud, latitud]`.

El punto se recibe como `latitude` y `longitude`, pero el algoritmo lo evalúa
en el orden GeoJSON `[longitude, latitude]`. Los puntos sobre los segmentos o
vértices del anillo se consideran dentro de la zona.

La precisión GPS debe ser un número no negativo y no puede superar
`config/citizen.php`, configurable mediante `CITIZEN_GPS_MAX_ACCURACY_METERS`.
El valor predeterminado es 100 metros. Este umbral es una política operativa
para rechazar lecturas insuficientes; no representa una garantía de exactitud
física del dispositivo.

La detección no escribe datos. La confirmación vuelve a ejecutar la detección,
ignora cualquier `zone_id` enviado por el cliente y actualiza la zona solo
cuando existe exactamente una coincidencia y el ciudadano envía
`confirmed=true`.
