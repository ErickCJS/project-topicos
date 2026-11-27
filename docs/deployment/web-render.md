# Guía de despliegue — Aplicación web SGRS JLO

> Estado: documentación de la aplicación web. La guía de despliegue móvil se agregará en una actualización posterior.

Esta guía no es una guía para ejecutar Laravel en localhost. Describe cómo publicar la aplicación web de SGRS JLO en internet, utilizando Render como alojamiento, Docker para construir la imagen, una base de datos MySQL externa y una URL HTTPS pública.

Al finalizar, la aplicación debe quedar accesible desde una dirección pública de Render (o un dominio propio), sin depender de que el desarrollador mantenga abierto su equipo.

## 1. Arquitectura y configuración del proyecto

La aplicación web utiliza:

| Componente | Configuración del repositorio |
|---|---|
| Framework | Laravel 12 |
| PHP | PHP 8.3 en la imagen Docker |
| Contenedor | Imagen php:8.3-apache |
| Base de datos | MySQL o MariaDB compatible |
| Frontend | Blade, JavaScript y estilos compilados con Tailwind CSS |
| Node.js para compilar | Node.js 22 durante la construcción Docker |
| Plataforma documentada | Render Web Service |
| Puerto | PORT de Render; 10000 como valor predeterminado |

Archivos que intervienen en el despliegue:

- Dockerfile: instala extensiones PHP, instala dependencias de Composer, compila los estilos y prepara la imagen.
- docker/entrypoint.sh: prepara los directorios, crea el enlace de almacenamiento, genera la caché de configuración, ejecuta migraciones cuando se habilitan y levanta Laravel.
- composer.json y composer.lock: dependencias PHP.
- package.json y package-lock.json: dependencias y compilación de estilos.
- bootstrap/app.php: registra la ruta de comprobación de salud /up.

El repositorio no contiene actualmente un archivo render.yaml. Por eso, la primera configuración del servicio se realiza desde el panel de Render.

**Importante:** ejecutar php artisan serve en una terminal local solo levanta un servidor de desarrollo en el equipo del desarrollador. Eso no publica la aplicación en internet ni sustituye los pasos de esta guía.

## 2. Requisitos previos

Antes de desplegar, se necesita:

1. Acceso al repositorio GitHub: [ErickCJS/project-topicos](https://github.com/ErickCJS/project-topicos).
2. Una cuenta en [Render](https://render.com/).
3. Una base de datos MySQL 8 o compatible, creada previamente en un proveedor externo y accesible desde Render.
4. Las credenciales de conexión a la base de datos.
5. Una clave APP_KEY segura para el entorno de producción.

### Preparar la base de datos

Crear una base de datos vacía para producción y conservar estos datos de conexión:

- Host del servidor MySQL.
- Puerto de conexión, normalmente 3306.
- Nombre de la base de datos.
- Usuario.
- Contraseña.
- Requisitos TLS/SSL indicados por el proveedor.

No utilizar localhost como host si la base de datos está alojada fuera del contenedor de Render. Se debe usar el host de conexión proporcionado por el proveedor.

Realizar un respaldo de la base de datos antes de aplicar migraciones sobre una instalación que ya tenga información.

## 3. Generar la clave de la aplicación

En una copia local del proyecto, ejecutar:

~~~bash
php artisan key:generate --show
~~~

Copiar el valor completo que devuelve el comando, incluido el prefijo base64:, y guardarlo como variable APP_KEY en Render.

La clave debe mantenerse estable entre despliegues. No generar una clave nueva cada vez que se publique una actualización.

No subir el archivo .env ni credenciales al repositorio. El archivo está excluido mediante .gitignore y .dockerignore.

## 4. Crear el servicio web en Render

Para una prueba académica se puede utilizar el nivel gratuito de Render, teniendo en cuenta sus limitaciones. Para conservar archivos subidos en el servidor y ejecutar comandos desde Shell, se necesita un servicio compatible con las funciones de pago de Render. Esta guía describe el despliegue con esas capacidades habilitadas.

1. Ingresar a [Render Dashboard](https://dashboard.render.com/).
2. Seleccionar New y luego Web Service.
3. Conectar la cuenta de GitHub si todavía no está vinculada.
4. Seleccionar el repositorio ErickCJS/project-topicos.
5. Configurar el servicio con estos valores:

| Campo | Valor |
|---|---|
| Name | project-topicos-web (o un nombre disponible) |
| Branch | main |
| Language / Runtime | Docker |
| Root Directory | Vacío, porque el Dockerfile está en la raíz |
| Dockerfile Path | ./Dockerfile |
| Docker Context | . (raíz del repositorio) |
| Health Check Path | /up |

El repositorio ya contiene las instrucciones de construcción y arranque. No reemplazar el ENTRYPOINT ni establecer un comando de inicio diferente en Render: el script docker/entrypoint.sh es el encargado de iniciar la aplicación.

Render asigna el puerto HTTP al servicio. El contenedor utiliza PORT y tiene 10000 como valor de respaldo.

## 5. Configurar las variables de entorno

En el servicio de Render, abrir Environment y agregar las siguientes variables.

### Aplicación

| Variable | Valor |
|---|---|
| APP_NAME | SGRS JLO |
| APP_ENV | production |
| APP_KEY | Clave generada en el paso 3 |
| APP_DEBUG | false |
| APP_URL | URL HTTPS asignada al servicio en Render |
| APP_LOCALE | es |
| APP_FALLBACK_LOCALE | es |
| LOG_CHANNEL | stderr |
| LOG_LEVEL | warning |

### Conexión MySQL

| Variable | Valor |
|---|---|
| DB_CONNECTION | mysql |
| DB_HOST | Host entregado por el proveedor |
| DB_PORT | Puerto entregado por el proveedor, normalmente 3306 |
| DB_DATABASE | Nombre de la base de datos |
| DB_USERNAME | Usuario MySQL |
| DB_PASSWORD | Contraseña MySQL |
| DB_CHARSET | utf8mb4 |
| DB_COLLATION | utf8mb4_unicode_ci |

Si el proveedor MySQL exige un certificado TLS, seguir sus instrucciones y configurar el certificado CA que corresponda. La configuración MySQL del proyecto contempla la variable MYSQL_ATTR_SSL_CA.

### Sesiones, caché y colas

| Variable | Valor recomendado |
|---|---|
| SESSION_DRIVER | database |
| SESSION_LIFETIME | 120 |
| CACHE_STORE | database |
| QUEUE_CONNECTION | sync |

La migración inicial del proyecto crea las tablas sessions, cache y jobs. Se recomienda sync para las colas mientras no exista un servicio worker separado que procese trabajos en segundo plano.

### Migraciones automáticas

| Variable | Valor |
|---|---|
| RUN_MIGRATIONS | true |

El script docker/entrypoint.sh ejecuta php artisan migrate --force cuando RUN_MIGRATIONS=true. Dejar primero configurada y comprobada la conexión MySQL.

Esta opción también se aplica cuando Render reinicia o vuelve a levantar el contenedor. Laravel no vuelve a ejecutar migraciones que ya figuran como aplicadas.

No activar APP_DEBUG=true en producción.

## 6. Configurar almacenamiento persistente para archivos

La aplicación guarda imágenes de perfiles, empleados, vehículos y marcas mediante el disco public de Laravel.

Render utiliza un sistema de archivos efímero por defecto. Sin almacenamiento persistente, los archivos guardados localmente pueden perderse al reiniciar o desplegar una nueva versión.

Para conservar las imágenes:

1. Abrir el servicio web en Render.
2. Entrar en Disks y seleccionar Add Disk.
3. Configurar un disco persistente.
4. Utilizar como Mount Path:

~~~text
/var/www/html/storage/app/public
~~~

5. Guardar la configuración y comprobar que el nuevo despliegue quede activo.

El proyecto crea el enlace public/storage durante el inicio del contenedor. El disco debe montarse en la ruta indicada para que las imágenes guardadas en el disco público permanezcan disponibles.

Los discos persistentes solo están disponibles para servicios de pago. Además, al adjuntar un disco, Render mantiene el servicio en una sola instancia y los despliegues pueden ocasionar una breve interrupción: no hay despliegue sin tiempo de inactividad mientras el disco esté conectado. No es posible escalar horizontalmente ese servicio con el disco adjunto.

El nivel gratuito no ofrece discos persistentes. Por eso, si se despliega en Free, las imágenes y archivos guardados en el sistema de archivos local pueden perderse cuando Render reinicie o vuelva a desplegar el servicio. Para una instalación con archivos reales, utilizar un servicio de pago con disco persistente o configurar un almacenamiento de objetos externo.

Como alternativa, el proyecto ya cuenta con una configuración de disco S3 en config/filesystems.php, que puede utilizarse si se implementa y configura un almacenamiento de objetos.

Configurar el almacenamiento persistente antes de comenzar a cargar archivos reales en producción.

## 7. Ejecutar el primer despliegue

1. Revisar que las variables de entorno estén completas.
2. Confirmar que la base de datos permita conexiones desde Render.
3. Seleccionar Create Web Service o Deploy Web Service.
4. Esperar a que Render construya la imagen usando el Dockerfile.
5. Revisar los registros del proceso en Logs y Events.
6. Confirmar que el servicio quede en estado Live.

Durante la construcción, Docker:

- Instala extensiones requeridas por Laravel y MySQL.
- Instala dependencias PHP mediante Composer.
- Instala dependencias frontend con npm ci.
- Compila los estilos con npm run build.
- Prepara los permisos y directorios de storage y bootstrap/cache.

Durante el inicio, docker/entrypoint.sh prepara el almacenamiento, genera la caché de configuración y ejecuta las migraciones cuando están habilitadas.

## 8. Sincronizar el catálogo de permisos

Después de que la base de datos esté migrada y el servicio esté disponible, abrir la consola del servicio desde Render (Shell) y ejecutar:

> El Shell de Render no está disponible en los servicios web Free. Para seguir este paso desde Render, el servicio debe utilizar un plan compatible con Shell.

~~~bash
php artisan permissions:sync-routes
~~~

Este comando sincroniza los módulos, permisos y relaciones con las rutas administrativas definidas por el proyecto. Es necesario para que la gestión de permisos y la navegación funcionen correctamente.

Si se agregan o modifican rutas o permisos en futuras versiones, volver a ejecutar este comando después del despliegue.

### Crear la primera cuenta administradora

No ejecutar php artisan db:seed en producción. El DatabaseSeeder del proyecto también llama a seeders de desarrollo y el seeder administrativo actual contiene credenciales predeterminadas conocidas.

Después de sincronizar permisos, crear la primera cuenta administradora con una contraseña nueva y privada. Desde la Shell de Render, iniciar Tinker:

~~~bash
php artisan tinker
~~~

Dentro de Tinker, ejecutar el siguiente ejemplo reemplazando los datos de ejemplo por los datos institucionales autorizados:

~~~php
$tipo = \App\Models\UserType::firstOrCreate(
    ['name' => 'Administrador'],
    ['description' => 'Usuario con acceso administrativo total al sistema.']
);

\App\Models\User::create([
    'name' => 'Nombre',
    'last_name' => 'Apellido',
    'dni' => '12345678',
    'email' => 'admin@dominio.gob.pe',
    'password' => 'REEMPLAZAR_POR_UNA_CLAVE_SEGURA_Y_UNICA',
    'email_verified_at' => now(),
    'user_type_id' => $tipo->id,
    'is_admin' => true,
    'is_active' => true,
]);
~~~

El modelo de usuario aplica el hash de la contraseña mediante su configuración de atributos. Utilizar un DNI que cumpla la validación del sistema y no esté registrado.

No incluir la contraseña real en este documento, en el repositorio, en capturas públicas ni en comentarios de la actividad. Una vez creada la cuenta, ingresar y comprobar el acceso administrativo.

En un servicio Free no se dispone de Shell en el panel. En ese caso, se debe provisionar la cuenta usando una ejecución de Artisan desde un entorno seguro autorizado para conectarse a la base de datos de producción, o utilizar un plan compatible con Shell. Nunca habilitar una ruta web pública temporal para crear al administrador ni dejar credenciales de administración escritas en el código.

## 9. Comprobaciones posteriores al despliegue

Abrir la URL HTTPS de Render y verificar:

- La ruta /up responde correctamente.
- La página de inicio carga sin errores.
- Es posible iniciar sesión con la cuenta administradora.
- El Dashboard y los módulos administrativos cargan.
- Las tablas y formularios muestran correctamente sus datos.
- Las operaciones de consulta, registro y edición funcionan con la base de datos de producción.
- Las imágenes de empleados, vehículos y marcas se pueden cargar y visualizar.
- Los archivos cargados continúan disponibles después de un reinicio controlado del servicio.
- El modo claro y el modo oscuro se muestran correctamente.
- El servicio no expone mensajes de depuración ni credenciales.

## 10. Actualizar la aplicación web

Cuando existan nuevos cambios aprobados en main:

1. Confirmar que los cambios estén publicados en GitHub.
2. Verificar que Render esté conectado a main y tenga habilitado el despliegue automático, o iniciar un despliegue manual.
3. Abrir Events y Logs para revisar la construcción y el inicio.
4. Si la actualización incluye migraciones, respaldar la base de datos antes de desplegar.
5. Confirmar que el despliegue finalice en estado Live.
6. Ejecutar php artisan permissions:sync-routes desde Shell si se agregaron o cambiaron rutas o permisos.

No cambiar APP_KEY durante una actualización normal.

## 11. Solución de problemas frecuentes

| Problema | Qué revisar |
|---|---|
| Error de conexión a MySQL | DB_HOST, puerto, usuario, contraseña, permisos de red y TLS del proveedor |
| Error 500 | Logs de Render, APP_ENV, APP_DEBUG=false, clave APP_KEY y conexión a base de datos |
| Tabla no encontrada | Confirmar que RUN_MIGRATIONS=true y que php artisan migrate --force haya terminado sin errores |
| No aparecen módulos o permisos | Ejecutar php artisan permissions:sync-routes |
| Imágenes no cargan | Revisar el disco persistente, el Mount Path, el enlace public/storage y los permisos de storage |
| Estilos o scripts desactualizados | Revisar el resultado de construcción Docker y ejecutar un nuevo despliegue para compilar los recursos |
| La aplicación no responde | Comprobar que el servicio escuche en 0.0.0.0 y en el puerto PORT asignado por Render |

## 12. Referencias

- [Repositorio del proyecto](https://github.com/ErickCJS/project-topicos)
- [Documentación de Render: Web Services](https://render.com/docs/web-services)
- [Documentación de Render: Docker](https://render.com/docs/docker)
- [Documentación de Render: variables de entorno](https://render.com/docs/configure-environment-variables)
- [Documentación de Render: Persistent Disks](https://render.com/docs/disks)
- [Documentación de Render: niveles gratuitos y sus limitaciones](https://render.com/docs/free)
- [Documentación de Render: Shell y SSH](https://render.com/docs/ssh)
- [Documentación de Laravel 12](https://laravel.com/docs/12.x)

---

## Próxima documentación

La guía de despliegue de la aplicación móvil Flutter queda pendiente de incorporar. Se documentará en una sección separada cuando se agregue ese proyecto y se confirme su repositorio, configuración de compilación y mecanismo de distribución.
