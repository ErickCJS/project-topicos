<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Contract;
use App\Models\admin\Employee;
use App\Services\ContractRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Contract::query()->join('employees', 'contracts.employee_id', '=', 'employees.id')
                ->select('contracts.*', 'employees.dni', 'employees.first_name', 'employees.last_name');

            return DataTables::eloquent($query)
                ->filterColumn('employees.last_name', function ($query, $keyword) {
                    $query->where(function ($query) use ($keyword) {
                        $query->where('employees.first_name', 'like', '%'.$keyword.'%')
                            ->orWhere('employees.last_name', 'like', '%'.$keyword.'%');
                    });
                })
                ->addColumn('employee', fn ($contract) => $contract->first_name.' '.$contract->last_name)
                ->editColumn('start_date', fn ($contract) => $contract->start_date->format('d/m/Y'))
                ->editColumn('end_date', fn ($contract) => $contract->end_date?->format('d/m/Y') ?? 'Sin término')
                ->addColumn('next_hiring_date', fn ($contract) => $contract->type === 'Temporal' && $contract->end_date
                    ? $contract->end_date->copy()->addMonthsNoOverflow(2)->format('d/m/Y') : 'No corresponde')
                ->addColumn('status', fn ($contract) => $contract->start_date->isFuture() ? 'Por iniciar' :
                    ($contract->end_date && $contract->end_date->lt(today()) ? 'Finalizado' : 'Vigente'))
                ->addColumn('edit', fn ($contract) => '<button type="button" class="btn btn-primary btn-sm btnEditar" data-id="'.$contract->id.'" aria-label="Editar contrato"><i class="fas fa-pencil-alt"></i></button>')
                ->rawColumns(['edit'])->toJson();
        }

        return view('admin.contracts.index');
    }

    public function create()
    {
        return view('admin.contracts.create', $this->formData());
    }

    public function edit(string $id)
    {
        return view('admin.contracts.edit', $this->formData() + ['contract' => Contract::findOrFail($id)]);
    }

    public function store(Request $request, ContractRules $rules)
    {
        $data = $this->validatedData($request);
        DB::transaction(function () use ($data, $rules) {
            // Serialize submissions for this worker so two requests cannot create overlapping contracts.
            $employee = Employee::whereKey($data['employee_id'])->lockForUpdate()->firstOrFail();
            $rules->validate($data, $employee->contracts()->get(), true, CarbonImmutable::today());
            Contract::create($data + ['is_active' => true]);
        });

        return response()->json(['mensaje' => 'Contrato registrado correctamente'], 201);
    }

    public function update(Request $request, string $id, ContractRules $rules)
    {
        $original = Contract::findOrFail($id);
        $data = $this->validatedData($request);
        if ((int) $data['employee_id'] !== $original->employee_id) {
            throw ValidationException::withMessages(['employee_id' => 'El trabajador de un contrato registrado no se puede cambiar.']);
        }

        DB::transaction(function () use ($data, $rules, $original) {
            $employee = Employee::whereKey($original->employee_id)->lockForUpdate()->firstOrFail();
            $contract = Contract::whereKey($original->id)->lockForUpdate()->firstOrFail();
            $rules->validate($data, $employee->contracts()->where('id', '!=', $contract->id)->get(), false, CarbonImmutable::today());
            if ($contract->allowsVacations() && $data['type'] === 'Temporal' &&
                DB::table('vacations')->where('employee_id', $employee->id)->exists()) {
                throw ValidationException::withMessages(['type' => 'No puede cambiar a Temporal mientras el trabajador tenga registros de vacaciones.']);
            }
            $contract->update($data);
        });

        return response()->json(['mensaje' => 'Contrato actualizado correctamente']);
    }

    private function formData(): array
    {
        return [
            'employees' => Employee::orderBy('last_name')->orderBy('first_name')->get()
                ->mapWithKeys(fn ($employee) => [$employee->id => $employee->dni.' - '.$employee->first_name.' '.$employee->last_name]),
            'types' => array_combine(Contract::TYPES, Contract::TYPES),
        ];
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'type' => ['required', Rule::in(Contract::TYPES)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:type,Temporal', 'prohibited_unless:type,Temporal', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            // Salary is required by the existing database schema. Trial period is intentionally not exposed.
            'salary' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
        ], [
            'employee_id.required' => 'Seleccione un trabajador.',
            'employee_id.exists' => 'El trabajador seleccionado no existe.',
            'type.required' => 'Seleccione el tipo de contrato.',
            'type.in' => 'El tipo de contrato no es válido.',
            'start_date.required' => 'Ingrese la fecha de inicio.',
            'start_date.date_format' => 'Ingrese una fecha de inicio válida.',
            'end_date.required_if' => 'Ingrese la fecha de término del contrato temporal.',
            'end_date.prohibited_unless' => 'Los contratos Permanentes y Nombrados no tienen fecha de término.',
            'end_date.date_format' => 'Ingrese una fecha de término válida.',
            'end_date.after_or_equal' => 'La fecha de término no puede ser anterior a la fecha de inicio.',
            'salary.required' => 'Ingrese el sueldo.',
            'salary.numeric' => 'El sueldo debe ser un número.',
            'salary.min' => 'El sueldo no puede ser negativo.',
            'salary.max' => 'El sueldo no puede superar 999999.99.',
            'salary.decimal' => 'El sueldo admite hasta dos decimales.',
        ]);

        $data['end_date'] = $data['type'] === 'Temporal' ? $data['end_date'] : null;

        return $data;
    }
}
