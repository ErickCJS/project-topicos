<?php

namespace Tests\Feature;

use App\Models\admin\EmployeeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class EmployeeTypesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('EmployeeTypesCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_index_and_actions_follow_the_approved_design(): void
    {
        $type = $this->createEmployeeType();

        $this->get(route('employee-types.index'))
            ->assertOk()
            ->assertSee('Listado de Tipos de Personal')
            ->assertSee('class="btn btn-success ms-auto"', false)
            ->assertSee('class="table-responsive"', false)
            ->assertSee('id="employeeTypeShowModal"', false)
            ->assertSee('error.status === 422', false);

        $adminIndexStyles = file_get_contents(resource_path('css/admin-indexes.css'));
        $this->assertIsString($adminIndexStyles);
        $this->assertStringContainsString('height: 31px;', $adminIndexStyles);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('employee-types.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $actions = (string) $response->json('data.0.actions');

        $this->assertStringNotContainsString('fas fa-', $actions);
        $this->assertStringContainsString('btn btn-outline-primary btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver tipo de personal"', $actions);
        $this->assertStringContainsString(route('employee-types.show', $type), $actions);
        $this->assertStringContainsString('btn btn-outline-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar tipo de personal"', $actions);
        $this->assertStringContainsString(route('employee-types.edit', $type), $actions);
        $this->assertStringContainsString('btn btn-outline-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('title="Eliminar"', $actions);
        $this->assertStringContainsString('aria-label="Eliminar tipo de personal"', $actions);
        $this->assertStringContainsString(route('employee-types.destroy', $type), $actions);
        $this->assertLessThan(strpos($actions, 'btnEditar'), strpos($actions, 'btnVer'));
        $this->assertLessThan(strpos($actions, 'btnEliminar'), strpos($actions, 'btnEditar'));
    }

    public function test_show_is_read_only_and_missing_types_return_404(): void
    {
        $type = $this->createEmployeeType();

        $this->get(route('employee-types.show', $type))
            ->assertOk()
            ->assertSee('Detalle del tipo de personal')
            ->assertSee('Nombre')
            ->assertSee('Operador')
            ->assertSee('Personal operativo')
            ->assertSee($type->created_at->format('d/m/Y H:i:s'))
            ->assertSee($type->updated_at->format('d/m/Y H:i:s'))
            ->assertDontSee('<form', false)
            ->assertDontSee('<input', false)
            ->assertDontSee('<textarea', false)
            ->assertDontSee('<select', false);

        $this->getJson(route('employee-types.show', 999999))->assertNotFound();
    }

    public function test_forms_keep_limits_and_use_secondary_cancel_with_bootstrap_icons(): void
    {
        $this->get(route('employee-types.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('maxlength="50"', false)
            ->assertSee('name="description"', false)
            ->assertSee('maxlength="255"', false)
            ->assertSee('btn btn-secondary', false)
            ->assertSee('bi bi-floppy', false)
            ->assertSee('bi bi-x-circle', false)
            ->assertDontSee('fas fa-', false);

        $type = $this->createEmployeeType();

        $this->get(route('employee-types.edit', $type))
            ->assertOk()
            ->assertSee(route('employee-types.update', $type), false)
            ->assertSee('Operador')
            ->assertSee('Personal operativo')
            ->assertSee('Actualizar');
    }

    public function test_validation_errors_return_422_and_existing_name_is_allowed_on_update(): void
    {
        $type = $this->createEmployeeType();

        $this->postJson(route('employee-types.store'), [
            'name' => '',
            'description' => str_repeat('d', 256),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description']);

        $this->postJson(route('employee-types.store'), $this->validData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->putJson(route('employee-types.update', $type), $this->validData([
            'description' => 'Descripción actualizada',
        ]))->assertOk();

        $this->assertDatabaseHas('employee_types', [
            'id' => $type->id,
            'name' => 'Operador',
            'description' => 'Descripción actualizada',
        ]);
    }

    public function test_type_with_related_employees_cannot_be_deleted(): void
    {
        $type = $this->createEmployeeType();

        DB::table('employees')->insert([
            'employee_type_id' => $type->id,
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'birthdate' => '1990-01-01',
            'email' => 'ana@example.test',
            'password' => 'password-de-prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson(route('employee-types.destroy', $type))
            ->assertStatus(409)
            ->assertJson([
                'message' => 'No se puede eliminar porque tiene personal relacionado',
            ]);

        $this->assertDatabaseHas('employee_types', ['id' => $type->id]);
    }

    public function test_base_employee_types_cannot_be_deleted(): void
    {
        foreach (['Conductor', 'Ayudante'] as $name) {
            $type = $this->createEmployeeType([
                'name' => $name,
            ]);

            $this->deleteJson(route('employee-types.destroy', $type))
                ->assertStatus(409)
                ->assertJson([
                    'message' => 'Conductor y Ayudante son tipos de personal permanentes y no pueden eliminarse.',
                ]);

            $this->assertDatabaseHas('employee_types', [
                'id' => $type->id,
                'name' => $name,
            ]);
        }
    }

    public function test_custom_employee_type_without_employees_can_be_deleted(): void
    {
        $type = $this->createEmployeeType([
            'name' => 'Supervisor',
        ]);

        $this->deleteJson(route('employee-types.destroy', $type))
            ->assertOk()
            ->assertJson([
                'message' => 'Tipo de personal eliminado correctamente',
            ]);

        $this->assertDatabaseMissing('employee_types', [
            'id' => $type->id,
        ]);
    }
    private function createEmployeeType(array $overrides = []): EmployeeType
    {
        return EmployeeType::create($this->validData($overrides));
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Operador',
            'description' => 'Personal operativo',
        ], $overrides);
    }
}
