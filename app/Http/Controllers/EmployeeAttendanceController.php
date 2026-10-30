<?php

namespace App\Http\Controllers;

use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Models\admin\StaffGroup;
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

                $previousOvernightAssignments = $this->assignments($employee->id, $previousDate)
                    ->filter(function ($assignment) {
                        $startTime = substr((string) $assignment->shift->start_time, 0, 8);
                        $endTime = substr((string) $assignment->shift->end_time, 0, 8);

                        return $endTime <= $startTime;
                    })
                    ->values();

                $previousExitCandidates = $previousOvernightAssignments->filter(function ($assignment) use ($employee, $previousDate, $date) {
                    $base = Attendance::where('employee_id', $employee->id)
                        ->where('shift_id', $assignment->shift_id);

                    $entryExists = (clone $base)
                        ->whereDate('date', $previousDate)
                        ->where('type', 'Entrada')
                        ->exists();

                    $exitExists = (clone $base)
                        ->whereDate('date', $date)
                        ->where('type', 'Salida')
                        ->exists();

                    return $entryExists && ! $exitExists;
                })->values();

                if ($assignments->isEmpty() && $previousExitCandidates->isEmpty()) {
                    throw ValidationException::withMessages([
                        'attendance' => 'No tienes programación asignada para hoy.',
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
                    $todayExitCandidates = $assignments->filter(function ($assignment) use ($employee, $date) {
                        $base = Attendance::where('employee_id', $employee->id)
                            ->where('shift_id', $assignment->shift_id)
                            ->whereDate('date', $date);

                        return (clone $base)->where('type', 'Entrada')->exists()
                            && ! (clone $base)->where('type', 'Salida')->exists();
                    })->values();

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
        $day = CarbonImmutable::parse($date)->dayOfWeekIso;

        return StaffGroup::query()
            ->with('shift')
            ->where('state', true)
            ->whereHas('days', fn ($query) => $query->where('day', $day))
            ->where(function ($query) use ($employeeId) {
                $query->where('driver_id', $employeeId)
                    ->orWhereHas('helpers', fn ($helperQuery) => $helperQuery->whereKey($employeeId));
            })
            ->get()
            ->unique('shift_id')
            ->values();
    }
}
