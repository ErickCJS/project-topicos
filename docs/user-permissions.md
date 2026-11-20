
# Gestión de usuarios, tipos y permisos

## 1. Arquitectura general

El sistema utiliza tres elementos independientes para administrar el acceso de los usuarios:

- `user_types`: catálogo de clasificaciones descriptivas.
- `users.is_admin`: determina si una cuenta tiene acceso administrativo total.
- `user_permissions`: almacena los permisos individuales asignados a cada usuario.

Los tipos de usuario no conceden permisos automáticamente.

Dos usuarios pueden pertenecer al mismo tipo y tener diferentes permisos dentro del sistema.

## 2. Tipos de usuario

La tabla `user_types` contiene:

| Campo | Descripción |
|---|---|
| id | Identificador del tipo |
| name | Nombre de la clasificación |
| description | Descripción opcional |
| created_at | Fecha de creación |
| updated_at | Fecha de actualización |

La tabla `users` utiliza `user_type_id` como clave foránea nullable para relacionar cada cuenta con una clasificación.

Ejemplos de tipos:

- Administrador
- Operador
- Supervisor
- Usuario de prueba

Estos nombres son descriptivos y pueden modificarse desde Gestión de usuarios.

### Reglas

1. Un usuario puede tener un tipo o permanecer sin clasificación.
2. Varios usuarios pueden compartir el mismo tipo.
3. Cambiar el tipo de un usuario no modifica sus permisos.
4. Cambiar el nombre de un tipo actualiza su denominación visible para los usuarios relacionados.
5. Eliminar un tipo no debe eliminar usuarios ni sus permisos. La relación permite dejar `user_type_id` en NULL.

## 3. Acceso administrativo

El campo `users.is_admin` controla el acceso administrativo total.

Un usuario activo con `is_admin = true` dispone de acceso administrativo mediante `UserPermissionService`.

La clasificación descriptiva no determina esta condición.

Por ejemplo:

| Usuario | Tipo | is_admin |
|---|---|---|
| Usuario 1 | Operador | false |
| Usuario 2 | Operador | false |
| Usuario 3 | Supervisor | true |

Los tres mantienen su clasificación independiente del indicador de acceso administrativo.

## 4. Permisos individuales

Los permisos se almacenan en `user_permissions`, mediante la relación entre:

- `user_id`
- `permission_id`

Cada cuenta administra sus propios permisos.

Ejemplo:

| Usuario | Tipo | Permisos asignados |
|---|---|---|
| Usuario 1 | Operador | Vehículos: Ver |
| Usuario 2 | Operador | Vehículos: Ver, Editar |
| Usuario 3 | Supervisor | Programación: Ver |

Compartir un tipo no implica compartir asignaciones.

### Reglas de autorización

- Los administradores activos tienen acceso total.
- Los usuarios normales dependen de sus permisos individuales.
- Los usuarios inactivos no pueden acceder al sistema.
- Modificar un tipo no modifica `user_permissions`.
- La administración de permisos individuales se realiza desde el modal de permisos de cada usuario.

La lógica de autorización se centraliza en:

`app/Services/UserPermissionService.php`

## 5. Gestión de usuarios

La pantalla principal se encuentra en:

`resources/views/admin/user/index.blade.php`

Dispone de tres operaciones principales:

### Nuevo usuario

Registra una cuenta y permite seleccionar un tipo descriptivo.

### Nuevo tipo de usuario

Registra una clasificación en el catálogo `user_types`.

### Gestionar tipos

Permite consultar y editar las clasificaciones registradas.

En los formularios de creación y edición de usuarios se utiliza el campo `user_type_id`.

El checkbox de acceso administrativo total se mantiene independiente del selector de tipos.

## 6. Protección de rutas

La configuración de los permisos se encuentra en:

`config/permissions.php`

Las rutas de administración del catálogo de tipos se encuentran en `admin_only_routes`:

- `user-types.index`
- `user-types.create`
- `user-types.store`
- `user-types.edit`
- `user-types.update`

Estas operaciones requieren acceso administrativo y no se asignan como permisos heredados de los tipos de usuario.

Los permisos individuales del módulo de usuarios siguen siendo independientes.

## 7. Migraciones de base de datos

La migración:

`2026_11_19_000001_restore_descriptive_user_types.php`

restaura el catálogo descriptivo y relaciona los usuarios existentes con sus clasificaciones.

Realiza las siguientes operaciones:

1. Crea `user_types`.
2. Agrega `users.user_type_id`.
3. Recupera las clasificaciones existentes desde `user_type_label`.
4. Crea los tipos correspondientes.
5. Asocia a los usuarios con sus tipos.
6. Elimina el antiguo campo `user_type_label`.

La migración conserva:

- `users.is_admin`.
- `user_permissions`.
- Las asignaciones individuales existentes.

No recupera la antigua tabla `user_type_permissions`.

Las migraciones históricas no deben modificarse para incorporar cambios posteriores.

### Actualización de la base de datos

Después de obtener los cambios del repositorio:

```bash
php artisan migrate
```

Laravel ejecutará las migraciones pendientes.

Para sincronizar el catálogo de permisos con las rutas y configuraciones:

```bash
php artisan permissions:sync-routes
```

Este comando actualiza la correspondencia entre las rutas administrativas y los permisos existentes. No asigna permisos según el tipo descriptivo.

## 8. Seeders

### DefaultAdminUserSeeder

Ubicación:

`database/seeders/DefaultAdminUserSeeder.php`

Crea o recupera la cuenta administrativa inicial y garantiza que tenga una clasificación descriptiva de Administrador.

El acceso total se establece mediante `is_admin`, no mediante el tipo.

### DevelopmentTestUserSeeder

Ubicación:

`database/seeders/DevelopmentTestUserSeeder.php`

Se ejecuta únicamente en entornos `local` y `testing`.

Administra la cuenta de demostración:

`user@gmail.com`

Le asigna una clasificación descriptiva de Usuario de prueba cuando corresponde y sincroniza sus permisos individuales.

No debe utilizarse para crear cuentas de demostración en producción.

Para ejecutarlo manualmente:

```bash
php artisan db:seed --class=DevelopmentTestUserSeeder
```

## 9. API de acceso

El endpoint:

`GET /api/me/access`

mantiene el campo `user_type_label` como denominación descriptiva, obtenida mediante la relación `userType`.

También conserva:

- `is_admin`
- `modulos`
- `permisos`

El nombre del tipo no interviene en el cálculo de autorizaciones.

## 10. Pruebas

La batería principal de pruebas se encuentra en:

`tests/Feature/UserPermissionSystemTest.php`

Incluye verificaciones de:

- Migración histórica de permisos individuales.
- Recuperación del catálogo descriptivo.
- Usuarios con el mismo tipo y distintos permisos.
- Creación, edición y listado de tipos.
- Rechazo de nombres duplicados.
- Selección de tipos en formularios.
- Cambio de clasificación sin modificación de permisos.
- Actualización del nombre de una clasificación.
- Guardado de permisos individuales.
- Respuesta de la API.
- Seeders.
- Protección del último administrador.
- Autenticación y cierre de sesión.

Para ejecutar estas pruebas:

```bash
php artisan test --filter=UserPermissionSystemTest
```

## 11. Consideraciones para GitHub y otros entornos

Los archivos de migración y código deben incorporarse al repositorio.

Cada entorno debe actualizar su código y ejecutar las migraciones pendientes.

No se deben utilizar:

```bash
php artisan migrate:fresh
```

ni operaciones que eliminen las tablas existentes para actualizar esta funcionalidad.

La actualización debe realizarse mediante migraciones incrementales y compatibles con la información existente.
