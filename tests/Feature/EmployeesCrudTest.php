<?php

namespace Tests\Feature;

use App\Models\admin\Contract;
use App\Models\admin\Employee;
use App\Models\admin\EmployeeType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class EmployeesCrudTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeType $employeeType;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('EmployeesCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->travelTo('2026-10-16 12:00:00');
        $this->actingAs(User::factory()->create());
        $this->employeeType = EmployeeType::create([
            'name' => 'Operador',
            'description' => 'Personal operativo',
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_employee_list_and_actions_follow_the_approved_design(): void
    {
        $employee = $this->createEmployee();

        $this->get(route('personal.employees.index'))
            ->assertOk()
            ->assertSee('Nuevo empleado')
            ->assertSee('class="btn btn-success ms-auto"', false)
            ->assertSee('class="table-responsive"', false)
            ->assertSee('error.status === 422', false)
            ->assertSee('URL.revokeObjectURL', false);

        $adminIndexStyles = file_get_contents(resource_path('css/admin-indexes.css'));
        $this->assertIsString($adminIndexStyles);
        $this->assertStringContainsString('width: 36px;', $adminIndexStyles);
        $this->assertStringContainsString('height: 31px;', $adminIndexStyles);
        $this->assertStringContainsString('var(--bs-body-bg)', $adminIndexStyles);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('personal.employees.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.dni', $employee->dni);

        $actions = (string) $response->json('data.0.actions');

        $this->assertStringContainsString('btn btn-info btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver empleado"', $actions);
        $this->assertStringContainsString(route('personal.employees.show', $employee), $actions);
        $this->assertStringContainsString('btn btn-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar empleado"', $actions);
        $this->assertStringContainsString(route('personal.employees.edit', $employee), $actions);
        $this->assertStringContainsString('btn btn-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('title="Eliminar"', $actions);
        $this->assertStringContainsString('aria-label="Eliminar empleado"', $actions);
        $this->assertStringContainsString(route('personal.employees.destroy', $employee), $actions);
        $this->assertLessThan(strpos($actions, 'btnEditarEmpleado'), strpos($actions, 'btnVerEmpleado'));
        $this->assertLessThan(strpos($actions, 'btnEliminarEmpleado'), strpos($actions, 'btnEditarEmpleado'));
    }

    public function test_authenticated_user_can_register_an_employee_with_an_encrypted_password_and_photo(): void
    {
        $this->postJson(route('personal.employees.store'), $this->validStoreData())
            ->assertOk()
            ->assertJson(['mensaje' => 'Personal registrado correctamente.']);

        $employee = Employee::sole();

        $this->assertSame('12345678', $employee->dni);
        $this->assertSame('Ana', $employee->first_name);
        $this->assertSame('Pérez', $employee->last_name);
        $this->assertSame('Activo', $employee->status);
        $this->assertTrue(Hash::check('contraseña-segura', $employee->password));
        $this->assertNotSame('contraseña-segura', $employee->password);
        Storage::disk('public')->assertExists($employee->profile_photo_path);
    }

    public function test_employee_validation_preserves_uniqueness_age_and_required_rules(): void
    {
        $this->createEmployee();

        $this->postJson(route('personal.employees.store'), [
            'dni' => '12345678',
            'tipo_personal_id' => $this->employeeType->id,
            'nombres' => '',
            'apellidos' => '',
            'fecha_nacimiento' => now()->subYears(17)->toDateString(),
            'email' => 'ana@example.test',
            'password' => 'corta',
            'direccion' => '',
            'foto' => UploadedFile::fake()->create('documento.txt', 2, 'text/plain'),
            'telefono' => '123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'dni',
                'nombres',
                'apellidos',
                'fecha_nacimiento',
                'email',
                'password',
                'direccion',
                'foto',
                'telefono',
            ]);

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_driver_license_is_required_and_must_match_the_dni(): void
    {
        $driverType = EmployeeType::create(['name' => 'Conductor']);

        $this->postJson(route('personal.employees.store'), $this->validStoreData([
            'tipo_personal_id' => $driverType->id,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('licencia');

        $this->postJson(route('personal.employees.store'), $this->validStoreData([
            'tipo_personal_id' => $driverType->id,
            'licencia' => 'Q87654321',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('licencia');

        $this->postJson(route('personal.employees.store'), $this->validStoreData([
            'tipo_personal_id' => $driverType->id,
            'licencia' => 'Q12345678',
        ]))->assertOk();

        $this->assertDatabaseHas('employees', [
            'dni' => '12345678',
            'license' => 'Q12345678',
        ]);
    }

    public function test_employee_can_be_viewed_and_updated_without_replacing_password_or_photo(): void
    {
        $employee = $this->createEmployee();
        $originalPassword = $employee->password;
        $originalPhoto = $employee->profile_photo_path;

        $this->get(route('personal.employees.show', $employee))
            ->assertOk()
            ->assertSee('Ficha del personal')
            ->assertSee('Información del empleado en modo de solo lectura')
            ->assertSee('Ana Pérez')
            ->assertSee('ana@example.test')
            ->assertSee(asset('storage/'.$originalPhoto), false)
            ->assertDontSee('bg-light', false)
            ->assertDontSee('bg-white', false)
            ->assertDontSee('text-dark', false)
            ->assertDontSee('<input', false)
            ->assertDontSee('password', false);

        $this->get(route('personal.employees.edit', $employee))
            ->assertOk()
            ->assertSee('Editar empleado')
            ->assertSee('btn btn-secondary', false)
            ->assertDontSee('btn btn-danger', false);

        $this->putJson(route('personal.employees.update', $employee), $this->validUpdateData([
            'nombres' => 'Ana María',
            'email' => 'ana.maria@example.test',
        ]))->assertOk()
            ->assertJson(['mensaje' => 'Personal actualizado correctamente.']);

        $employee->refresh();

        $this->assertSame('Ana María', $employee->first_name);
        $this->assertSame('ana.maria@example.test', $employee->email);
        $this->assertSame($originalPassword, $employee->password);
        $this->assertSame($originalPhoto, $employee->profile_photo_path);
        Storage::disk('public')->assertExists($originalPhoto);
    }

    public function test_invalid_photo_preserves_the_existing_file_and_valid_replacement_removes_it(): void
    {
        $employee = $this->createEmployee();
        $originalPhoto = $employee->profile_photo_path;

        $this->putJson(route('personal.employees.update', $employee), $this->validUpdateData([
            'foto' => UploadedFile::fake()->create('documento.txt', 2, 'text/plain'),
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('foto');

        $this->assertSame($originalPhoto, $employee->fresh()->profile_photo_path);
        Storage::disk('public')->assertExists($originalPhoto);

        $this->putJson(route('personal.employees.update', $employee), $this->validUpdateData([
            'foto' => UploadedFile::fake()->image('nueva-foto.jpg', 300, 300),
        ]))->assertOk();

        $newPhoto = $employee->fresh()->profile_photo_path;

        $this->assertNotSame($originalPhoto, $newPhoto);
        Storage::disk('public')->assertMissing($originalPhoto);
        Storage::disk('public')->assertExists($newPhoto);
    }

    public function test_current_contract_blocks_deletion_and_change_to_inactive(): void
    {
        $employee = $this->createEmployee();
        $this->createContract($employee, [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $this->deleteJson(route('personal.employees.destroy', $employee))
            ->assertStatus(409)
            ->assertJson([
                'mensaje' => 'No se puede dar de baja al empleado porque tiene un contrato vigente.',
            ]);

        $this->putJson(route('personal.employees.update', $employee), $this->validUpdateData([
            'estado' => 'Inactivo',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('estado');

        $this->assertSame('Activo', $employee->fresh()->status);
        Storage::disk('public')->assertExists($employee->profile_photo_path);
    }

    public function test_historical_contract_converts_deletion_to_inactive_and_keeps_the_photo(): void
    {
        $employee = $this->createEmployee();
        $photo = $employee->profile_photo_path;
        $this->createContract($employee, [
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->subMonths(3)->toDateString(),
        ]);

        $this->deleteJson(route('personal.employees.destroy', $employee))
            ->assertOk()
            ->assertJson([
                'mensaje' => 'El empleado fue marcado como inactivo porque tiene contratos históricos.',
            ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'Inactivo',
        ]);
        Storage::disk('public')->assertExists($photo);
    }

    public function test_employee_without_contracts_is_deleted_with_their_photo(): void
    {
        $employee = $this->createEmployee();
        $photo = $employee->profile_photo_path;

        $this->deleteJson(route('personal.employees.destroy', $employee))
            ->assertOk()
            ->assertJson(['mensaje' => 'Empleado eliminado correctamente.']);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        Storage::disk('public')->assertMissing($photo);
    }

    private function createEmployee(array $overrides = []): Employee
    {
        $photo = 'employees/employee-'.$this->employeeType->id.'.jpg';
        Storage::disk('public')->put($photo, 'contenido-de-prueba');

        return Employee::create(array_merge([
            'employee_type_id' => $this->employeeType->id,
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'birthdate' => '1990-01-01',
            'phone' => '987654321',
            'address' => 'Av. Principal 123',
            'email' => 'ana@example.test',
            'password' => Hash::make('contraseña-original'),
            'status' => 'Activo',
            'profile_photo_path' => $photo,
        ], $overrides));
    }

    private function createContract(Employee $employee, array $overrides = []): Contract
    {
        return Contract::create(array_merge([
            'employee_id' => $employee->id,
            'type' => 'Temporal',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'salary' => 1500,
            'is_active' => true,
        ], $overrides));
    }

    private function validStoreData(array $overrides = []): array
    {
        return array_merge([
            'dni' => '12345678',
            'tipo_personal_id' => $this->employeeType->id,
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'fecha_nacimiento' => '1990-01-01',
            'email' => 'ana@example.test',
            'password' => 'contraseña-segura',
            'direccion' => 'Av. Principal 123',
            'foto' => UploadedFile::fake()->image('empleado.jpg', 300, 300),
            'telefono' => '987654321',
        ], $overrides);
    }

    private function validUpdateData(array $overrides = []): array
    {
        return array_merge([
            'dni' => '12345678',
            'tipo_personal_id' => $this->employeeType->id,
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'fecha_nacimiento' => '1990-01-01',
            'email' => 'ana@example.test',
            'estado' => 'Activo',
            'direccion' => 'Av. Principal 123',
            'telefono' => '987654321',
        ], $overrides);
    }
}
