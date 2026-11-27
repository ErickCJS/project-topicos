# Despliegue Laravel 12 en cPanel

Esta guía prepara el repositorio para cPanel. La ruta de repositorio del archivo
.cpanel.yml debe ajustarse al nombre de usuario y a la ubicación real del repositorio
en el hosting antes de usar Deploy.

## Requisitos del hosting

- PHP 8.3 (o una versión compatible con Laravel 12)
- Extensiones PHP requeridas por Laravel y Composer, incluyendo PDO MySQL, Mbstring,
  OpenSSL, cURL, Fileinfo, XML, Ctype, JSON, BCMath, GD y ZIP según el proyecto
- Composer
- Node.js y npm para compilar Tailwind
- Git Version Control, SSH/Terminal y rsync
- MySQL
- Dominio con document root configurado a la carpeta public del proyecto, NO a la raíz Laravel

## Layout recomendado

- Repositorio cPanel: $HOME/repositories/project-topicos
- Aplicación desplegada: $HOME/apps/project-topicos
- Document root del dominio: $HOME/apps/project-topicos/public

La raíz del proyecto Laravel debe quedar fuera de public_html. Configura el dominio desde
cPanel Domains para que su document root apunte a la carpeta public de la app desplegada.
Si tu proveedor no permite apuntar el dominio a una carpeta fuera de public_html, solicita
instrucciones de configuración segura antes de publicar.

## Primera configuración

1. Clona el repositorio desde Git Version Control en $HOME/repositories/project-topicos.
2. Crea la carpeta de despliegue $HOME/apps/project-topicos.
3. Copia .env.example a $HOME/apps/project-topicos/.env y completa valores de producción.
4. Configura APP_ENV=production, APP_DEBUG=false, APP_URL=https://tu-dominio.
5. Configura DB_CONNECTION=mysql y los datos de MySQL local del hosting.
6. Configura SESSION_DRIVER=file, CACHE_STORE=file y QUEUE_CONNECTION=sync para una
   instalación básica, salvo que el hosting tenga Redis u otros servicios.
7. Instala Composer en la app, luego genera APP_KEY con php artisan key:generate.
8. Configura el document root del dominio a apps/project-topicos/public.
9. Ejecuta deployment/cpanel-deploy.sh para el primer despliegue. El script conserva
   .env y datos persistentes de storage/app.
10. Verifica el sitio, login, uploads, permisos y conexión a base de datos.

## Despliegue

El script deployment/cpanel-deploy.sh copia el código sin .env, instala dependencias,
compila assets con npm, crea el enlace de storage, ejecuta migraciones y cachea
configuración y vistas.

Las migraciones se ejecutan con --force. Revisa y respalda la base de datos antes de
desplegar cambios que alteren datos.

## Automatización GitHub -> cPanel

El Git Version Control de cPanel puede desplegar automáticamente cuando recibe un push
a un repositorio cPanel-managed que tenga .cpanel.yml. Sin embargo, clonar GitHub
en cPanel y pulsar Update from Remote no activa por sí solo el despliegue automático
al recibir un push en GitHub: ese flujo suele requerir actualización y Deploy desde
cPanel, o configurar una integración/webhook/cron adicional compatible con el hosting.

Antes de automatizar un webhook, verifica que el proveedor permita SSH, Git, Composer,
Node/npm, rsync y ejecución de scripts en el hook.

## Seguridad

- No subas .env ni credenciales al repositorio.
- Mantén APP_DEBUG=false en producción.
- No apuntes el dominio a la raíz del repositorio; solo a public/.
- No ejecutes migraciones sin respaldo cuando haya datos importantes.
- Habilita SSL/HTTPS.
