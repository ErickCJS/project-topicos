<?php

namespace App\Http\Controllers;

use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Services\AttendanceMarkingRules;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeAttendanceController extends Controller
{
    public function __construct(private AttendanceMarkingRules $markingRules)
    {
    }

    public function login(Request $request): mixed
    {
        $this->clearEmployeeSession($request);

        return response()->view('employee.login')
            ->header('Cache-Control', 'no-store, no-cache, private, max-age=0');
    }

    public function authenticate(Request $request): mixed
    {
        $credentials = $request->validate([
            'dni' => ['required', 'digits:8'],
            'password' => ['required', 'string'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
        ], [
            'dni.digits' => 'El DNI debe tener 8 dígitos.',
        ]);

        $employee = Employee::query()
            ->where('dni', $credentials['dni'])
            ->where('status', 'Activo')
            ->first();

        if (! $employee || ! Hash::check($credentials['password'], $employee->password)) {
            throw ValidationException::withMessages([
                'credentials' => 'Las credenciales ingresadas no son válidas.',
            ]);
        }

        Auth::guard('employee')->login($employee);
        // Rotate only the session ID: the shared kiosk CSRF token must remain reusable.
        $request->session()->migrate(true);

        try {
            $result = DB::transaction(function () use ($employee, $credentials) {
                Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

                $now = CarbonImmutable::now();
                $pendingExits = $this->markingRules->pendingExits($employee->id, $now);

                // A pending entry takes precedence over the current time window.
                // This prevents a morning exit from being mistaken for an afternoon entry.
                if ($pendingExits->count() > 1) {
                    throw ValidationException::withMessages([
                        'attendance' => 'Tienes más de una entrada pendiente de salida. Acércate a administración para regularizar tus marcaciones.',
                    ]);
                }

                if ($pendingExits->count() === 1) {
                    /** @var Attendance $entry */
                    $entry = $pendingExits->first();

                    $exit = Attendance::create([
                        'employee_id' => $employee->id,
                        'shift_id' => $entry->shift_id,
                        'date' => $now->toDateString(),
                        'time' => $now->format('H:i:s'),
                        'type' => 'Salida',
                        'status' => 'Presente',
                        'origin' => $entry->origin ?? 'No programado',
                    ]);

                    return [
                        'type' => 'Salida',
                        'shift_id' => $entry->shift_id,
                        'shift_name' => $entry->shift?->name,
                        'status' => $exit->status,
                        'origin' => $exit->origin,
                    ];
                }

                $resolved = $this->markingRules->resolveShift(
                    $now,
                    isset($credentials['shift_id']) ? (int) $credentials['shift_id'] : null
                );

                $shift = $resolved['shift'];
                $date = $resolved['start_date'];

                $this->assertEligible($employee->id, $date);

                $alreadyEntered = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->where('shift_id', $shift->id)
                    ->whereDate('date', $date)
                    ->where('type', 'Entrada')
                    ->exists();

                if ($alreadyEntered) {
                    throw ValidationException::withMessages([
                        'attendance' => 'Ya tienes una entrada registrada para este turno y fecha. No se permiten dos entradas en el mismo turno.',
                    ]);
                }

                $alreadyExited = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->where('shift_id', $shift->id)
                    ->whereDate('date', $date)
                    ->where('type', 'Salida')
                    ->exists();

                if ($alreadyExited) {
                    throw ValidationException::withMessages([
                        'attendance' => 'Ya existe una salida registrada para este turno y fecha. Acércate a administración.',
                    ]);
                }

                $status = $this->markingRules->entryStatus($shift, $date, $now);
                $origin = $this->attendanceOrigin($employee->id, $shift->id, $date);

                $entry = Attendance::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shift->id,
                    'date' => $date,
                    'time' => $now->format('H:i:s'),
                    'type' => 'Entrada',
                    'status' => $status,
                    'origin' => $origin,
                ]);

                return [
                    'type' => 'Entrada',
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'status' => $entry->status,
                    'origin' => $origin,
                ];
            }, 3);
        } catch (QueryException $exception) {
            $this->clearEmployeeSession($request);

            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'attendance' => 'La marcación ya fue registrada. No se permiten duplicados.',
                ]);
            }

            throw $exception;
        } catch (ValidationException $exception) {
            $this->clearEmployeeSession($request);
            throw $exception;
        }

        $this->clearEmployeeSession($request);

        $message = $result['type'] === 'Entrada'
            ? 'Entrada registrada correctamente'
            : 'Salida registrada correctamente';

        return response()->json([
            'message' => $message,
            ...$result,
        ]);
    }

    /**
     * A worker may mark without a programming assignment. The only eligibility
     * requirements are an active employee record, a valid contract, and no
     * approved vacation on the work date.
     */
    private function assertEligible(int $employeeId, string $date): void
    {
        $eligible = Employee::query()
            ->whereKey($employeeId)
            ->where('status', 'Activo')
            ->whereHas('contracts', function ($query) use ($date) {
                $query->where('is_active', true)
                    ->whereNull('cancelled_at')
                    ->whereDate('start_date', '<=', $date)
                    ->where(function ($dates) use ($date) {
                        $dates->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $date);
                    });
            })
            ->whereDoesntHave('vacations', function ($query) use ($date) {
                $query->where('status', 'Aprobada')
                    ->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages([
                'attendance' => 'No puedes registrar asistencia porque no tienes contrato vigente, tu cuenta no está activa o tienes vacaciones aprobadas para esta fecha.',
            ]);
        }
    }

    private function attendanceOrigin(int $employeeId, int $shiftId, string $date): string
    {
        $programmed = DB::table('programming_employees')
            ->join('programmings', 'programmings.id', '=', 'programming_employees.programming_id')
            ->where('programming_employees.employee_id', $employeeId)
            ->whereDate('programming_employees.date', $date)
            ->where('programming_employees.shift_id', $shiftId)
            ->whereIn('programmings.status', ['Programado', 'Finalizado'])
            ->whereNull('programmings.deleted_at')
            ->exists();

        if ($programmed) {
            return 'Programado';
        }

        $retainer = DB::table('employees')
            ->join('employee_types', 'employee_types.id', '=', 'employees.employee_type_id')
            ->where('employees.id', $employeeId)
            ->whereIn('employee_types.name', ['Conductor', 'Ayudante'])
            ->exists();

        return $retainer ? 'Retén' : 'No programado';
    }

    private function clearEmployeeSession(Request $request): void
    {
        if (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
            // Preserve the shared kiosk CSRF token and any administrative session.
            $request->session()->migrate(true);
        }
    }
}
