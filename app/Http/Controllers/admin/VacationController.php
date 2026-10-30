<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Contract;
use App\Models\admin\Employee;
use App\Models\admin\Vacation;
use App\Services\VacationService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VacationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Vacation::query()
                ->with(['employee.contracts' => fn ($query) => $query
                    ->whereIn('type', ['Permanente', 'Nombrado'])
                    ->orderByDesc('start_date')])
                ->join('employees', 'vacations.employee_id', '=', 'employees.id')
                ->select([
                    'vacations.*',
                    'employees.dni',
                    'employees.first_name',
                    'employees.last_name',
                ])
                ->selectSub(function ($query) {
                    $query->from('vacations as annual_vacations')
                        ->selectRaw('COALESCE(SUM(annual_vacations.scheduled_days), 0)')
                        ->whereColumn('annual_vacations.employee_id', 'vacations.employee_id')
                        ->whereColumn('annual_vacations.year', 'vacations.year')
                        ->where('annual_vacations.status', 'Aprobada');
                }, 'annual_scheduled_days');

            return DataTables::eloquent($query)
                ->filterColumn('employee_name', function ($query, $keyword) {
                    $query->where(function ($query) use ($keyword) {
                        $query->where('employees.first_name', 'like', '%'.$keyword.'%')
                            ->orWhere('employees.last_name', 'like', '%'.$keyword.'%');
                    });
                })
                ->addColumn('employee_name', fn ($vacation) => trim($vacation->first_name.' '.$vacation->last_name))
                ->addColumn('contract', fn (Vacation $vacation) => $this->contractLabel($vacation))
                ->addColumn('period', fn (Vacation $vacation) => $vacation->isHistorical()
                    ? 'Sin fechas'
                    : $vacation->start_date->format('d/m/Y').' - '.$vacation->end_date->format('d/m/Y'))
                ->addColumn('balance', fn ($vacation) => max(0, Vacation::ANNUAL_DAYS - (int) $vacation->annual_scheduled_days))
                ->editColumn('status', function (Vacation $vacation) {
                    $status = $vacation->statusLabel();
                    $class = match ($status) {
                        'Aprobada' => 'text-bg-success',
                        'Rechazada' => 'text-bg-secondary',
                        default => 'text-bg-warning',
                    };

                    return '<span class="badge '.$class.'">'.e($status).'</span>';
                })
                ->addColumn('actions', function (Vacation $vacation) {
                    $actions = '<div class="vacation-actions">'
                        .'<button type="button" class="btn btn-outline-primary btn-sm vacation-action-btn btnVer" data-url="'.e(route('vacations.show', $vacation)).'" title="Ver" aria-label="Ver vacación"><i class="bi bi-eye" aria-hidden="true"></i></button>';

                    if ($vacation->isPending() || ($vacation->isApproved() && $vacation->isHistorical())) {
                        $actions .= '<button type="button" class="btn btn-outline-warning btn-sm vacation-action-btn btnEditar" data-url="'.e(route('vacations.edit', $vacation)).'" title="Editar" aria-label="Editar vacación"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>';
                    }

                    if ($vacation->isPending()) {
                        $actions .= '<button type="button" class="btn btn-outline-danger btn-sm vacation-action-btn btnEliminar" data-url="'.e(route('vacations.destroy', $vacation)).'" title="Eliminar" aria-label="Eliminar vacación"><i class="bi bi-trash" aria-hidden="true"></i></button>'
                            .'<button type="button" class="btn btn-outline-success btn-sm vacation-action-btn btnAprobar" data-url="'.e(route('vacations.approve', $vacation)).'" title="Aprobar" aria-label="Aprobar vacación"><i class="bi bi-check-circle" aria-hidden="true"></i></button>'
                            .'<button type="button" class="btn btn-outline-secondary btn-sm vacation-action-btn btnRechazar" data-url="'.e(route('vacations.reject', $vacation)).'" title="Rechazar" aria-label="Rechazar vacación"><i class="bi bi-x-circle" aria-hidden="true"></i></button>';
                    }

                    return $actions.'</div>';
                })
                ->rawColumns(['status', 'actions'])
                ->toJson();
        }

        return view('admin.vacations.index');
    }

    public function create()
    {
        return view('admin.vacations.create', $this->formData());
    }

    public function store(Request $request, VacationService $service)
    {
        $vacation = $service->create($this->validatedData($request));

        return response()->json([
            'mensaje' => 'Vacación registrada correctamente.',
            'data' => ['id' => $vacation->id, 'scheduled_days' => $vacation->scheduled_days, 'status' => $vacation->status, 'balance' => $vacation->pending_days],
        ], 201);
    }

    public function show(Vacation $vacation, VacationService $service)
    {
        $vacation->load(['employee.contracts' => fn ($query) => $query
            ->whereIn('type', ['Permanente', 'Nombrado'])
            ->orderByDesc('start_date')]);
        $contractLabel = $this->contractLabel($vacation);
        $balance = $service->annualBalance($vacation->employee_id, $vacation->year);

        return view('admin.vacations.show', compact('vacation', 'contractLabel', 'balance'));
    }

    public function edit(Vacation $vacation, VacationService $service)
    {
        $vacation->load('employee');

        return view('admin.vacations.edit', $this->formData($vacation, $service) + compact('vacation'));
    }

    public function update(Request $request, Vacation $vacation, VacationService $service)
    {
        $updated = $service->update($vacation, $this->validatedData($request, $vacation));

        return response()->json([
            'mensaje' => $vacation->isHistorical()
                ? 'Vacación histórica regularizada correctamente.'
                : 'Vacación actualizada correctamente.',
            'data' => ['id' => $updated->id, 'scheduled_days' => $updated->scheduled_days, 'status' => $updated->status, 'balance' => $updated->pending_days],
        ]);
    }

    public function approve(Vacation $vacation, VacationService $service)
    {
        $approved = $service->approve($vacation);

        return response()->json([
            'mensaje' => 'Solicitud de vacaciones aprobada correctamente.',
            'data' => ['id' => $approved->id, 'status' => $approved->status, 'balance' => $approved->pending_days],
        ]);
    }

    public function reject(Request $request, Vacation $vacation, VacationService $service)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ], [
            'rejection_reason.required' => 'Ingrese el motivo del rechazo.',
            'rejection_reason.string' => 'El motivo del rechazo no es válido.',
            'rejection_reason.max' => 'El motivo del rechazo no debe superar los 1000 caracteres.',
        ]);
        $rejected = $service->rejectRequest($vacation, trim($data['rejection_reason']));

        return response()->json([
            'mensaje' => 'Solicitud de vacaciones rechazada correctamente.',
            'data' => ['id' => $rejected->id, 'status' => $rejected->status, 'balance' => $rejected->pending_days],
        ]);
    }

    public function destroy(Request $request, Vacation $vacation, VacationService $service)
    {
        $request->validate(
            ['confirmed' => ['required', 'accepted']],
            [
                'confirmed.required' => 'Debe confirmar expresamente la eliminación.',
                'confirmed.accepted' => 'Debe confirmar expresamente la eliminación.',
            ],
        );

        $service->delete($vacation);

        return response()->json(['mensaje' => 'Vacación eliminada correctamente.']);
    }

    public function check(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ], [
            'employee_id.required' => 'Seleccione un empleado.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'date.required' => 'Ingrese la fecha a consultar.',
            'date.date_format' => 'La fecha a consultar no es válida.',
        ]);

        $vacation = Vacation::query()
            ->where('employee_id', $data['employee_id'])
            ->covering($data['date'])
            ->first();

        return response()->json([
            'employee_id' => (int) $data['employee_id'],
            'date' => $data['date'],
            'on_vacation' => $vacation !== null,
            'vacation' => $vacation ? [
                'id' => $vacation->id,
                'start_date' => $vacation->start_date->toDateString(),
                'end_date' => $vacation->end_date->toDateString(),
            ] : null,
        ]);
    }

    public function balance(Request $request, VacationService $service)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'year' => ['required', 'integer', 'between:1900,2100'],
            'excluding_id' => ['nullable', 'integer', 'exists:vacations,id'],
        ]);

        $excludingId = isset($data['excluding_id']) ? (int) $data['excluding_id'] : null;
        if ($excludingId !== null && ! Vacation::query()
            ->whereKey($excludingId)
            ->where('employee_id', $data['employee_id'])
            ->where('year', $data['year'])
            ->exists()) {
            return response()->json([
                'message' => 'La vacación excluida no pertenece al empleado y año seleccionados.',
                'errors' => ['excluding_id' => ['La vacación excluida no pertenece al empleado y año seleccionados.']],
            ], 422);
        }

        $available = $service->requestableBalance((int) $data['employee_id'], (int) $data['year'], $excludingId);

        return response()->json([
            'annual_days' => Vacation::ANNUAL_DAYS,
            'scheduled_days' => Vacation::ANNUAL_DAYS - $available,
            'available_days' => $available,
        ]);
    }

    private function validatedData(Request $request, ?Vacation $vacation = null): array
    {
        $startRules = ['required', 'date_format:Y-m-d'];
        if (! $vacation || ! $vacation->isHistorical()) {
            $startRules[] = 'after_or_equal:today';
        }

        return $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'year' => ['required', 'integer', 'between:1900,2100'],
            'start_date' => $startRules,
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [
            'employee_id.required' => 'Seleccione un empleado.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'year.required' => 'Indique el año de la vacación.',
            'year.integer' => 'El año de la vacación no es válido.',
            'year.between' => 'El año debe estar entre 1900 y 2100.',
            'start_date.required' => 'Ingrese la fecha de inicio.',
            'start_date.date_format' => 'La fecha de inicio no es válida.',
            'start_date.after_or_equal' => 'La fecha de inicio de una solicitud ordinaria debe ser hoy o posterior.',
            'end_date.required' => 'Ingrese la fecha de término.',
            'end_date.date_format' => 'La fecha de término no es válida.',
            'end_date.after_or_equal' => 'La fecha de término no puede ser anterior a la fecha de inicio.',
        ]);
    }

    private function formData(?Vacation $vacation = null, ?VacationService $service = null): array
    {
        $employees = Employee::query()
            ->where('status', 'Activo')
            ->whereHas('contracts', fn ($query) => $query
                ->whereIn('type', ['Permanente', 'Nombrado'])
                ->where('is_active', true)
                ->whereNull('cancelled_at')
                ->whereDate('start_date', '<=', today())
                ->where(function ($query) {
                    $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
                }))
            ->with(['contracts' => fn ($query) => $query
                ->whereIn('type', ['Permanente', 'Nombrado'])
                ->where('is_active', true)
                ->whereNull('cancelled_at')
                ->where(function ($query) {
                    $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
                })
                ->orderByDesc('start_date')])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($vacation && ! $employees->contains('id', $vacation->employee_id)) {
            $employees->push($vacation->employee()->with('contracts')->first());
        }

        return [
            'employees' => $employees,
            'balance' => $vacation && $service
                ? $service->annualBalance($vacation->employee_id, $vacation->year)
                : Vacation::ANNUAL_DAYS,
        ];
    }

    private function contractLabel(Vacation $vacation): string
    {
        $contracts = $vacation->employee?->contracts ?? collect();
        $contract = $contracts->first(function (Contract $contract) use ($vacation) {
            if ($vacation->isHistorical()) {
                return true;
            }

            return $contract->start_date->lte($vacation->start_date)
                && ($contract->end_date === null || $contract->end_date->gte($vacation->end_date));
        });

        return $contract ? $contract->type : 'Sin contrato asociado';
    }
}
