<?php

namespace App\Http\Controllers;

use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Models\admin\Shift;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeAttendanceController extends Controller
{
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
        ], ['dni.digits' => 'El DNI debe tener 8 dígitos.']);
        $employee = Employee::where('dni', $credentials['dni'])->where('status', 'Activo')->first();
        if (! $employee || ! Hash::check($credentials['password'], $employee->password)) {
            throw ValidationException::withMessages(['credentials' => 'Las credenciales ingresadas no son válidas.']);
        }
        Auth::guard('employee')->login($employee);
        // Laravel 12's regenerate() also rotates the CSRF token. Keep the
        // kiosk page's token stable while rotating only the session ID.
        $request->session()->migrate(true);
        try {
            [$type, $shiftId, $origin] = DB::transaction(function () use ($employee, $credentials) {
                Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
                $now = CarbonImmutable::now();
                $date = $now->toDateString();
                $previousDate = $now->subDay()->toDateString();

                $assignments = $this->assignments($employee->id, $date);
                $previousExitCandidates = $this->pendingOvernightExits($employee->id, $previousDate, $date);
                $todayExitCandidates = $this->pendingExits($employee->id, $date);

                $entryCandidates = $assignments->filter(function ($assignment) use ($employee, $date, $now) {
                    $base = Attendance::where('employee_id', $employee->id)
                        ->where('shift_id', $assignment->shift_id)
                        ->whereDate('date', $date);

                    if ((clone $base)->where('type', 'Entrada')->exists()) {
                        return false;
                    }

                    $start = CarbonImmutable::parse($date.' '.$assignment->shift->start_time);

                    return $now->greaterThanOrEqualTo($start->subMinutes(30))
                        && $now->lessThanOrEqualTo($start->addMinutes(30));
                })->values();

                $origin = 'Programado';
                if ($entryCandidates->count() > 1) {
                    if (! empty($credentials['shift_id'])) {
                        $entryCandidates = $entryCandidates->where('shift_id', (int) $credentials['shift_id'])->values();
                    }
                }

                if ($entryCandidates->count() > 1) {
                    throw $this->ambiguousShift($entryCandidates);
                }

                if ($entryCandidates->count() === 1) {
                    if ($previousExitCandidates->isNotEmpty()) {
                        throw ValidationException::withMessages([
                            'attendance' => 'Tienes una salida pendiente de un turno anterior. Acércate a administración.',
                        ]);
                    }

                    $type = 'Entrada';
                    $assignment = $entryCandidates->first();
                } else {
                    $exitCandidates = $todayExitCandidates
                        ->concat($previousExitCandidates)
                        ->values();

                    if ($exitCandidates->count() > 1) {
                        throw ValidationException::withMessages([
                            'attendance' => 'Hay más de un turno pendiente de salida. Acércate a administración.',
                        ]);
                    }

                    if ($exitCandidates->count() === 1) {
                        $type = 'Salida';
                        $assignment = $exitCandidates->first();
                    } else {
                        $retainerCandidates = $this->retainerShifts($date, $now);
                        if (! empty($credentials['shift_id'])) {
                            $retainerCandidates = $retainerCandidates->where('shift_id', (int) $credentials['shift_id'])->values();
                        }
                        if ($retainerCandidates->count() > 1) {
                            throw $this->ambiguousShift($retainerCandidates);
                        }
                        if ($retainerCandidates->count() === 1) {
                            if (! $this->hasEligibility($employee->id, $date)) {
                                if (! $this->hasRetainerRole($employee->id)) {
                                    throw ValidationException::withMessages([
                                        'attendance' => 'No tienes una programación asignada para esta fecha. Comunícate con administración o tu supervisor.',
                                    ]);
                                }
                                throw ValidationException::withMessages([
                                    'attendance' => 'No puedes registrar asistencia como retén porque no tienes contrato vigente o tienes vacaciones aprobadas.',
                                ]);
                            }
                            $type = 'Entrada';
                            $origin = 'Retén';
                            $assignment = $retainerCandidates->first();
                        } elseif ($assignments->isEmpty()) {
                            throw ValidationException::withMessages([
                                'attendance' => 'No hay un turno compatible para registrar la asistencia como retén.',
                            ]);
                        } else {
                        $futureAssignments = $assignments->filter(function ($assignment) use ($employee, $date, $now) {
                            $base = Attendance::where('employee_id', $employee->id)
                                ->where('shift_id', $assignment->shift_id)
                                ->whereDate('date', $date);

                            if ((clone $base)->where('type', 'Entrada')->exists()) {
                                return false;
                            }

                            $start = CarbonImmutable::parse($date.' '.$assignment->shift->start_time);

                            return $now->lessThan($start->subMinutes(30));
                        })->sortBy(function ($assignment) use ($date) {
                            return CarbonImmutable::parse($date.' '.$assignment->shift->start_time)
                                ->subMinutes(30)->getTimestamp();
                        })->values();

                        if ($futureAssignments->isNotEmpty()) {
                            $nextStart = CarbonImmutable::parse(
                                $date.' '.$futureAssignments->first()->shift->start_time
                            )->subMinutes(30)->format('H:i');

                            throw ValidationException::withMessages([
                                'attendance' => "Tu turno todavía no está habilitado. Podrás registrar tu entrada desde las {$nextStart}.",
                            ]);
                        }

                        throw ValidationException::withMessages([
                            'attendance' => 'Tu tiempo de tolerancia ha finalizado. Acércate a administración para registrar tu asistencia',
                        ]);
                        }
                    }
                }

                Attendance::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $assignment->shift_id,
                    'date' => $now->toDateString(),
                    'time' => $now->format('H:i:s'),
                    'type' => $type,
                    'status' => 'Presente',
                    'origin' => $origin,
                ]);

                return [$type, $assignment->shift_id, $origin];

            }, 3);
        } catch (QueryException $exception) {
            $this->clearEmployeeSession($request);
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['attendance' => 'La marcación ya fue registrada.']);
            }
            throw $exception;
        } catch (ValidationException $exception) {
            $this->clearEmployeeSession($request);
            throw $exception;
        }
        $this->clearEmployeeSession($request);
        return response()->json([
            'message' => $type === 'Entrada' ? 'Entrada registrada correctamente' : 'Salida registrada correctamente',
            'type' => $type, 'shift_id' => $shiftId, 'origin' => $origin,
        ]);
    }

    private function retainerShifts(string $date, CarbonImmutable $now)
    {
        return Shift::query()->orderBy('start_time')->get()
            ->filter(function (Shift $shift) use ($date, $now) {
                $start = CarbonImmutable::parse($date.' '.$shift->start_time);

                return $now->greaterThanOrEqualTo($start->subMinutes(30))
                    && $now->lessThanOrEqualTo($start->addMinutes(30));
            })->map(fn (Shift $shift) => (object) [
                'shift_id' => $shift->id,
                'shift' => (object) [
                    'name' => $shift->name,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                ],
            ])->values();
    }

    private function hasEligibility(int $employeeId, string $date): bool
    {
        return Employee::query()->whereKey($employeeId)
            ->where('status', 'Activo')
            ->whereHas('employeeType', fn ($query) => $query->whereIn('name', ['Conductor', 'Ayudante']))
            ->whereHas('contracts', fn ($query) => $query->where('is_active', true)->whereNull('cancelled_at')
                ->whereDate('start_date', '<=', $date)
                ->where(fn ($dates) => $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $date)))
            ->whereDoesntHave('vacations', fn ($query) => $query->where('status', 'Aprobada')
                ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date))
            ->exists();
    }

    private function hasRetainerRole(int $employeeId): bool
    {
        return Employee::query()->whereKey($employeeId)
            ->whereHas('employeeType', fn ($query) => $query->whereIn('name', ['Conductor', 'Ayudante']))
            ->exists();
    }

    private function clearEmployeeSession(Request $request): void
    {
        if (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
            // Preserve the CSRF token and any independent administrative web session.
            $request->session()->migrate(true);
        }
    }

    private function assignments(int $employeeId, string $date)
    {
        return DB::table('programming_employees')
            ->join('programmings', 'programmings.id', '=', 'programming_employees.programming_id')
            ->join('shifts', 'shifts.id', '=', 'programming_employees.shift_id')
            ->where('programming_employees.employee_id', $employeeId)
            ->whereDate('programming_employees.date', $date)
            ->whereColumn('programming_employees.date', 'programmings.date')
            ->whereColumn('programming_employees.shift_id', 'programmings.shift_id')
            ->where('programmings.status', 'Programado')
            ->whereNull('programmings.deleted_at')
            ->select([
                'programming_employees.shift_id',
                'shifts.name',
                'shifts.start_time',
                'shifts.end_time',
            ])
            ->distinct()
            ->get()
            ->map(fn ($assignment) => (object) [
                'shift_id' => $assignment->shift_id,
                'shift' => (object) [
                    'name' => $assignment->name,
                    'start_time' => $assignment->start_time,
                    'end_time' => $assignment->end_time,
                ],
            ])->values();
    }

    private function ambiguousShift($candidates): ValidationException
    {
        return ValidationException::withMessages([
            'attendance' => 'Seleccione un turno compatible para registrar su asistencia.',
            'shift_id' => $candidates->pluck('shift_id')->values()->all(),
            'shift_options' => $candidates->map(fn ($candidate) => [
                'id' => $candidate->shift_id,
                'name' => $candidate->shift->name ?? 'Turno',
            ])->values()->all(),
        ]);
    }

    private function pendingExits(int $employeeId, string $date)
    {
        return Attendance::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->where('type', 'Entrada')
            ->whereNotExists(function ($query) use ($date) {
                $query->selectRaw('1')
                    ->from('attendances as exits')
                    ->whereColumn('exits.employee_id', 'attendances.employee_id')
                    ->whereColumn('exits.shift_id', 'attendances.shift_id')
                    ->whereDate('exits.date', $date)
                    ->where('exits.type', 'Salida');
            })
            ->get()
            ->map(fn (Attendance $attendance) => (object) [
                'shift_id' => $attendance->shift_id,
                'shift' => $attendance->shift,
                'origin' => $attendance->origin,
            ])->unique('shift_id')->values();
    }

    private function pendingOvernightExits(int $employeeId, string $previousDate, string $date)
    {
        return Attendance::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->whereDate('date', $previousDate)
            ->where('type', 'Entrada')
            ->whereHas('shift', function ($query) {
                $query->whereColumn('shifts.end_time', '<=', 'shifts.start_time');
            })
            ->whereNotExists(function ($query) use ($date) {
                $query->selectRaw('1')
                    ->from('attendances as exits')
                    ->whereColumn('exits.employee_id', 'attendances.employee_id')
                    ->whereColumn('exits.shift_id', 'attendances.shift_id')
                    ->whereDate('exits.date', $date)
                    ->where('exits.type', 'Salida');
            })
            ->get()
            ->map(fn (Attendance $attendance) => (object) [
                'shift_id' => $attendance->shift_id,
                'shift' => $attendance->shift,
                'origin' => $attendance->origin,
            ])->unique('shift_id')->values();
    }
}
