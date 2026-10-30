<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use App\Models\admin\Programming;
use App\Models\admin\Shift;
use App\Models\admin\StaffGroup;
use App\Models\admin\Zone;
use App\Services\ProgrammingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ProgrammingController extends Controller
{
    public function __construct(private ProgrammingService $service) {}

    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $filters = $request->validate([
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d'],
                'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
                'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            ]);
            $start = $request->has('start_date') ? ($filters['start_date'] ?? null) : today()->toDateString();
            $end = $request->has('end_date') ? ($filters['end_date'] ?? null) : today()->toDateString();
            if ($start && $end && $start > $end) {
                $this->reject('La fecha final no puede ser anterior a la inicial.');
            }
            $query = Programming::query()->orderBy('date')->orderBy('id')
                ->when($start, fn ($q) => $q->whereDate('date', '>=', $start))
                ->when($end, fn ($q) => $q->whereDate('date', '<=', $end))
                ->when($filters['zone_id'] ?? null, fn ($q, $id) => $q->where('zone_id', $id))
                ->when($filters['shift_id'] ?? null, fn ($q, $id) => $q->where('shift_id', $id));

            // Client pagination keeps exports consistent with every filtered row.
            return DataTables::collection($query->get()->map(fn ($row) => $this->row($row)))->skipPaging()->escapeColumns([])->toJson();
        }
        $groups = StaffGroup::with(['zone', 'shift'])->where('state', true)->orderBy('name')->get();
        $employees = Employee::where('status', 'Activo')->orderBy('last_name')->orderBy('first_name')->get();
        $zones = Zone::orderBy('name')->get();
        $shifts = Shift::orderBy('name')->get();

        return view('admin.programming.index', compact('groups', 'employees', 'zones', 'shifts'));
    }

    public function group(StaffGroup $staffGroup)
    {
        abort_unless($staffGroup->state, 422, 'El grupo está inactivo.');
        $staffGroup->load(['zone', 'shift', 'vehicle', 'days', 'helpers']);

        return response()->json([
            'id' => $staffGroup->id, 'name' => $staffGroup->name, 'zone' => $staffGroup->zone->name,
            'shift' => $staffGroup->shift->name,
            'hours' => substr($staffGroup->shift->start_time, 0, 5).' - '.substr($staffGroup->shift->end_time, 0, 5),
            'vehicle' => $staffGroup->vehicle->name, 'plate' => $staffGroup->vehicle->plate,
            'capacity' => (int) $staffGroup->vehicle->occupats_capacity, 'driver_id' => $staffGroup->driver_id,
            'helpers' => $staffGroup->helpers->pluck('id'), 'days' => $staffGroup->days->pluck('day'),
        ]);
    }

    public function check(Request $request)
    {
        $data = $this->validated($request);
        $editing = $data['programming_id'] ? Programming::findOrFail($data['programming_id']) : null;
        $result = $this->service->inspect($data, $editing);
        $token = null;
        if (! $result['issues']) {
            $token = Str::random(64);
            $request->session()->put('programming.validation', ['token' => $token, 'hash' => $this->fingerprint($data), 'group_hash' => $this->service->groupFingerprint($result['group']), 'expires' => now()->addMinutes(15)->timestamp]);
        } else {
            $request->session()->forget('programming.validation');
        }

        return response()->json([
            'valid' => ! $result['issues'], 'count' => count($result['dates']), 'dates' => $result['dates'],
            'issues' => $result['issues'], 'suggestions' => $result['suggestions'],
            'omitted_holidays' => $result['omittedHolidays'], 'omitted_duplicates' => $result['omittedDuplicates'],
            'validation_token' => $token,
        ]);
    }

    public function store(Request $request)
    {
        return $this->persist($request);
    }

    public function update(Request $request, Programming $programming)
    {
        return $this->persist($request, $programming);
    }

    private function persist(Request $request, ?Programming $editing = null)
    {
        $data = $this->validated($request);
        if ($data['programming_id'] !== $editing?->id) {
            $this->reject('La programación seleccionada no coincide con la operación.');
        }
        $validation = $request->session()->get('programming.validation');
        if (! $validation || $validation['expires'] < now()->timestamp
            || ! hash_equals($validation['token'], (string) $request->input('validation_token'))
            || ! hash_equals($validation['hash'], $this->fingerprint($data))) {
            $this->reject('Valide la disponibilidad de los datos actuales antes de guardar.');
        }
        $result = $this->service->save($data, (int) $request->user()->id, $editing, $validation['group_hash']);
        $request->session()->forget('programming.validation');

        return response()->json(['message' => $editing ? 'Programación actualizada correctamente.' : $result['created'].' recorrido(s) registrado(s).', ...$result], $editing ? 200 : 201);
    }

    public function show(Programming $programming)
    {
        $programming->load('employees');

        return response()->json([...$this->row($programming),
            'driver_id' => $programming->driver_id,
            'helper_ids' => $programming->employees->where('pivot.role', 'helper')->pluck('id')->values(),
            'days' => [$programming->date->dayOfWeekIso],
        ]);
    }

    public function history(string $id)
    {
        $programming = Programming::withTrashed()->findOrFail($id);

        return response()->json(['data' => $programming->changes()->with('user:id,name')->orderBy('id')->get()->map(fn ($change) => [
            'action' => $change->action, 'user' => $change->user?->name ?? 'Usuario no disponible',
            'at' => $change->created_at->format('d/m/Y H:i'), 'before' => $change->before, 'after' => $change->after,
        ])]);
    }

    public function finish(Request $request, Programming $programming)
    {
        DB::transaction(function () use ($programming, $request) {
            $row = Programming::whereKey($programming->id)->lockForUpdate()->firstOrFail();
            if ($row->status !== 'Programado') {
                $this->reject('El recorrido ya está finalizado.');
            }
            $before = $row->toArray();
            $row->update(['status' => 'Finalizado', 'finished_by' => $request->user()->id, 'finished_at' => now()]);
            $row->changes()->create(['user_id' => $request->user()->id, 'action' => 'Finalización web', 'before' => $before, 'after' => $row->toArray()]);
        });

        return response()->json(['message' => 'Recorrido finalizado correctamente.']);
    }

    public function destroy(Request $request, Programming $programming)
    {
        DB::transaction(function () use ($programming, $request) {
            $row = Programming::whereKey($programming->id)->lockForUpdate()->firstOrFail();
            if ($row->status !== 'Programado') {
                $this->reject('No se puede eliminar un recorrido finalizado.');
            }
            $before = $row->toArray();
            $row->employees()->detach();
            $row->update(['group_date_key' => null, 'vehicle_date_key' => null]);
            $row->delete();
            $row->changes()->create(['user_id' => $request->user()->id, 'action' => 'Eliminación', 'before' => $before, 'after' => $row->toArray()]);
        });

        return response()->json(['message' => 'Programación eliminada. El historial se conserva.']);
    }

    private function row(Programming $row): array
    {
        return ['id' => $row->id, 'date' => $row->date->toDateString(), 'status' => $row->status,
            'group_id' => $row->staff_group_id, ...$row->snapshot, 'notes' => $row->notes,
            'finished_at' => $row->finished_at?->format('d/m/Y H:i')];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'staff_group_id' => ['required', 'integer', 'exists:staff_groups,id'],
            'driver_id' => ['required', 'integer', 'exists:employees,id'],
            'helpers' => ['present', 'array'], 'helpers.*' => ['required', 'integer', 'distinct', 'exists:employees,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1'], 'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'programming_id' => ['nullable', 'integer', 'exists:programmings,id'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor seleccionado para :attribute no existe.',
            'date_format' => 'El campo :attribute debe tener formato AAAA-MM-DD.',
            'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            'days.min' => 'Seleccione al menos un día de trabajo.',
            'helpers.*.distinct' => 'No puede seleccionar al mismo ayudante más de una vez.',
            'notes.max' => 'Las observaciones no deben superar los 1000 caracteres.',
        ], ['staff_group_id' => 'grupo de personal', 'driver_id' => 'conductor', 'start_date' => 'fecha de inicio', 'end_date' => 'fecha de fin', 'days' => 'días de trabajo', 'helpers' => 'ayudantes']);
        if (CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) > 365) {
            $this->reject('Seleccione un período máximo de 366 días por operación.');
        }
        $data['staff_group_id'] = (int) $data['staff_group_id'];
        $data['driver_id'] = (int) $data['driver_id'];
        $data['programming_id'] = empty($data['programming_id']) ? null : (int) $data['programming_id'];
        $data['helpers'] = array_map('intval', $data['helpers']);
        $data['days'] = array_map('intval', $data['days']);
        sort($data['days']);
        $data['notes'] = trim($data['notes'] ?? '');

        return $data;
    }

    private function fingerprint(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['programming' => $message]);
    }
}
