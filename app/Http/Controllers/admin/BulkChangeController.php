<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use App\Models\admin\Programming;
use App\Models\admin\Reason;
use App\Models\admin\Shift;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use App\Services\BulkProgrammingModificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BulkChangeController extends Controller
{
    public function __construct(private BulkProgrammingModificationService $service) {}

    public function index()
    {
        return view('admin.change-management.cambios.bulk', [
            'zones' => Zone::orderBy('name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
            'reasons' => Reason::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function options(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'type' => ['required', 'in:shift,driver,helper,vehicle'],
        ], ['end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.']);

        $rows = Programming::with('employees')->where('status', 'Programado')->where('zone_id', $data['zone_id'])
            ->whereDate('date', '>=', $data['start_date'])->whereDate('date', '<=', $data['end_date'])->get();
        $helperIds = $rows->flatMap(fn (Programming $row) => $row->employees->where('pivot.role', 'helper')->pluck('id'))->unique()->values();
        $people = Employee::where('status', 'Activo')
            ->when($data['type'] === 'driver', fn ($q) => $q->where('employee_type_id', 1))
            ->when($data['type'] === 'helper', fn ($q) => $q->where('employee_type_id', '!=', 1))
            ->orderBy('last_name')->orderBy('first_name')->get()
            ->map(fn (Employee $employee) => ['id' => $employee->id, 'name' => trim($employee->first_name.' '.$employee->last_name), 'dni' => $employee->dni]);
        $oldHelpers = Employee::whereIn('id', $helperIds)->orderBy('last_name')->orderBy('first_name')->get()
            ->map(fn (Employee $employee) => ['id' => $employee->id, 'name' => trim($employee->first_name.' '.$employee->last_name), 'dni' => $employee->dni])->values();

        return response()->json([
            'count' => $rows->count(),
            'people' => $people->values(),
            'old_helpers' => $oldHelpers,
            'vehicles' => Vehicle::orderBy('name')->get(['id', 'name', 'code', 'plate', 'occupats_capacity']),
        ]);
    }

    public function check(Request $request)
    {
        $request->session()->forget('cambios.bulk-modification');
        $data = $this->validated($request, false);
        $result = $this->service->preview($data);

        if (! $result['valid']) {
            $result['validation_token'] = null;
            return response()->json($result);
        }

        $data['ids'] = array_values(array_map('intval', $result['ids'] ?? []));
        $token = Str::random(64);
        $request->session()->put('cambios.bulk-modification', [
            'token' => $token,
            'hash' => $this->fingerprint($data),
            'operation_id' => (string) Str::uuid(),
            'expires' => now()->addMinutes(15)->timestamp,
        ]);
        $result['validation_token'] = $token;

        return response()->json($result);
    }

    public function apply(Request $request)
    {
        $saved = $request->session()->get('cambios.bulk-modification');
        $request->session()->forget('cambios.bulk-modification');
        $data = $this->validated($request);

        if (! $saved || $saved['expires'] < now()->timestamp
            || ! hash_equals($saved['token'], (string) $request->input('validation_token'))
            || ! hash_equals($saved['hash'], $this->fingerprint($data))) {
            throw ValidationException::withMessages(['bulk_modifications' => 'La vista previa expiró o los datos cambiaron. Consulte nuevamente antes de confirmar.']);
        }

        $count = $this->service->apply($data, (int) $request->user()->id, $saved['operation_id']);

        return response()->json(['message' => $count.' programación(es) modificada(s) correctamente.', 'count' => $count]);
    }

    private function validated(Request $request, bool $requireIds = true): array
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'type' => ['required', 'in:shift,driver,helper,vehicle'],
            'new_shift_id' => ['required_if:type,shift', 'exclude_unless:type,shift', 'integer', 'exists:shifts,id'],
            'new_driver_id' => ['required_if:type,driver', 'exclude_unless:type,driver', 'integer', 'exists:employees,id'],
            'old_employee_id' => ['required_if:type,helper', 'exclude_unless:type,helper', 'integer', 'exists:employees,id'],
            'new_employee_id' => ['required_if:type,helper', 'exclude_unless:type,helper', 'integer', 'exists:employees,id', 'different:old_employee_id'],
            'new_vehicle_id' => ['required_if:type,vehicle', 'exclude_unless:type,vehicle', 'integer', 'exists:vehicles,id'],
            'ids' => $requireIds ? ['required', 'array', 'min:1'] : ['sometimes', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:programmings,id'],
            'reason' => ['required', 'string', 'max:100', 'exists:reason,name'],
            'custom_reason' => ['nullable', 'string', 'max:120', 'required_if:reason,Otro'],
            'description' => ['nullable', 'string', 'max:1000'],
            'validation_token' => ['nullable', 'string'],
        ], [
            'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            'reason.required' => 'El motivo es obligatorio.',
            'reason.exists' => 'El motivo seleccionado no existe.',
            'ids.required' => 'Consulte las programaciones antes de confirmar.',
        ]);

        if ($requireIds) {
            $data['ids'] = array_values(array_map('intval', $data['ids']));
        } else {
            unset($data['ids']);
        }
        $data['zone_id'] = (int) $data['zone_id'];
        $data['description'] = trim($data['description'] ?? '');
        $data['custom_reason'] = trim($data['custom_reason'] ?? '');
        foreach (['new_shift_id', 'new_driver_id', 'old_employee_id', 'new_employee_id', 'new_vehicle_id'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $data[$key] = (int) $data[$key];
            }
        }
        unset($data['validation_token']);

        return $data;
    }

    private function fingerprint(array $data): string
    {
        ksort($data);
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
