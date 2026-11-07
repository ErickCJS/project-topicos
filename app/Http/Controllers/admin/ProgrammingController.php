<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Holiday;
use App\Models\admin\Programming;
use App\Models\admin\Shift;
use App\Models\admin\StaffGroup;
use App\Models\admin\Zone;
use App\Models\admin\Employee;
use App\Models\admin\Vehicle;
use App\Models\admin\Reason;
use App\Services\ProgrammingService;
use App\Services\ProgrammingModificationService;
use App\Services\BulkProgrammingModificationService;
use App\Services\ProgrammingReschedulingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ProgrammingController extends Controller
{
    public function __construct(private ProgrammingService $service, private ProgrammingModificationService $modificationService, private BulkProgrammingModificationService $bulkModificationService, private ProgrammingReschedulingService $reschedulingService) {}

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
        $zones = Zone::orderBy('name')->get();
        $shifts = Shift::orderBy('name')->get();

        $reasons = Reason::query()->orderBy('name')->get(['id', 'name']);
        return view('admin.programming.index', compact('groups', 'zones', 'shifts', 'reasons'));
    }

    public function holidays(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.today('America/Lima')->toDateString()],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
        ]);
        if (CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) > 365) {
            $this->reject('Seleccione un período máximo de 366 días por operación.');
        }
        $holidays = Holiday::where('state', true)->whereDate('date', '>=', $data['start_date'])
            ->whereDate('date', '<=', $data['end_date'])->orderBy('date')->get()
            ->filter(fn ($holiday) => in_array($holiday->date->dayOfWeekIso, array_map('intval', $data['days']), true))
            ->groupBy(fn ($holiday) => $holiday->date->toDateString())
            ->map(fn ($items, $date) => ['date' => $date, 'names' => $items->pluck('name')->values()->all()])->values();

        return response()->json(['data' => $holidays]);
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
            'capacity' => (int) $staffGroup->vehicle->occupats_capacity, 'vehicle_id' => $staffGroup->vehicle_id, 'driver_id' => $staffGroup->driver_id,
            'helpers' => $staffGroup->helpers->pluck('id'), 'days' => $staffGroup->days->pluck('day'),
        ]);
    }

    public function people(Request $request)
    {
        $data = $this->validated($request, false);
        $editing = $data['programming_id'] ? Programming::findOrFail($data['programming_id']) : null;

        return response()->json($this->service->availablePeople($data, $editing));
    }

    /** Data for the modal. It intentionally reads configuration but never writes it. */
    public function bulkContext(Request $request)
    {
        $dates = $request->validate(['start_date' => ['required','date_format:Y-m-d','after_or_equal:'.today('America/Lima')->toDateString()], 'end_date' => ['required','date_format:Y-m-d','after_or_equal:start_date']]);
        $groups = StaffGroup::with(['zone','shift','vehicle','driver','helpers','days'])->where('state', true)->orderBy('name')->get();
        return response()->json([
            'holidays' => Holiday::where('state', true)->whereBetween('date', [$dates['start_date'], $dates['end_date']])->orderBy('date')->get()->map(fn ($h) => ['date' => $h->date->toDateString(), 'name' => $h->name]),
            'groups' => $groups->map(fn ($g) => ['id'=>$g->id,'name'=>$g->name,'zone'=>$g->zone->name,'shift'=>$g->shift->name,'shift_id'=>$g->shift_id,'days'=>$g->days->pluck('day')->values(), 'vehicle_id'=>$g->vehicle_id,'driver_id'=>$g->driver_id,'helpers'=>$g->helpers->pluck('id')->values(), 'capacity'=>(int)$g->vehicle->occupats_capacity]),
            'vehicles' => Vehicle::orderBy('name')->get(['id','code','name','plate','occupats_capacity']),
            // Solo se muestra personal disponible para el período solicitado. La
            // validación final se repite por grupo y fecha antes de guardar.
            'people' => Employee::with(['contracts', 'vacations'])->where('status','Activo')->orderBy('last_name')->orderBy('first_name')->get()
                ->filter(function (Employee $person) use ($dates) {
                    $hasContract = $person->contracts->contains(fn ($contract) => $contract->is_active && ! $contract->cancelled_at
                        && $contract->start_date->toDateString() <= $dates['start_date']
                        && (! $contract->end_date || $contract->end_date->toDateString() >= $dates['end_date']));
                    $hasVacation = $person->vacations->contains(fn ($vacation) => $vacation->status === 'Aprobada' && $vacation->start_date && $vacation->end_date
                        && $vacation->start_date->toDateString() <= $dates['end_date'] && $vacation->end_date->toDateString() >= $dates['start_date']);
                    return $hasContract && ! $hasVacation;
                })->map(fn ($p) => ['id'=>$p->id,'name'=>trim($p->first_name.' '.$p->last_name),'dni'=>$p->dni,'is_driver'=>(int)$p->employee_type_id === 1])->values(),
        ]);
    }

    public function bulkCheck(Request $request)
    {
        $data = $this->validatedBulk($request);
        $reports = []; $valid = true; $reservations = []; $totalDates = 0;
        foreach ($data['groups'] as $item) {
            $result = $this->service->inspect($this->bulkItem($data, $item));
            $index = count($reports);
            $issues = $result['issues'];
            $warnings = collect($result['omittedDuplicates'])->map(fn ($d) => ['reason'=>'Ya existe una programación previa','dates'=>[$d]])->values()->all();
            if (! count($result['dates'])) {
                if ($result['omittedHolidays']) {
                    $warnings[] = ['reason' => 'Este grupo no se programará porque sus fechas laborales son feriados excluidos', 'dates' => $result['omittedHolidays']];
                }
                if ($result['omittedDuplicates']) {
                    $warnings[] = ['reason' => 'Este grupo no se programará porque ya tiene programaciones previas', 'dates' => $result['omittedDuplicates']];
                }
                if (! $result['omittedHolidays'] && ! $result['omittedDuplicates']) {
                    $names = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
                    $workdays = $result['group']->days->pluck('day')->map(fn ($day) => $names[(int) $day] ?? '—')->implode(', ');
                    $warnings[] = ['reason' => 'Este grupo no se programará en este rango; trabaja: '.$workdays, 'dates' => []];
                }
            }
            $totalDates += count($result['dates']);
            $reports[] = ['group_id'=>$item['staff_group_id'], 'errors'=>$issues, 'warnings'=>$warnings, 'dates'=>$result['dates']];
            foreach ($result['dates'] as $date) foreach (array_merge(['v:'.$item['vehicle_id']], array_map(fn ($id) => 'p:'.$id, array_merge([$item['driver_id']], $item['helpers']))) as $resource) {
                $key = $date.':'.$result['group']->shift_id.':'.$resource;
                $resourceName = str_starts_with($resource, 'v:')
                    ? ($result['vehicle']->name.' · '.$result['vehicle']->plate)
                    : $this->service->name($result['people'][(int) substr($resource, 2)]);
                if (isset($reservations[$key])) {
                    $other = $reservations[$key];
                    $type = str_starts_with($resource, 'v:') ? 'El vehículo' : 'La persona';
                    $reports[$index]['errors'][] = ['reason'=>$type.' '.$resourceName.' también está asignado al grupo '.$other['group'], 'dates'=>[$date]];
                    $reports[$other['index']]['errors'][] = ['reason'=>$type.' '.$resourceName.' también está asignado al grupo '.$result['group']->name, 'dates'=>[$date]];
                } else {
                    $reservations[$key] = ['index'=>$index, 'group'=>$result['group']->name];
                }
            }
            $valid = $valid && ! count($issues);
        }
        $valid = $valid && $totalDates > 0 && ! collect($reports)->contains(fn ($report) => count($report['errors']));
        $token = null;
        if ($valid) { $token = Str::random(64); $request->session()->put('programming.bulk', ['token'=>$token,'hash'=>$this->fingerprint($data),'expires'=>now()->addMinutes(15)->timestamp]); }
        else { $request->session()->forget('programming.bulk'); }
        return response()->json(['valid'=>$valid, 'reports'=>$reports, 'validation_token'=>$token, 'message'=>$totalDates ? null : 'No hay fechas programables para los turnos y el período seleccionados.']);
    }

    public function bulkGenerate(Request $request)
    {
        $data = $this->validatedBulk($request);
        $saved = $request->session()->get('programming.bulk');
        if (! $saved || $saved['expires'] < now()->timestamp || ! hash_equals($saved['token'], (string) $request->input('validation_token')) || ! hash_equals($saved['hash'], $this->fingerprint($data))) $this->reject('Valide nuevamente la programación masiva antes de generarla.');
        $ids = DB::transaction(function () use ($data, $request) { $ids=[]; foreach ($data['groups'] as $item) { $itemData = $this->bulkItem($data, $item); if (! $this->service->inspect($itemData)['dates']) continue; $out=$this->service->save($itemData, (int)$request->user()->id); $ids=array_merge($ids,$out['created_ids']); } return $ids; }, 3);
        $request->session()->forget('programming.bulk');
        return response()->json(['message'=>count($ids).' programación(es) creada(s).', 'ids'=>$ids]);
    }

    public function bulkUndo(Request $request)
    {
        $ids = $request->validate(['ids'=>['required','array','min:1','max:1000'], 'ids.*'=>['integer','distinct','exists:programmings,id']])['ids'];
        DB::transaction(function () use ($ids, $request) { foreach (Programming::whereIn('id',$ids)->lockForUpdate()->get() as $row) { if ($row->created_by !== $request->user()->id || $row->status !== 'Programado') $this->reject('Solo puede deshacer programaciones masivas propias que sigan pendientes.'); $before=$row->toArray(); $row->employees()->detach(); $row->update(['status'=>'Anulado','group_date_key'=>null,'vehicle_date_key'=>null]); $row->changes()->create(['user_id'=>$request->user()->id,'action'=>'Reversión masiva','before'=>$before,'after'=>$row->toArray()]); } }, 3);
        return response()->json(['message'=>'Programación masiva deshecha.']);
    }

    public function bulkModificationCheck(Request $request)
    {
        $request->session()->forget('programming.bulk-modification');
        $data = $this->validatedBulkModification($request, false);
        $result = $this->bulkModificationService->preview($data);
        if ($result['valid']) {
            // The preview discovers the affected rows. Bind that exact set to
            // the confirmation token without requiring it on the preview call.
            $data['ids'] = array_values(array_map('intval', $result['ids'] ?? []));
            $token = Str::random(64);
            $request->session()->put('programming.bulk-modification', [
                'token' => $token,
                'hash' => $this->bulkModificationFingerprint($data),
                'operation_id' => (string) Str::uuid(),
                'expires' => now()->addMinutes(15)->timestamp,
            ]);
            $result['validation_token'] = $token;
        } else {
            $result['validation_token'] = null;
        }

        return response()->json($result);
    }

    public function bulkModificationOptions(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'type' => ['required', 'in:shift,driver,helper,vehicle'],
        ], ['end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.']);
        $rows = Programming::with('employees')->whereIn('status', [Programming::STATUS_PROGRAMMED, Programming::STATUS_RESCHEDULED])->where('zone_id', $data['zone_id'])
            ->whereDate('date', '>=', $data['start_date'])->whereDate('date', '<=', $data['end_date'])->get();
        $helperIds = $rows->flatMap(fn (Programming $row) => $row->employees->where('pivot.role', 'helper')->pluck('id'))->unique()->values();
        $people = Employee::where('status', 'Activo')->when($data['type'] === 'driver', fn ($q) => $q->where('employee_type_id', 1))->when($data['type'] === 'helper', fn ($q) => $q->where('employee_type_id', '!=', 1))->orderBy('last_name')->orderBy('first_name')->get()
            ->map(fn (Employee $employee) => ['id' => $employee->id, 'name' => trim($employee->first_name.' '.$employee->last_name), 'dni' => $employee->dni]);
        $oldHelpers = Employee::whereIn('id', $helperIds)->orderBy('last_name')->orderBy('first_name')->get()->map(fn (Employee $employee) => ['id' => $employee->id, 'name' => trim($employee->first_name.' '.$employee->last_name), 'dni' => $employee->dni])->values();
        return response()->json(['count' => $rows->count(), 'people' => $people->values(), 'old_helpers' => $oldHelpers, 'vehicles' => Vehicle::orderBy('name')->get(['id', 'name', 'code', 'plate', 'occupats_capacity'])]);
    }

    public function bulkModificationApply(Request $request)
    {
        $saved = $request->session()->get('programming.bulk-modification');
        $request->session()->forget('programming.bulk-modification');
        $data = $this->validatedBulkModification($request);
        if (! $saved || $saved['expires'] < now()->timestamp
            || ! hash_equals($saved['token'], (string) $request->input('validation_token'))
            || ! hash_equals($saved['hash'], $this->bulkModificationFingerprint($data))) {
            $this->reject('La vista previa expiró o los datos cambiaron. Consulte nuevamente antes de confirmar.');
        }

        $count = $this->bulkModificationService->apply($data, (int) $request->user()->id, $saved['operation_id']);

        return response()->json(['message' => $count.' programación(es) modificada(s) correctamente.', 'count' => $count]);
    }

    public function check(Request $request)
    {
        $data = $this->validated($request);
        $editing = $data['programming_id'] ? Programming::findOrFail($data['programming_id']) : null;
        $result = $this->service->inspect($data, $editing);
        $token = null;
        $canSave = ! $result['issues'] && count($result['dates']) > 0;
        if ($canSave) {
            $token = Str::random(64);
            $request->session()->put('programming.validation', ['token' => $token, 'hash' => $this->fingerprint($data), 'group_hash' => $this->service->groupFingerprint($result['group']), 'expires' => now()->addMinutes(15)->timestamp]);
        } else {
            $request->session()->forget('programming.validation');
        }

        return response()->json([
            'valid' => $canSave, 'count' => count($result['dates']), 'dates' => $result['dates'],
            'issues' => $result['issues'], 'suggestions' => $result['suggestions'],
            'omitted_holidays' => $result['omittedHolidays'], 'omitted_duplicates' => $result['omittedDuplicates'],
            'included_holidays' => $result['includedHolidays'],
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

    public function reschedulingOptions(Request $request, Programming $programming)
    {
        $data = $request->validate([
            'destination_date' => ['nullable', 'date_format:Y-m-d'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
        ]);
        return response()->json($this->reschedulingService->options($programming, $data['destination_date'] ?? null, isset($data['shift_id']) ? (int) $data['shift_id'] : null));
    }

    public function reschedulingCheck(Request $request, Programming $programming)
    {
        $data = $this->validatedRescheduling($request);
        $result = $this->reschedulingService->preview($programming, $data);
        if ($result['valid']) {
            $token = Str::random(64);
            $request->session()->put('programming.rescheduling', ['token' => $token, 'hash' => $this->fingerprint($data), 'expires' => now()->addMinutes(15)->timestamp]);
            $result['validation_token'] = $token;
        } else {
            $request->session()->forget('programming.rescheduling');
            $result['validation_token'] = null;
        }
        return response()->json($result);
    }

    public function reschedule(Request $request, Programming $programming)
    {
        $saved = $request->session()->get('programming.rescheduling');
        $data = $this->validatedRescheduling($request);
        $request->session()->forget('programming.rescheduling');
        if (! $saved || $saved['expires'] < now()->timestamp || ! hash_equals($saved['token'], (string) $request->input('validation_token')) || ! hash_equals($saved['hash'], $this->fingerprint($data))) {
            $this->reject('La vista previa expiró o los datos cambiaron. Vuelva a validar antes de confirmar.');
        }
        return response()->json($this->reschedulingService->confirm($programming, $data, (int) $request->user()->id), 201);
    }

    public function related(Programming $programming)
    {
        return response()->json($this->reschedulingService->related($programming));
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
            'work_holidays' => ! empty($programming->snapshot['holiday_work']) ? [$programming->date->toDateString()] : [],
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

    public function modifications(Programming $programming)
    {
        $programming->load('employees');
        $employeeIds = $programming->employees->pluck('id');
        $presentIds = \App\Models\admin\Attendance::query()->whereDate('date', $programming->date)
            ->where('shift_id', $programming->shift_id)->whereIn('employee_id', $employeeIds)
            ->where('status', 'Presente')->distinct()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
        $people = $programming->employees->unique('id')->map(fn ($employee) => [
            'id' => (int) $employee->id,
            'name' => trim($employee->first_name.' '.$employee->last_name),
            'role' => $employee->pivot->role,
            'present' => in_array((int) $employee->id, $presentIds, true),
        ])->values();
        $driver = $programming->employees->firstWhere('id', $programming->driver_id);

        return response()->json([
            'current' => [...$this->row($programming),
                'shift_id' => $programming->shift_id,
                'vehicle_id' => $programming->vehicle_id,
                'driver_id' => $programming->driver_id,
                'helper_ids' => $programming->employees->where('pivot.role', 'helper')->pluck('id')->values(),
                'helper_people' => $programming->employees->where('pivot.role', 'helper')->map(fn ($employee) => [
                    'id' => $employee->id, 'name' => trim($employee->first_name.' '.$employee->last_name),
                    'present' => in_array((int) $employee->id, $presentIds, true),
                ])->values(),
                'driver' => $programming->snapshot['driver'] ?? ($driver ? trim($driver->first_name.' '.$driver->last_name) : 'Sin conductor'),
                'people' => $people,
                'present' => $people->where('present', true)->values(),
                'missing' => $people->where('present', false)->values(),
                'present_count' => $people->where('present', true)->count(),
                'missing_count' => $people->where('present', false)->count(),
            ],
            'options' => $this->modificationService->options($programming, (int) request('shift_id', $programming->shift_id)),
            'history' => $programming->changes()->with('user:id,name')->orderByDesc('id')->get()
                ->filter(fn ($change) => str_starts_with($change->action, 'Cambio de ') || $change->action === 'Retiro de ayudante')
                ->map(fn ($change) => [
                    'id' => $change->id, 'type' => $change->action, 'before' => $change->before, 'after' => $change->after,
                    'user' => $change->user?->name ?? 'Usuario no disponible',
                    'at' => $change->created_at->format('d/m/Y H:i'),
                ])->values(),
        ]);
    }

    public function modificationOptions(Request $request, Programming $programming)
    {
        $data = $request->validate(['shift_id' => ['required', 'integer', 'exists:shifts,id']]);

        return response()->json($this->modificationService->options($programming, (int) $data['shift_id']));
    }

    public function updateModifications(Request $request, Programming $programming)
    {
        $data = $request->validate([
            'changes' => ['required', 'array', 'min:1', 'max:20'],
            'changes.*.type' => ['required', 'in:shift,vehicle,driver,helper'],
            'changes.*.new_id' => ['required', 'integer', 'min:1'],
            'changes.*.is_retainer' => ['nullable', 'boolean'],
            'changes.*.old_id' => ['required_if:changes.*.type,helper', 'nullable', 'integer', 'min:1'],
            'changes.*.reason' => ['required', 'string', 'max:100', 'exists:reason,name'],
            'changes.*.custom_reason' => ['nullable', 'string', 'max:120', 'required_if:changes.*.reason,Otro'],
            'changes.*.description' => ['nullable', 'string', 'max:1000'],
        ], [
            'changes.required' => 'Agregue al menos un cambio antes de guardar.',
            'changes.*.reason.required' => 'Cada cambio debe incluir un motivo.',
            'changes.*.custom_reason.required_if' => 'Especifique el motivo cuando seleccione “Otro”.',
        ]);

        $count = $this->modificationService->apply($programming, $data['changes'], (int) $request->user()->id);

        return response()->json(['message' => 'Modificaciones guardadas correctamente.', 'count' => $count]);
    }

    public function finish(Request $request, Programming $programming)
    {
        DB::transaction(function () use ($programming, $request) {
            $row = Programming::whereKey($programming->id)->lockForUpdate()->firstOrFail();
            if (! in_array($row->status, [Programming::STATUS_PROGRAMMED, Programming::STATUS_RESCHEDULED], true)) {
                $this->reject('El recorrido ya está finalizado.');
            }
            $before = $row->toArray();
            $row->update(['status' => 'Finalizado', 'finished_by' => $request->user()->id, 'finished_at' => now()]);
            $row->changes()->create(['user_id' => $request->user()->id, 'action' => 'Finalización web', 'before' => $before, 'after' => $row->toArray()]);
        });

        return response()->json(['message' => 'Recorrido finalizado correctamente.']);
    }

    public function annul(Request $request, Programming $programming)
    {
        DB::transaction(function () use ($programming, $request) {
            $row = Programming::whereKey($programming->id)->lockForUpdate()->firstOrFail();
            if (! in_array($row->status, [Programming::STATUS_PROGRAMMED, Programming::STATUS_RESCHEDULED], true)) {
                $this->reject('Solo se puede anular un recorrido programado.');
            }
            $before = $row->toArray();
            $row->employees()->detach();
            $row->update(['status' => 'Anulado', 'group_date_key' => null, 'vehicle_date_key' => null]);
            $row->changes()->create(['user_id' => $request->user()->id, 'action' => 'Anulación', 'before' => $before, 'after' => $row->toArray()]);
        });

        return response()->json(['message' => 'Programación anulada correctamente.']);
    }

    public function destroy(Request $request, Programming $programming)
    {
        DB::transaction(function () use ($programming, $request) {
            $row = Programming::whereKey($programming->id)->lockForUpdate()->firstOrFail();
            if (! in_array($row->status, [Programming::STATUS_PROGRAMMED, Programming::STATUS_RESCHEDULED], true)) {
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
            'group_id' => $row->staff_group_id, 'parent_programming_id' => $row->parent_programming_id,
            'child_programming_id' => $row->childProgrammings()->value('id'), ...$row->snapshot, 'notes' => $row->notes,
            'finished_at' => $row->finished_at?->format('d/m/Y H:i')];
    }

    private function validated(Request $request, bool $requirePeople = true): array
    {
        $data = $request->validate([
            'staff_group_id' => ['required', 'integer', 'exists:staff_groups,id'],
            'driver_id' => $requirePeople ? ['required', 'integer', 'exists:employees,id'] : ['exclude'],
            'helpers' => $requirePeople ? ['present', 'array'] : ['exclude'], 'helpers.*' => ['required', 'integer', 'distinct', 'exists:employees,id'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.today('America/Lima')->toDateString()],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1'], 'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'programming_id' => ['nullable', 'integer', 'exists:programmings,id'],
            'work_holidays' => ['sometimes', 'array', 'max:366'],
            'work_holidays.*' => ['required', 'date_format:Y-m-d', 'distinct'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor seleccionado para :attribute no existe.',
            'date_format' => 'El campo :attribute debe tener formato AAAA-MM-DD.',
            'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            'start_date.after_or_equal' => 'La programación debe comenzar hoy o en una fecha futura.',
            'days.min' => 'Seleccione al menos un día de trabajo.',
            'helpers.*.distinct' => 'No puede seleccionar al mismo ayudante más de una vez.',
            'notes.max' => 'Las observaciones no deben superar los 1000 caracteres.',
            'work_holidays.*.distinct' => 'No puede repetir un feriado seleccionado.',
        ], ['staff_group_id' => 'grupo de personal', 'driver_id' => 'conductor', 'start_date' => 'fecha de inicio', 'end_date' => 'fecha de fin', 'days' => 'días de trabajo', 'helpers' => 'ayudantes']);
        if (CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) > 365) {
            $this->reject('Seleccione un período máximo de 366 días por operación.');
        }
        $data['staff_group_id'] = (int) $data['staff_group_id'];
        $data['driver_id'] = (int) ($data['driver_id'] ?? 0);
        $data['vehicle_id'] = empty($data['vehicle_id']) ? null : (int) $data['vehicle_id'];
        $data['programming_id'] = empty($data['programming_id']) ? null : (int) $data['programming_id'];
        $data['helpers'] = array_map('intval', $data['helpers'] ?? []);
        $data['days'] = array_map('intval', $data['days']);
        sort($data['days']);
        $data['notes'] = trim($data['notes'] ?? '');
        $data['work_holidays'] = $data['work_holidays'] ?? [];
        sort($data['work_holidays']);

        return $data;
    }

    private function validatedRescheduling(Request $request): array
    {
        $data = $request->validate([
            'destination_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.today('America/Lima')->toDateString()],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'integer', 'exists:employees,id'],
            'helpers' => ['present', 'array'], 'helpers.*' => ['integer', 'distinct', 'exists:employees,id'],
            'reason_id' => ['nullable', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:100', 'exists:reason,name'],
            'description' => ['nullable', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:1000'],
            'validation_token' => ['nullable', 'string'],
        ], ['destination_date.after_or_equal' => 'La fecha destino no puede estar en el pasado.', 'reason_id.required' => 'El motivo es obligatorio.']);
        if (empty($data['reason_id']) && empty($data['reason'])) $this->reject('El motivo es obligatorio.');
        $reason = ! empty($data['reason_id']) ? Reason::find((int) $data['reason_id']) : null;
        if (! $reason && ! empty($data['reason'])) $reason = Reason::where('name', $data['reason'])->first();
        if (! $reason) $this->reject('El motivo seleccionado no existe.');
        $data['reason_id'] = (int) $reason->id;
        $data['shift_id'] = (int) $data['shift_id']; $data['vehicle_id'] = (int) $data['vehicle_id']; $data['driver_id'] = (int) $data['driver_id'];
        $data['helpers'] = array_values(array_map('intval', $data['helpers'] ?? [])); $data['description'] = trim($data['description'] ?? ''); $data['notes'] = trim($data['notes'] ?? '');
        unset($data['validation_token']);
        return $data;
    }

    private function fingerprint(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function bulkModificationFingerprint(array $data): string
    {
        ksort($data);
        return $this->fingerprint($data);
    }

    private function validatedBulk(Request $request): array
    {
        $data = $request->validate([
            'start_date'=>['required','date_format:Y-m-d','after_or_equal:'.today('America/Lima')->toDateString()], 'end_date'=>['required','date_format:Y-m-d','after_or_equal:start_date'],
            'excluded_holidays'=>['sometimes','array'], 'excluded_holidays.*'=>['date_format:Y-m-d','distinct'],
            'groups'=>['required','array','min:1'], 'groups.*.staff_group_id'=>['required','integer','distinct','exists:staff_groups,id'], 'groups.*.vehicle_id'=>['required','integer','exists:vehicles,id'], 'groups.*.driver_id'=>['required','integer','exists:employees,id'], 'groups.*.helpers'=>['present','array'], 'groups.*.helpers.*'=>['integer','exists:employees,id'],
        ], [
            'groups.required' => 'Debe incluir al menos un grupo de trabajo.',
            'groups.*.staff_group_id.required' => 'El grupo de trabajo es obligatorio.',
            'groups.*.staff_group_id.exists' => 'El grupo de trabajo seleccionado no existe.',
            'groups.*.vehicle_id.required' => 'El vehículo es obligatorio.',
            'groups.*.vehicle_id.exists' => 'El vehículo seleccionado no existe.',
            'groups.*.driver_id.required' => 'El conductor es obligatorio.',
            'groups.*.driver_id.exists' => 'El conductor seleccionado no es válido.',
            'groups.*.helpers.array' => 'Los ayudantes seleccionados no son válidos.',
            'groups.*.helpers.*.exists' => 'Uno de los ayudantes seleccionados no es válido.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
        ]);
        if (CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) > 365) $this->reject('Seleccione un período máximo de 366 días por operación.');
        $data['excluded_holidays'] = $data['excluded_holidays'] ?? [];
        foreach ($data['groups'] as &$group) {
            $group['helpers']=array_map('intval',$group['helpers']); $group['staff_group_id']=(int)$group['staff_group_id']; $group['vehicle_id']=(int)$group['vehicle_id']; $group['driver_id']=(int)$group['driver_id'];
            $groupName = StaffGroup::find($group['staff_group_id'])?->name ?? 'seleccionado';
            $repeated = collect($group['helpers'])->duplicates()->first();
            if ($repeated !== null) {
                $employee = Employee::find($repeated);
                $this->reject('Grupo '.$groupName.': el ayudante '.($employee ? $this->service->name($employee) : 'seleccionado').' está repetido dentro del mismo grupo.');
            }
            if (in_array($group['driver_id'], $group['helpers'], true)) {
                $employee = Employee::find($group['driver_id']);
                $this->reject('Grupo '.$groupName.': '.($employee ? $this->service->name($employee) : 'la persona seleccionada').' no puede ser conductor y ayudante a la vez.');
            }
            $vehicle = Vehicle::findOrFail($group['vehicle_id']);
            $maximumHelpers = max(0, (int) $vehicle->occupats_capacity - 1);
            if (count($group['helpers']) > $maximumHelpers) {
                $this->reject('El grupo '.$groupName.' supera la capacidad del vehículo. Se permiten como máximo '.$maximumHelpers.' ayudante(s).');
            }
        }
        unset($group); sort($data['excluded_holidays']);
        return $data;
    }

    private function bulkItem(array $bulk, array $item): array
    {
        $group = StaffGroup::with('days')->findOrFail($item['staff_group_id']);
        $workHolidays = Holiday::where('state', true)->whereBetween('date', [$bulk['start_date'], $bulk['end_date']])->get()->pluck('date')->map(fn ($date) => $date->toDateString())->reject(fn ($date) => in_array($date, $bulk['excluded_holidays'], true))->values()->all();
        return ['staff_group_id'=>$item['staff_group_id'], 'vehicle_id'=>$item['vehicle_id'], 'driver_id'=>$item['driver_id'], 'helpers'=>$item['helpers'], 'start_date'=>$bulk['start_date'], 'end_date'=>$bulk['end_date'], 'days'=>$group->days->pluck('day')->map(fn($day)=>(int)$day)->all(), 'work_holidays'=>$workHolidays, 'notes'=>null, 'programming_id'=>null];
    }

    private function validatedBulkModification(Request $request, bool $requireIds = true): array
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
            'start_date.required' => 'La fecha inicial es obligatoria.',
            'end_date.required' => 'La fecha final es obligatoria.',
            'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            'zone_id.required' => 'La zona es obligatoria.',
            'type.required' => 'Seleccione el tipo de cambio.',
            'ids.required' => 'Consulte las programaciones antes de confirmar.',
            'reason.required' => 'El motivo es obligatorio.',
            'custom_reason.required_if' => 'Especifique el motivo cuando seleccione “Otro”.',
            'new_driver_id.required_if' => 'Seleccione un conductor.',
            'new_driver_id.integer' => 'El conductor seleccionado no es válido.',
            'new_driver_id.exists' => 'El conductor seleccionado no existe.',
            'new_employee_id.required_if' => 'Seleccione el nuevo ocupante.',
            'new_employee_id.integer' => 'El nuevo ocupante seleccionado no es válido.',
            'new_employee_id.exists' => 'El nuevo ocupante seleccionado no existe.',
            'old_employee_id.required_if' => 'Seleccione el ocupante actual.',
            'old_employee_id.exists' => 'El ocupante actual no existe.',
            'new_vehicle_id.required_if' => 'Seleccione un vehículo.',
            'new_vehicle_id.exists' => 'El vehículo seleccionado no existe.',
            'new_shift_id.required_if' => 'Seleccione un turno.',
            'new_shift_id.exists' => 'El turno seleccionado no existe.',
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
            if (array_key_exists($key, $data) && $data[$key] !== null) $data[$key] = (int) $data[$key];
        }
        unset($data['validation_token']);
        return $data;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['programming' => $message]);
    }
}
