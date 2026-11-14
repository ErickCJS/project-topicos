# Gestión de usuarios y permisos

Después de obtener la rama, ejecutar las migraciones incrementales y sincronizar las rutas:

```sh
php artisan migrate
php artisan permissions:sync-routes
```

La migración de asignación inicial crea o recupera el tipo protegido Administrador y lo asigna a todas las cuentas que ya existen. En entornos `local` y `testing`, `user@gmail.com` conserva el tipo Usuario de prueba cuando ya está registrado. La sincronización de rutas no cambia asignaciones de usuarios, por lo que las cuentas creadas después mantienen el tipo que eligió el administrador.

En entornos `local` o `testing`, se puede crear o actualizar la cuenta de demostración con:

```sh
php artisan db:seed --class=DevelopmentTestUserSeeder
```

La cuenta de demostración no se crea en producción.
