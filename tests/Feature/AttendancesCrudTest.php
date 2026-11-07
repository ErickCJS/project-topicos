<?php

namespace Tests\Feature;

use App\Models\admin\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AttendancesCrudTest extends TestCase
{
    use RefreshDatabase;

    private int $employeeId;

    private int $shiftId;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('AttendancesCrudTest solo puede ejecutarse con SQLite en memoria.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $employeeTypeId = DB::table('employee_types')->insertGetId([
            'name' => 'Conductor',
            'description' => 'Tipo para pruebas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->employeeId = DB::table('employees')->insertGetId([
            'employee_type_id' => $employeeTypeId,
            'dni' => '12345678',
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'birthdate' => '1990-01-01',
            'phone' => '999999999',
            'address' => 'Dirección reservada',
            'email' => 'ana@example.test',
            'password' => 'password-hash-no-exponer',
            'status' => 'Activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contracts')->insert([
            'employee_id' => $this->employeeId,
            'type' => 'Permanente',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'salary' => 1500,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->shiftId = DB::table('shifts')->insertGetId([
            'name' => 'Mañana',
            'start_time' => '05:00:00',
            'end_time' => '13:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createProgramming('2026-10-16');
        $this->createProgramming('2026-10-17');
    }

    public function test_authenticated_user_can_list_attendances(): void
    {
        $attendance = $this->createAttendance();

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Listado de Asistencias');

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('attendances.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.id', $attendance->id)
            ->assertJsonPath('data.0.dni', '12345678')
            ->assertJsonPath('data.0.employee_name', 'Ana Pérez')
            ->assertJsonPath('data.0.shift_name', 'Mañana');
    }

    public function test_authenticated_user_can_query_daily_attendance_history(): void
    {
        $this->createAttendance();

        $this->getJson(route('attendances.history', [
            'employee_id' => $this->employeeId,
            'date' => '2026-10-16',
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.time', '08:30')
            ->assertJsonPath('0.type', 'Entrada')
            ->assertJsonPath('0.status', 'Presente')
            ->assertJsonPath('0.shift', 'Mañana');
    }

    public function test_daily_attendance_history_validates_employee_and_date(): void
    {
        $this->getJson(route('attendances.history', [
            'employee_id' => 999999,
            'date' => '16/10/2026',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_id', 'date']);
    }

    public function test_daily_attendance_history_is_separated_by_employee(): void
    {
        $otherEmployeeId = $this->createOtherEmployee();
        $this->createAttendance();
        $this->createAttendance(['employee_id' => $otherEmployeeId, 'time' => '09:15']);

        $this->getJson(route('attendances.history', [
            'employee_id' => $this->employeeId,
            'date' => '2026-10-16',
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.time', '08:30');
    }

    public function test_datatables_can_search_employee_last_name(): void
    {
        $otherEmployeeId = $this->createOtherEmployee();
        $this->createAttendance();
        $this->createAttendance(['employee_id' => $otherEmployeeId]);

        $columns = [
            ['data' => 'dni', 'name' => 'employees.dni', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'employee_name', 'name' => 'employee_name', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'date_time', 'name' => 'attendances.date', 'searchable' => 'false', 'orderable' => 'true'],
            ['data' => 'type', 'name' => 'attendances.type', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'status', 'name' => 'attendances.status', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'shift_name', 'name' => 'shifts.name', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'notes', 'name' => 'attendances.notes', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'actions', 'name' => 'actions', 'searchable' => 'false', 'orderable' => 'false'],
        ];

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('attendances.index', [
                'draw' => 1, 'start' => 0, 'length' => 10,
                'columns' => $columns,
                'search' => ['value' => 'Gomez', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.employee_name', 'Luis Gomez');
    }

    public function test_datatables_can_search_employee_full_name(): void
    {
        $otherEmployeeId = $this->createOtherEmployee();
        $this->createAttendance();
        $this->createAttendance(['employee_id' => $otherEmployeeId]);

        $columns = [
            ['data' => 'dni', 'name' => 'employees.dni', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'employee_name', 'name' => 'employee_name', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'date_time', 'name' => 'attendances.date', 'searchable' => 'false', 'orderable' => 'true'],
            ['data' => 'type', 'name' => 'attendances.type', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'status', 'name' => 'attendances.status', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'shift_name', 'name' => 'shifts.name', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'notes', 'name' => 'attendances.notes', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'actions', 'name' => 'actions', 'searchable' => 'false', 'orderable' => 'false'],
        ];

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('attendances.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => $columns,
                'search' => ['value' => 'Luis Gomez', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.employee_name', 'Luis Gomez');
    }

    public function test_datatables_can_order_employees_by_last_name(): void
    {
        $otherEmployeeId = $this->createOtherEmployee();

        $anaAttendance = $this->createAttendance();
        $luisAttendance = $this->createAttendance([
            'employee_id' => $otherEmployeeId,
        ]);

        $columns = [
            ['data' => 'dni', 'name' => 'employees.dni', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'employee_name', 'name' => 'employee_name', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'date_time', 'name' => 'attendances.date', 'searchable' => 'false', 'orderable' => 'true'],
            ['data' => 'type', 'name' => 'attendances.type', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'status', 'name' => 'attendances.status', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'shift_name', 'name' => 'shifts.name', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'notes', 'name' => 'attendances.notes', 'searchable' => 'true', 'orderable' => 'true'],
            ['data' => 'actions', 'name' => 'actions', 'searchable' => 'false', 'orderable' => 'false'],
        ];

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('attendances.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => $columns,
                'order' => [
                    ['column' => 1, 'dir' => 'asc'],
                ],
                'search' => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2)
            ->assertJsonPath('data.0.id', $luisAttendance->id)
            ->assertJsonPath('data.1.id', $anaAttendance->id);
    }

    public function test_daily_attendance_history_excludes_other_dates(): void
    {
        $this->createAttendance();

        $this->createAttendance([
            'date' => '2026-10-17',
            'time' => '09:15',
        ]);

        $this->getJson(route('attendances.history', [
            'employee_id' => $this->employeeId,
            'date' => '2026-10-16',
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.time', '08:30');
    }

    public function test_authenticated_user_can_store_a_valid_attendance(): void
    {
        $this->postJson(route('attendances.store'), $this->validData())
            ->assertCreated()
            ->assertJson(['mensaje' => 'Asistencia registrada correctamente.']);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeId,
            'shift_id' => $this->shiftId,
            'time' => '08:30',
            'type' => 'Entrada',
            'status' => 'Presente',
            'notes' => 'Observación de prueba',
        ]);
        $this->assertSame('2026-10-16', Attendance::first()->date->format('Y-m-d'));
    }

    public function test_attendance_requires_a_programmed_assignment_for_the_same_date_and_shift(): void
    {
        $this->postJson(route('attendances.store'), $this->validData(['date' => '2026-10-18']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employee_id');
        $this->assertDatabaseCount('attendances', 0);

        $otherShiftId = DB::table('shifts')->insertGetId([
            'name' => 'Noche', 'start_time' => '14:00:00', 'end_time' => '22:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->createProgramming('2026-10-19', 'Programado', null, $this->employeeId, $otherShiftId);
        $this->postJson(route('attendances.store'), $this->validData(['date' => '2026-10-19']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employee_id');
    }

    public function test_attendance_accepts_a_helper_assignment_and_finished_historical_programming(): void
    {
        $helperId = $this->createOtherEmployee();
        DB::table('contracts')->insert([
            'employee_id' => $helperId, 'type' => 'Permanente', 'start_date' => '2026-01-01',
            'end_date' => null, 'salary' => 1500, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $programmingId = DB::table('programmings')->where('date', '2026-10-16')->value('id');
        DB::table('programming_employees')->insert([
            'programming_id' => $programmingId, 'employee_id' => $helperId, 'shift_id' => $this->shiftId,
            'date' => '2026-10-16', 'role' => 'helper',
        ]);

        $this->postJson(route('attendances.store'), $this->validData(['employee_id' => $helperId]))
            ->assertCreated();

        $this->createProgramming('2026-10-20', 'Finalizado');
        $this->postJson(route('attendances.store'), $this->validData(['date' => '2026-10-20']))
            ->assertCreated();
    }

    public function test_attendance_rejects_annulled_and_soft_deleted_programmings(): void
    {
        $this->createProgramming('2026-10-18', 'Anulado');
        $this->createProgramming('2026-10-19', 'Programado', now()->toDateTimeString());

        foreach (['2026-10-18', '2026-10-19'] as $date) {
            $this->postJson(route('attendances.store'), $this->validData(['date' => $date]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('employee_id');
        }
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_create_and_edit_forms_use_attendance_fields_and_existing_catalogs(): void
    {
        $this->get(route('attendances.create'))
            ->assertOk()
            ->assertSee('12345678 - Ana Pérez')
            ->assertSee('Mañana (05:00 - 13:00)')
            ->assertSee('name="employee_id"', false)
            ->assertSee('name="shift_id"', false)
            ->assertSee('name="date"', false)
            ->assertSee('name="time"', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="notes"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('/admin/brands', false);

        $attendance = $this->createAttendance();

        $this->get(route('attendances.edit', $attendance))
            ->assertOk()
            ->assertSee(route('attendances.update', $attendance), false)
            ->assertSee('2026-10-16')
            ->assertSee('08:30')
            ->assertSee('Observación de prueba')
            ->assertDontSee('/admin/brands', false);
    }

    public function test_invalid_attendance_data_is_rejected(): void
    {
        $this->postJson(route('attendances.store'), [
            'employee_id' => 999999,
            'shift_id' => 999999,
            'date' => '16/10/2026',
            'time' => '25:61',
            'type' => 'Ingreso',
            'status' => 'Tardanza',
            'notes' => ['valor no válido'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'employee_id',
                'shift_id',
                'date',
                'time',
                'type',
                'status',
                'notes',
            ]);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_authenticated_user_can_view_an_existing_attendance_without_sensitive_data(): void
    {
        $attendance = $this->createAttendance();

        $this->get(route('attendances.show', $attendance))
            ->assertOk()
            ->assertSee('Detalle de asistencia')
            ->assertSee('12345678 - Ana Pérez')
            ->assertSee('Mañana')
            ->assertSee('Entrada')
            ->assertSee('Presente')
            ->assertSee('Observación de prueba')
            ->assertDontSee('password-hash-no-exponer')
            ->assertDontSee('ana@example.test')
            ->assertDontSee('Dirección reservada');
    }

    public function test_authenticated_user_can_update_an_attendance(): void
    {
        $attendance = $this->createAttendance();
        $updated = $this->validData([
            'date' => '2026-10-17',
            'time' => '13:15',
            'type' => 'Salida',
            'status' => 'Ausente',
            'notes' => 'Asistencia actualizada',
        ]);

        $this->putJson(route('attendances.update', $attendance), $updated)
            ->assertOk()
            ->assertJson(['mensaje' => 'Asistencia actualizada correctamente.']);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'employee_id' => $this->employeeId,
            'shift_id' => $this->shiftId,
            'time' => '13:15',
            'type' => 'Salida',
            'status' => 'Ausente',
            'notes' => 'Asistencia actualizada',
        ]);
        $this->assertSame('2026-10-17', $attendance->fresh()->date->format('Y-m-d'));
    }

    public function test_rejected_attendance_edit_does_not_modify_the_existing_record(): void
    {
        $attendance = $this->createAttendance();
        $this->putJson(route('attendances.update', $attendance), $this->validData([
            'date' => '2026-10-18',
            'time' => '12:00',
            'type' => 'Salida',
            'status' => 'Ausente',
            'notes' => 'No debe persistir',
        ]))->assertUnprocessable()->assertJsonValidationErrors('employee_id');

        $saved = $attendance->fresh();
        $this->assertSame('2026-10-16', $saved->date->format('Y-m-d'));
        $this->assertSame('08:30', $saved->time);
        $this->assertSame('Entrada', $saved->type);
        $this->assertSame('Presente', $saved->status);
        $this->assertSame('Observación de prueba', $saved->notes);
    }

    public function test_authenticated_user_can_delete_an_attendance(): void
    {
        $attendance = $this->createAttendance();

        $this->deleteJson(route('attendances.destroy', $attendance))
            ->assertOk()
            ->assertJson(['mensaje' => 'Asistencia eliminada correctamente.']);

        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_attendance_actions_have_the_required_order_and_never_use_brand_routes(): void
    {
        $this->createAttendance();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('attendances.index', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]));

        $response->assertOk();
        $actions = (string) $response->json('data.0.actions');

        $this->assertStringContainsString('btn btn-outline-primary btn-sm', $actions);
        $this->assertStringContainsString('bi bi-eye', $actions);
        $this->assertStringContainsString('title="Ver"', $actions);
        $this->assertStringContainsString('aria-label="Ver asistencia"', $actions);
        $this->assertStringContainsString('btn btn-outline-warning btn-sm', $actions);
        $this->assertStringContainsString('bi bi-pencil-square', $actions);
        $this->assertStringContainsString('title="Editar"', $actions);
        $this->assertStringContainsString('aria-label="Editar asistencia"', $actions);
        $this->assertStringContainsString('btn btn-outline-danger btn-sm', $actions);
        $this->assertStringContainsString('bi bi-trash', $actions);
        $this->assertStringContainsString('title="Eliminar"', $actions);
        $this->assertStringContainsString('aria-label="Eliminar asistencia"', $actions);
        $this->assertLessThan(strpos($actions, 'btnEditar'), strpos($actions, 'btnVer'));
        $this->assertLessThan(strpos($actions, 'btnEliminar'), strpos($actions, 'btnEditar'));
        $this->assertStringNotContainsString('/admin/brands', $actions);

        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertDontSee('/admin/brands', false)
            ->assertDontSee('brands.edit', false)
            ->assertDontSee('brands.destroy', false);
    }

    public function test_unauthenticated_users_cannot_access_attendance_routes(): void
    {
        $attendance = $this->createAttendance();
        auth()->logout();

        foreach ([
            route('attendances.index'),
            route('attendances.create'),
            route('attendances.show', $attendance),
            route('attendances.edit', $attendance),
            route('attendances.history', ['employee_id' => $this->employeeId, 'date' => '2026-10-16']),
        ] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }

        $this->postJson(route('attendances.store'), $this->validData())->assertUnauthorized();
        $this->putJson(route('attendances.update', $attendance), $this->validData())->assertUnauthorized();
        $this->deleteJson(route('attendances.destroy', $attendance))->assertUnauthorized();
    }

    private function createAttendance(array $overrides = []): Attendance
    {
        return Attendance::create($this->validData($overrides));
    }

    private function createProgramming(string $date, string $status = 'Programado', ?string $deletedAt = null, ?int $employeeId = null, ?int $shiftId = null): void
    {
        $employeeId ??= $this->employeeId;
        $shiftId ??= $this->shiftId;
        $sequence = DB::table('departments')->count() + 1;
        $department = DB::table('departments')->insertGetId(['code' => str_pad((string) $sequence, 2, '0', STR_PAD_LEFT), 'name' => 'Departamento '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $province = DB::table('provinces')->insertGetId(['department_id' => $department, 'code' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT), 'name' => 'Provincia '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $district = DB::table('districts')->insertGetId(['province_id' => $province, 'code' => str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'name' => 'Distrito '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $zone = DB::table('zones')->insertGetId(['name' => 'Zona '.$sequence, 'district_id' => $district, 'created_at' => now(), 'updated_at' => now()]);
        $brand = DB::table('brands')->insertGetId(['name' => 'Marca '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $model = DB::table('brandmodels')->insertGetId(['name' => 'Modelo '.$sequence, 'brand_id' => $brand, 'created_at' => now(), 'updated_at' => now()]);
        $vehicleType = DB::table('vehicletypes')->insertGetId(['name' => 'Tipo '.$sequence, 'created_at' => now(), 'updated_at' => now()]);
        $color = DB::table('color')->insertGetId(['name' => 'Color '.$sequence, 'code' => '#123456', 'created_at' => now(), 'updated_at' => now()]);
        $vehicle = DB::table('vehicles')->insertGetId([
            'code' => 'V-'.$sequence, 'name' => 'Vehículo '.$sequence, 'plate' => 'ABC-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            'year' => 2026, 'load_capacity' => 5, 'occupats_capacity' => 3, 'fuel_capacity' => 10, 'compact_capacity' => 5,
            'type_id' => $vehicleType, 'brand_id' => $brand, 'model_id' => $model, 'color_id' => $color,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $group = DB::table('staff_groups')->insertGetId([
            'name' => 'Grupo '.$sequence, 'shift_id' => $shiftId, 'zone_id' => $zone, 'vehicle_id' => $vehicle,
            'driver_id' => $employeeId, 'state' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $programming = DB::table('programmings')->insertGetId([
            'staff_group_id' => $group, 'zone_id' => $zone, 'shift_id' => $shiftId, 'vehicle_id' => $vehicle,
            'driver_id' => $employeeId, 'date' => $date, 'status' => $status, 'snapshot' => json_encode([]),
            'deleted_at' => $deletedAt, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('programming_employees')->insert([
            'programming_id' => $programming, 'employee_id' => $employeeId, 'shift_id' => $shiftId,
            'date' => $date, 'role' => 'driver',
        ]);
    }

    private function createOtherEmployee(): int
    {
        return DB::table('employees')->insertGetId([
            'employee_type_id' => DB::table('employee_types')->value('id'),
            'dni' => '87654321',
            'first_name' => 'Luis',
            'last_name' => 'Gomez',
            'birthdate' => '1991-01-01',
            'email' => 'luis@example.test',
            'password' => 'password-hash-no-exponer',
            'status' => 'Activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => $this->employeeId,
            'shift_id' => $this->shiftId,
            'date' => '2026-10-16',
            'time' => '08:30',
            'type' => 'Entrada',
            'status' => 'Presente',
            'notes' => 'Observación de prueba',
        ], $overrides);
    }
}
