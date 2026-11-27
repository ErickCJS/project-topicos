<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserType;
use Database\Seeders\DefaultAdminUserSeeder;
use Database\Seeders\DevelopmentTestUserSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPermissionSystemTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): void
    {
        Artisan::call('permissions:sync-routes');
    }

    private function createType(string $name): UserType
    {
        return UserType::create([
            'name' => $name,
            'description' => 'Clasificación descriptiva de prueba.',
        ]);
    }

    public function test_transition_migration_copies_individual_permissions_before_dropping_legacy_tables(): void
    {
        $connection = 'permission_migration_test';

        config([
            'database.connections.'.$connection => array_merge(
                config('database.connections.sqlite'),
                ['database' => ':memory:']
            ),
        ]);

        DB::purge($connection);

        $originalDefault = config('database.default');

        config(['database.default' => $connection]);

        try {
            Schema::create('user_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->boolean('is_admin');
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->foreignId('user_type_id')
                    ->nullable()
                    ->constrained('user_types');
            });

            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
            });

            Schema::create('user_type_permissions', function (Blueprint $table) {
                $table->foreignId('user_type_id')
                    ->constrained('user_types')
                    ->cascadeOnDelete();

                $table->foreignId('permission_id')
                    ->constrained('permissions')
                    ->cascadeOnDelete();

                $table->primary(['user_type_id', 'permission_id']);
            });

            DB::table('user_types')->insert([
                [
                    'id' => 1,
                    'name' => 'Administrador',
                    'slug' => 'admin',
                    'is_admin' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => 2,
                    'name' => 'Operaciones',
                    'slug' => 'ops',
                    'is_admin' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            DB::table('users')->insert([
                [
                    'id' => 1,
                    'email' => 'admin@example.test',
                    'user_type_id' => 1,
                ],
                [
                    'id' => 2,
                    'email' => 'one@example.test',
                    'user_type_id' => 2,
                ],
                [
                    'id' => 3,
                    'email' => 'user@gmail.com',
                    'user_type_id' => 2,
                ],
            ]);

            DB::table('permissions')->insert([
                ['id' => 10],
                ['id' => 11],
            ]);

            DB::table('user_type_permissions')->insert([
                ['user_type_id' => 1, 'permission_id' => 10],
                ['user_type_id' => 1, 'permission_id' => 11],
                ['user_type_id' => 2, 'permission_id' => 10],
            ]);

            $migration = require base_path(
                'database/migrations/2026_11_14_000001_convert_user_types_to_individual_permissions.php'
            );

            $migration->up();

            $this->assertDatabaseHas('users', [
                'id' => 1,
                'is_admin' => true,
                'user_type_label' => 'Administrador',
            ]);

            $this->assertDatabaseHas('users', [
                'id' => 3,
                'is_admin' => false,
                'user_type_label' => 'Operaciones',
            ]);

            $this->assertDatabaseHas('user_permissions', [
                'user_id' => 1,
                'permission_id' => 11,
            ]);

            $this->assertDatabaseHas('user_permissions', [
                'user_id' => 2,
                'permission_id' => 10,
            ]);

            $this->assertDatabaseHas('user_permissions', [
                'user_id' => 3,
                'permission_id' => 10,
            ]);

            $this->assertFalse(Schema::hasTable('user_types'));
            $this->assertFalse(Schema::hasTable('user_type_permissions'));
            $this->assertFalse(Schema::hasColumn('users', 'user_type_id'));

        } finally {
            config(['database.default' => $originalDefault]);
            DB::purge($connection);
        }
    }

    public function test_new_migration_restores_descriptive_types_without_affecting_individual_permissions(): void
    {
        $connection = 'descriptive_type_migration_test';

        config([
            'database.connections.'.$connection => array_merge(
                config('database.connections.sqlite'),
                ['database' => ':memory:']
            ),
        ]);

        DB::purge($connection);

        $originalDefault = config('database.default');

        config(['database.default' => $connection]);

        try {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email');
                $table->boolean('is_admin')->default(false);
                $table->string('user_type_label', 100)->nullable();
            });

            Schema::create('user_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('permission_id');
                $table->primary(['user_id', 'permission_id']);
            });

            DB::table('users')->insert([
                [
                    'id' => 1,
                    'email' => 'one@example.test',
                    'is_admin' => false,
                    'user_type_label' => 'Operador',
                ],
                [
                    'id' => 2,
                    'email' => 'two@example.test',
                    'is_admin' => false,
                    'user_type_label' => 'Operador',
                ],
                [
                    'id' => 3,
                    'email' => 'admin@example.test',
                    'is_admin' => true,
                    'user_type_label' => 'Administrador',
                ],
            ]);

            DB::table('user_permissions')->insert([
                ['user_id' => 1, 'permission_id' => 10],
                ['user_id' => 2, 'permission_id' => 11],
            ]);

            $migration = require base_path(
                'database/migrations/2026_11_19_000001_restore_descriptive_user_types.php'
            );

            $migration->up();

            $this->assertTrue(Schema::hasTable('user_types'));
            $this->assertTrue(Schema::hasColumn('users', 'user_type_id'));
            $this->assertFalse(Schema::hasColumn('users', 'user_type_label'));

            $operatorId = DB::table('user_types')
                ->where('name', 'Operador')
                ->value('id');

            $this->assertNotNull($operatorId);

            $this->assertDatabaseHas('users', [
                'id' => 1,
                'user_type_id' => $operatorId,
                'is_admin' => false,
            ]);

            $this->assertDatabaseHas('users', [
                'id' => 2,
                'user_type_id' => $operatorId,
                'is_admin' => false,
            ]);

            $this->assertDatabaseHas('users', [
                'id' => 3,
                'is_admin' => true,
            ]);

            $this->assertDatabaseHas('user_permissions', [
                'user_id' => 1,
                'permission_id' => 10,
            ]);

            $this->assertDatabaseHas('user_permissions', [
                'user_id' => 2,
                'permission_id' => 11,
            ]);

            $this->assertDatabaseCount('user_types', 2);

            $migration->down();

            $this->assertFalse(Schema::hasTable('user_types'));
            $this->assertFalse(Schema::hasColumn('users', 'user_type_id'));
            $this->assertTrue(Schema::hasColumn('users', 'user_type_label'));

            $this->assertDatabaseHas('users', [
                'id' => 1,
                'user_type_label' => 'Operador',
                'is_admin' => false,
            ]);

            $this->assertDatabaseHas('users', [
                'id' => 3,
                'user_type_label' => 'Administrador',
                'is_admin' => true,
            ]);

        } finally {
            config(['database.default' => $originalDefault]);
            DB::purge($connection);
        }
    }

    public function test_users_with_the_same_type_have_independent_permissions(): void
    {
        $this->catalog();

        $type = $this->createType('Operador');

        $first = User::factory()->regular()->create([
            'user_type_id' => $type->id,
        ]);

        $second = User::factory()->regular()->create([
            'user_type_id' => $type->id,
        ]);

        $permission = Permission::where(
            'slug',
            'vehiculos.view'
        )->firstOrFail();

        $first->permissions()->sync([$permission->id]);

        $service = app(\App\Services\UserPermissionService::class);

        $this->assertTrue($service->allows($first, 'vehiculos.view'));
        $this->assertFalse($service->allows($second, 'vehiculos.view'));

        $this->assertSame($type->id, $first->fresh()->user_type_id);
        $this->assertSame($type->id, $second->fresh()->user_type_id);

        $newUser = User::factory()->regular()->create();

        $this->assertSame(
            [],
            $newUser->permissions()->pluck('slug')->all()
        );

        $admin = User::factory()->create();

        $this->assertTrue($service->isAdministrator($admin));
        $this->assertTrue($service->allows($admin, 'cualquier.permiso'));
    }

    public function test_administrator_can_create_and_edit_descriptive_user_types(): void
    {
        $this->catalog();

        $admin = User::factory()->create();

        $viewPermission = Permission::where('slug', 'vehiculos.view')->firstOrFail();
        $editPermission = Permission::where('slug', 'vehiculos.details')->firstOrFail();

        $this->actingAs($admin)
            ->postJson(route('user-types.store'), [
                'name' => 'Supervisor',
                'description' => 'Personal de supervisión.',
                'permissions' => [$viewPermission->id],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Tipo de usuario registrado correctamente.',
            ]);

        $type = UserType::where('name', 'Supervisor')->firstOrFail();

        $this->actingAs($admin)
            ->putJson(route('user-types.update', $type->id), [
                'name' => 'Supervisor General',
                'description' => 'Supervisión general del sistema.',
                'permissions' => [$editPermission->id],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Tipo de usuario actualizado correctamente.',
            ]);

        $this->assertDatabaseHas('user_types', [
            'id' => $type->id,
            'name' => 'Supervisor General',
            'description' => 'Supervisión general del sistema.',
        ]);

        $this->assertSame(
            [$editPermission->id],
            $type->fresh()->permissions()->pluck('permissions.id')->all()
        );
    }

    public function test_duplicate_user_type_names_are_rejected(): void
    {
        $this->catalog();

        $admin = User::factory()->create();

        $this->createType('Operador');

        $this->actingAs($admin)
            ->postJson(route('user-types.store'), [
                'name' => 'Operador',
                'description' => 'Otro tipo.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('user_types', 1);
    }

    public function test_user_type_listing_can_be_loaded_as_modal_and_datatable(): void
    {
        $this->catalog();

        $admin = User::factory()->create();

        $this->createType('Operador');

        $this->actingAs($admin)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Modal-Request' => '1',
            ])
            ->get(route('user-types.index'))
            ->assertOk()
            ->assertSee('userTypesTable');
            
        $this->actingAs($admin)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Modal-Request' => '',
            ])
            ->get(route('user-types.index'))
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Operador',
            ]);
        
    }

    public function test_user_creation_and_edit_forms_include_descriptive_type_selector(): void
    {
        $this->catalog();

        $admin = User::factory()->create();

        $type = $this->createType('Operador');

        $user = User::factory()->regular()->create([
            'user_type_id' => $type->id,
        ]);

        $this->actingAs($admin)
            ->withHeaders(['X-Modal-Request' => '1'])
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('user_type_id')
            ->assertSee('Operador');

        $this->actingAs($admin)
            ->withHeaders(['X-Modal-Request' => '1'])
            ->get(route('users.edit', $user))
            ->assertOk()
            ->assertSee('user_type_id')
            ->assertSee('Operador');
    }

    public function test_changing_user_type_applies_the_new_type_permission_template(): void
    {
        $this->catalog();

        $admin = User::factory()->create();

        $firstType = $this->createType('Operador');
        $secondType = $this->createType('Supervisor');

        $firstPermission = Permission::where('slug', 'vehiculos.details')->firstOrFail();
        $secondPermission = Permission::where('slug', 'dashboard.view')->firstOrFail();

        $firstType->permissions()->sync([$firstPermission->id]);
        $secondType->permissions()->sync([$secondPermission->id]);

        $user = User::factory()->regular()->create([
            'dni' => '12345678',
            'user_type_id' => $firstType->id,
        ]);
        $user->permissions()->sync([$firstPermission->id]);

        $this->actingAs($admin)
            ->putJson(route('users.update', $user), [
                'name' => $user->name,
                'last_name' => $user->last_name,
                'dni' => $user->dni,
                'email' => $user->email,
                'user_type_id' => $secondType->id,
                'is_admin' => false,
                'is_active' => true,
            ])
            ->assertOk();

        $user->refresh();

        $this->assertSame($secondType->id, $user->user_type_id);
        $this->assertSame(
            [$secondPermission->id],
            $user->permissions()->pluck('permissions.id')->all()
        );

        $service = app(\App\Services\UserPermissionService::class);
        $this->assertTrue($service->allows($user, 'dashboard.view'));
        $this->assertFalse($service->allows($user, 'vehiculos.details'));
    }

    public function test_creating_user_copies_the_selected_type_permission_template(): void
    {
        $this->catalog();

        $admin = User::factory()->create();
        $type = $this->createType('Operador de campo');
        $permissions = Permission::whereIn('slug', [
            'dashboard.view',
            'vehiculos.view',
        ])->orderBy('id')->pluck('id')->all();

        $type->permissions()->sync($permissions);

        $this->actingAs($admin)
            ->postJson(route('users.store'), [
                'name' => 'Usuario nuevo',
                'last_name' => 'De plantilla',
                'dni' => '76543210',
                'email' => 'plantilla@example.test',
                'password' => 'Password123',
                'user_type_id' => $type->id,
                'is_admin' => false,
                'is_active' => true,
            ])
            ->assertCreated();

        $createdUser = User::where('email', 'plantilla@example.test')->firstOrFail();

        $this->assertSame(
            $permissions,
            $createdUser->permissions()->orderBy('permissions.id')->pluck('permissions.id')->all()
        );
    }

    public function test_renaming_type_updates_associated_users_without_changing_permissions(): void
    {
        $this->catalog();

        $admin = User::factory()->create();

        $type = $this->createType('Operador');

        $user = User::factory()->regular()->create([
            'user_type_id' => $type->id,
        ]);

        $permission = Permission::where(
            'slug',
            'vehiculos.view'
        )->firstOrFail();

        $user->permissions()->sync([$permission->id]);

        $this->actingAs($admin)
            ->putJson(route('user-types.update', $type->id), [
                'name' => 'Operador General',
                'description' => 'Clasificación actualizada.',
            ])
            ->assertOk();

        $user->refresh();

        $this->assertSame('Operador General', $user->user_type_label);

        $this->assertSame(
            [$permission->id],
            $user->permissions()->pluck('permissions.id')->all()
        );
    }

    public function test_administrator_can_save_permissions_for_one_user(): void
    {
        $this->catalog();

        $admin = User::factory()->create();

        $user = User::factory()->regular()->create();

        $permission = Permission::where(
            'slug',
            'vehiculos.details'
        )->firstOrFail();

        $this->actingAs($admin)
            ->withHeaders([
                'X-Modal-Request' => '1',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('users.permissions', $user))
            ->assertOk()
            ->assertSee('permissionMatrixForm');

        $this->actingAs($admin)
            ->putJson(route('users.permissions.save', $user), [
                'permissions' => [$permission->id],
            ])
            ->assertOk();

        $this->assertSame(
            [$permission->id],
            $user->fresh()->permissions()->pluck('permissions.id')->all()
        );
    }

    public function test_user_index_displays_type_separately_from_administrative_access(): void
    {
        $admin = User::factory()->create();

        $adminType = $this->createType('Administrador');
        $operatorType = $this->createType('Operador');

        User::factory()->create([
            'user_type_id' => $adminType->id,
        ]);

        $operator = User::factory()->regular()->create([
            'user_type_id' => $operatorType->id,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('users.index'));

        $response->assertOk();

        $data = $response->json('data');

        $operatorRow = collect($data)->firstWhere(
            'email',
            $operator->email
        );

        $this->assertNotNull($operatorRow);

        $this->assertStringContainsString(
            'Operador',
            $operatorRow['type']
        );

        $this->assertStringNotContainsString(
            'Acceso total',
            $operatorRow['type']
        );
    }

    public function test_access_api_returns_individual_permissions_and_descriptive_type(): void
    {
        $this->catalog();

        $type = $this->createType('Operador');

        $user = User::factory()->regular()->create([
            'user_type_id' => $type->id,
        ]);

        $permission = Permission::where(
            'slug',
            'dashboard.view'
        )->firstOrFail();

        $user->permissions()->sync([$permission->id]);

        $this->actingAs($user)
            ->getJson('/api/me/access')
            ->assertOk()
            ->assertJsonPath('is_admin', false)
            ->assertJsonPath('user_type_label', 'Operador')
            ->assertJsonPath('permisos', ['dashboard.view']);
    }

    public function test_default_administrator_seeder_assigns_descriptive_type_and_total_access(): void
    {
        $this->seed(DefaultAdminUserSeeder::class);

        $type = UserType::where('name', 'Administrador')->firstOrFail();

        $admin = User::where(
            'email',
            'administrador@rsu.local'
        )->firstOrFail();

        $this->assertTrue($admin->is_admin);
        $this->assertTrue($admin->is_active);

        $this->assertSame($type->id, $admin->user_type_id);
        $this->assertSame('Administrador', $admin->user_type_label);
    }

    public function test_administrative_type_starts_with_all_permissions(): void
    {
        $this->catalog();

        $this->seed(\Database\Seeders\UserTypePermissionSeeder::class);

        $type = UserType::where('name', 'Administrador')->firstOrFail();

        $this->assertGreaterThan(0, Permission::count());
        $this->assertSame(
            Permission::orderBy('id')->pluck('id')->all(),
            $type->permissions()->orderBy('permissions.id')->pluck('permissions.id')->all()
        );

        $this->assertTrue($type->fresh()->permissions_configured);

        // Una plantilla guardada vacía también debe conservarse al repetir el seeder.
        $type->permissions()->sync([]);
        $type->forceFill(['permissions_configured' => true])->save();

        $this->seed(\Database\Seeders\\UserTypePermissionSeeder::class);

        $this->assertSame(
            [],
            $type->fresh()->permissions()->pluck('permissions.id')->all()
        );
    }

    public function test_test_user_seeder_assigns_individual_permissions_without_changing_existing_data(): void
    {
        $this->catalog();

        $user = User::factory()->create([
            'email' => 'user@gmail.com',
            'password' => 'OriginalPass123',
            'name' => 'Conservado',
        ]);

        $original = $user->fresh()->only([
            'password',
            'name',
            'email',
        ]);

        $this->seed(DevelopmentTestUserSeeder::class);

        $user = $user->fresh();

        $this->assertSame(
            $original,
            $user->only(array_keys($original))
        );

        $this->assertFalse($user->is_admin);

        $this->assertSame(
            [
                'dashboard.view',
                'vehiculos.details',
                'vehiculos.view',
            ],
            $user->permissions()
                ->orderBy('slug')
                ->pluck('slug')
                ->all()
        );

        $this->assertSame(
            'Usuario de prueba',
            $user->user_type_label
        );
    }

    public function test_last_active_administrator_cannot_be_disabled_demoted_or_deleted(): void
    {
        $admin = User::factory()->create([
            'dni' => '87654321',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('users.status', $admin), [
                'is_active' => false,
            ])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->putJson(route('users.update', $admin), [
                'name' => $admin->name,
                'last_name' => $admin->last_name,
                'dni' => $admin->dni,
                'email' => $admin->email,
                'is_admin' => false,
                'is_active' => true,
            ])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->deleteJson(route('users.destroy', $admin))
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
            'is_admin' => true,
        ]);
    }

    public function test_administrators_cannot_be_deleted_and_regular_users_can_authenticate_and_logout(): void
    {
        $this->catalog();

        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();

        $this->actingAs($admin)
            ->deleteJson(route('users.destroy', $otherAdmin))
            ->assertUnprocessable();

        $this->post(route('logout'));

        $this->assertGuest();

        $user = User::factory()->regular()->create();

        $user->permissions()->sync(
            Permission::whereIn('slug', [
                'dashboard.view',
                'vehiculos.view',
            ])->pluck('id')
        );

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('vehicles.index'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }
}
