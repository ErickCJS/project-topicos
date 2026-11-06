<?php

namespace App\Http\Controllers;

use App\Models\admin\Attendance;
use App\Models\admin\Employee;
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
        return view('employee.login');
    }

    public function authenticate(Request $request): mixed
    {
        $credentials = $request->validate([
            'dni' => ['required', 'digits:8'],
            'password' => ['required', 'string'],
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
            [$type, $shiftId] = DB::transaction(function () use ($employee) {
                Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
                $now = CarbonImmutable::now();
                $date = $now->toDateString();
                $previousDate = $now->subDay()->toDateString();

                $assignments = $this->assignments($employee->id, $date);
                $previousExitCandidates = $this->pendingOvernightExits($employee->id, $previousDate, $date);
                $todayExitCandidates = $this->pendingExits($employee->id, $date);

                if ($assignments->isEmpty() && $todayExitCandidates->isEmpty() && $previousExitCandidates->isEmpty()) {
                    throw ValidationException::withMessages([
                        'attendance' => 'No tienes una programación asignada para esta fecha. Comunícate con administración o tu supervisor.',
                    ]);
                }

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

                if ($entryCandidates->count() > 1) {
                    throw ValidationException::withMessages([
                        'attendance' => 'Hay más de un turno compatible con la hora actual. Acércate a administración.',
                    ]);
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

                Attendance::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $assignment->shift_id,
                    'date' => $now->toDateString(),
                    'time' => $now->format('H:i:s'),
                    'type' => $type,
                    'status' => 'Presente',
                ]);

                return [$type, $assignment->shift_id];

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
            'type' => $type, 'shift_id' => $shiftId,
        ]);
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
                'shifts.start_time',
                'shifts.end_time',
            ])
            ->distinct()
            ->get()
            ->map(fn ($assignment) => (object) [
                'shift_id' => $assignment->shift_id,
                'shift' => (object) [
                    'start_time' => $assignment->start_time,
                    'end_time' => $assignment->end_time,
                ],
            ])->values();
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
            ])->unique('shift_id')->values();
    }
}
