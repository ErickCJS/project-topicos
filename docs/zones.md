# Gestión de Zonas

## Geografía y fuente de datos

El módulo usa las tablas `departments`, `provinces` y `districts` con los códigos UBIGEO oficiales del Instituto Nacional de Estadística e Informática (INEI). La fuente de referencia es el dataset público [Ubigeos (Códigos de Ubicación Geográfica) del INEI](https://www.datosabiertos.gob.pe/dataset/ubigeos-c%C3%B3digos-de-ubicaci%C3%B3n-geogr%C3%A1fica-instituto-nacional-de-estad%C3%ADstica-e-inform%C3%A1tica-inei), que identifica cada departamento, provincia y distrito mediante su código oficial.

El repositorio no incluye una copia de ese catálogo para evitar publicar una versión potencialmente desactualizada o inventar relaciones geográficas. Descargue y revise un CSV oficial con las columnas `ubigeo,departamento,provincia,distrito`, guárdelo como `database/data/peru_ubigeo.csv` (o indique `PERU_UBIGEO_FILE`) y ejecute:

```bash
php artisan db:seed --class=Database\\Seeders\\PeruGeographySeeder
```

`PeruGeographySeeder` valida códigos de seis dígitos, deriva únicamente los prefijos departamental/provincial contenidos en el UBIGEO oficial, usa transacción y `updateOrCreate`, por lo que es idempotente. Si el archivo no existe, muestra una advertencia y no inserta registros.

## Zonas y perímetros

Cada zona pertenece a un distrito mediante `district_id`. El campo `perimeter` almacena un único GeoJSON `Polygon`, con coordenadas en el orden GeoJSON `[longitud, latitud]`. El backend exige un anillo cerrado con al menos tres vértices distintos, área mayor que cero y comprueba los rangos `-180..180` y `-90..90`. El formulario usa Leaflet 1.9.4 y Leaflet.draw 1.0.4 desde CDN; las URLs están declaradas en la vista para que puedan fijarse a una política CSP o servirse localmente en el despliegue.

Las rutas y programaciones futuras pueden referenciar `zones.id`; este módulo todavía no crea esas tablas.


## Sectores operativos referenciales de José Leonardo Ortiz

`ZoneSeeder` administra 16 zonas asociadas exclusivamente al distrito con UBIGEO `140105`:

- Actualiza la geometría de `JLO - Sector 01` a `JLO - Sector 04`.
- Añade sectores de referencia para San Miguel, Miraflores, Ramiro Prialé, 1 de Mayo, Nuevo San Lorenzo, San Carlos, Villa Hermosa, Quinta Santa María, Santa Isabel, Santa Rosa, Urrunaga y la zona central.

Los nombres se apoyan en localidades, pueblos jóvenes, UPIS y urbanizaciones que aparecen en documentación municipal y de planificación distrital. Las coordenadas de referencia identifican ubicaciones aproximadas de esas localidades. Los polígonos irregulares se construyeron como particiones operativas alrededor de esos puntos; no se deben interpretar como límites catastrales, límites vecinales aprobados ni trazos oficiales de calles. Antes de usarlos para contratos, fiscalización o asignación definitiva de responsabilidades, deben validarse con cartografía municipal y trabajo de campo.

El seeder es idempotente: actualiza las 4 zonas base, crea las 12 adicionales si todavía no existen y no elimina zonas de otros distritos. La validación de solapamientos se limita al distrito JLO y se ejecuta en MySQL.

Para aplicar los cambios en una base que ya tiene cargada la geografía:

```bash
php artisan db:seed --class="Database\\Seeders\\ZoneSeeder"
```

La vista de gestión incluye un mapa general de los 16 perímetros con colores diferenciados y etiquetas. El mapa usa OpenStreetMap como cartografía de fondo.
