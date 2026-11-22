# Deploy project-topicos en Render

## Crear servicio
1. En Render, crea un **Web Service** desde este repositorio.
2. Selecciona la rama `main`.
3. Render usará el `Dockerfile` de la raíz.
4. No necesitas definir Build Command ni Start Command; están configurados en el Dockerfile.
5. Deja que Render asigne la URL pública y luego coloca esa URL en `APP_URL`.

## Variables de entorno
Configura estas variables en **Environment** del servicio:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY`: genera una clave local con `php artisan key:generate --show`
- `APP_URL`: URL HTTPS que Render asigna al servicio
- `LOG_CHANNEL=stderr`
- `LOG_LEVEL=warning`
- `DB_CONNECTION=mysql`
- `DB_HOST`: hostname remoto de tu proveedor MySQL/MariaDB (no localhost)
- `DB_PORT=3306`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `FILESYSTEM_DISK=public`

## Migraciones
El contenedor no ejecuta migraciones automáticamente. Una vez que tengas la base conectada, usa Render Shell:
```bash
php artisan migrate --force
```
Opcionalmente, `RUN_MIGRATIONS=true` ejecutará migraciones al iniciar, pero no es recomendable en despliegues con reinicios frecuentes.

## Persistencia de archivos
El filesystem de Render es efímero. Fotos, documentos y otros archivos guardados localmente pueden perderse en un redeploy o reinicio. Configura un Persistent Disk o almacenamiento externo (S3 compatible) si esos archivos deben conservarse.

## Base de datos de cPanel
La base de cPanel con host `localhost` no es accesible desde Render como `localhost`. Necesitas el hostname remoto que indique tu hosting y que el proveedor permita conexiones entrantes desde Render; la apertura de acceso remoto puede requerir restricciones de firewall/IP.
