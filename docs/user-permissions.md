# Gestión de usuarios, tipos y permisos

## 1. Modelo de acceso

El sistema separa cuatro elementos:

- \`user_types\`: catálogo de tipos de usuario que funcionan como plantillas de permisos.
- \`user_type_permissions\`: permisos predeterminados asociados a cada tipo.
- \`user_permissions\`: copia de los permisos efectivos asignados a cada cuenta, que después puede personalizarse individualmente.
- \`users.is_admin\`: indicador independiente que concede acceso administrativo total.

Un tipo de usuario ya no es solamente descriptivo. Define los permisos iniciales de las cuentas que se crean o se reasignan a ese tipo.

## 2. Plantillas de tipos de usuario

Cada tipo tiene un nombre, una descripción y una matriz de permisos organizada por módulos y submódulos.

Ejemplos:

- Administrativo: plantilla inicial con todos los permisos del catálogo.
- Supervisor: puede consultar y gestionar los módulos que se habiliten.
- Operador: recibe únicamente las acciones operativas seleccionadas.

Desde Gestión de usuarios se puede crear o editar un tipo en un modal. La matriz permite seleccionar todos los permisos, deseleccionarlos o configurar un módulo o submódulo concreto.

La tabla \`user_type_permissions\` relaciona:

| Campo | Descripción |
|---|---|
| user_type_id | Tipo de usuario |
| permission_id | Permiso incluido en la plantilla |

## 3. Cómo se aplican los permisos a los usuarios

La plantilla se copia a la cuenta en estos casos:

1. Al crear un usuario y seleccionar un tipo.
2. Al cambiar el tipo de un usuario existente.

La copia se guarda en \`user_permissions\`. Después, los permisos de esa cuenta pueden editarse individualmente desde la matriz de permisos del usuario.

Reglas importantes:

- Editar una plantilla no sobrescribe los permisos de los usuarios que ya estaban asignados a ella.
- Los nuevos usuarios sí reciben la versión actualizada de la plantilla.
- Cuando se cambia un usuario a otro tipo, se reemplaza su asignación individual por la plantilla del nuevo tipo.
- Si se edita un usuario sin cambiar su tipo, sus permisos individuales se conservan.
- La edición de la plantilla no modifica ni elimina las cuentas asociadas.

Este comportamiento permite usar tipos como valores iniciales sin quitar la flexibilidad de personalizar una cuenta.

## 4. Acceso administrativo total

El campo \`users.is_admin\` es independiente de la plantilla.

Una cuenta activa con \`is_admin = true\` tiene acceso administrativo total mediante \`UserPermissionService\`, aunque su tipo sea otro.

El tipo \`Administrativo\` se crea inicialmente con todos los permisos granulares del catálogo, pero no convierte automáticamente a sus usuarios en administradores globales. Su acceso sigue limitado a los permisos de la plantilla y a las reglas de rutas reservadas al administrador del sistema.

## 5. Permisos individuales

Los permisos efectivos copiados a cada usuario se almacenan en \`user_permissions\`, mediante:

- \`user_id\`
- \`permission_id\`

La administración individual sigue disponible desde el botón de permisos del listado de usuarios. Modificar la matriz de una cuenta no cambia la plantilla del tipo ni los permisos de otros usuarios.

La lógica de autorización se centraliza en:

\`app/Services/UserPermissionService.php\`

## 6. Catálogo de módulos y rutas

La configuración está en:

\`config/permissions.php\`

El catálogo define grupos, módulos, acciones y correspondencias con rutas administrativas.

El comando:

\`\`\`bash
php artisan permissions:sync-routes
\`\`\`

sincroniza módulos, permisos y rutas con esa configuración. Debe ejecutarse antes de inicializar la plantilla Administrativo para que el catálogo esté completo.

Las rutas de gestión de usuarios y tipos continúan reservadas al administrador del sistema, independientemente de los permisos de una plantilla.

## 7. Migraciones

La migración:

\`2026_11_19_000001_restore_descriptive_user_types.php\`

restaura la tabla \`user_types\` y la relación \`users.user_type_id\` en las bases de datos que ejecutaron la migración histórica que eliminaba esos elementos.

La nueva migración:

\`2026_11_27_000001_create_user_type_permissions_table.php\`

crea la relación entre tipos y permisos predeterminados.

Para actualizar una base de datos existente:

\`\`\`bash
php artisan migrate
php artisan permissions:sync-routes
php artisan db:seed --class=UserTypePermissionSeeder
\`\`\`

No se debe utilizar \`migrate:fresh\` para esta actualización, porque eliminaría las tablas y los datos existentes.

## 8. Seeders

### DefaultAdminUserSeeder

Crea o recupera la cuenta inicial de administración y le asigna el tipo descriptivo \`Administrador\`. El acceso total se controla con \`users.is_admin\`.

### UserTypePermissionSeeder

Crea el tipo \`Administrativo\` con todos los permisos granulares disponibles en el momento de su creación. La inicialización es idempotente y no vuelve a sobrescribir una plantilla que ya fue personalizada.

### DatabaseSeeder

Después de crear los datos base, ejecuta \`permissions:sync-routes\` y \`UserTypePermissionSeeder\` para dejar disponible la plantilla inicial.

## 9. Interfaz

La gestión se realiza desde:

\`resources/views/admin/user/index.blade.php\`

Las ventanas modales permiten:

- Crear y editar usuarios.
- Crear tipos de usuario.
- Configurar los permisos predeterminados de un tipo.
- Editar los permisos particulares de una cuenta.

La pantalla muestra el número de permisos que tiene configurado cada tipo.

## 10. Pruebas

Las pruebas principales están en:

\`tests/Feature/UserPermissionSystemTest.php\`

Incluyen la persistencia de permisos de tipos, creación de usuarios con copia de plantilla, reasignación de tipo, edición de permisos individuales y protección del último administrador.

Ejecutar:

\`\`\`bash
php artisan test --filter=UserPermissionSystemTest
\`\`\`
