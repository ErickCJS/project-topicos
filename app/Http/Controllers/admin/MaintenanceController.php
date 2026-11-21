<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use App\Models\admin\Maintenance;
use App\Models\admin\MaintenanceDetail;
use App\Models\admin\MaintenanceSchedule;
use App\Models\admin\Vehicle;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::eloquent(Maintenance::query())
                ->editColumn('start_date', fn (Maintenance $maintenance) => $maintenance->start_date?->format('Y-m-d'))
                ->editColumn('end_date', fn (Maintenance $maintenance) => $maintenance->end_date?->format('Y-m-d'))
                ->editColumn('created_at', fn (Maintenance $maintenance) => $maintenance->created_at?->format('d/m/Y H:i'))
                ->addColumn('actions', function (Maintenance $maintenance) {
                    $actions = '<div class="maintenance-actions d-flex justify-content-center gap-2">';

                    if (Gate::allows('permission', 'mantenimientos.edit')) {
                        $actions .= '<button type="button" class="btn btn-outline-warning btn-sm btnEditarMantenimiento"'
                            .' data-name="'.e($maintenance->name).'"'
                            .' data-start-date="'.e($maintenance->start_date?->format('Y-m-d')).'"'
                            .' data-end-date="'.e($maintenance->end_date?->format('Y-m-d')).'"'
                            .' data-url="'.e(route('maintenances.update', $maintenance)).'"'
                            .' title="Editar mantenimiento" aria-label="Editar mantenimiento">'
                            .'<i class="bi bi-pencil-square" aria-hidden="true"></i></button>';
                    }

                    if (Gate::allows('permission', 'mantenimientos.delete')) {
                        $actions .= '<button type="button" class="btn btn-outline-danger btn-sm btnEliminarMantenimiento"'
                            .' data-url="'.e(route('maintenances.destroy', $maintenance)).'"'
                            .' title="Eliminar mantenimiento" aria-label="Eliminar mantenimiento">'
                            .'<i class="bi bi-trash" aria-hidden="true"></i></button>';
                    }

                    if (Gate::allows('permission', 'mantenimientos.view')) {
                        $actions .= '<button type="button" class="btn btn-outline-primary btn-sm btnHorariosMantenimiento"'
                            .' data-url="'.e(route('maintenances.schedules.index', $maintenance)).'"'
                            .' title="Ver horarios" aria-label="Ver horarios">'
                            .'<i class="bi bi-calendar-week" aria-hidden="true"></i></button>';
                    }

                    return $actions.'</div>';
                })
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('admin.maintenances.index');
    }

    public function schedules(Maintenance $maintenance)
    {
        $weekday = request()->integer('weekday') ?: null;

        return response()->json([
            'maintenance' => [
                'id' => $maintenance->id,
                'name' => $maintenance->name,
                'start_date' => $maintenance->start_date->format('Y-m-d'),
                'end_date' => $maintenance->end_date->format('Y-m-d'),
            ],
            'schedules' => $maintenance->schedules()
                ->with(['vehicle:id,name,code,plate', 'responsible:id,dni,first_name,last_name', 'details:id,maintenance_schedule_id,scheduled_date'])
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get()
                ->map(fn (MaintenanceSchedule $schedule) => [
                    'id' => $schedule->id,
                    'vehicle_id' => $schedule->vehicle_id,
                    'responsible_id' => $schedule->responsible_id,
                    'vehicle' => trim($schedule->vehicle->code.' - '.$schedule->vehicle->name.' ('.$schedule->vehicle->plate.')'),
                    'responsible' => trim($schedule->responsible->first_name.' '.$schedule->responsible->last_name),
                    'type' => $schedule->type,
                    'weekday' => $schedule->weekday,
                    'start_time' => substr($schedule->start_time, 0, 5),
                    'end_time' => substr($schedule->end_time, 0, 5),
                    'dates' => $schedule->details->pluck('scheduled_date')->map(fn ($date) => Carbon::parse($date)->format('d/m/Y'))->values(),
                ])->values(),
            'vehicles' => Vehicle::orderBy('name')->get(['id', 'name', 'code', 'plate']),
            'responsibles' => $this->responsiblesForSchedule($maintenance, $weekday),
            'permissions' => [
                'view' => Gate::allows('permission', 'mantenimientos.view'),
                'create' => Gate::allows('permission', 'mantenimientos.create'),
                'edit' => Gate::allows('permission', 'mantenimientos.edit'),
                'delete' => Gate::allows('permission', 'mantenimientos.delete'),
            ],
        ]);
    }

    public function details(Maintenance $maintenance, MaintenanceSchedule $maintenanceSchedule)
    {
        $schedule = $maintenance->schedules()->findOrFail($maintenanceSchedule->id);

        return response()->json([
            'schedule' => [
                'id' => $schedule->id,
                'vehicle' => $schedule->vehicle->name,
                'type' => $schedule->type,
            ],
            'details' => $schedule->details()
                ->orderBy('scheduled_date')
                ->get()
                ->map(fn (MaintenanceDetail $detail) => $this->detailData($detail))
                ->values(),
        ]);
    }

    public function updateDetail(Request $request, Maintenance $maintenance, MaintenanceSchedule $maintenanceSchedule, MaintenanceDetail $maintenanceDetail)
    {
        $schedule = $maintenance->schedules()->findOrFail($maintenanceSchedule->id);
        $detail = $schedule->details()->findOrFail($maintenanceDetail->id);

        $data = $request->validate([
            'observation' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:Pendiente,Realizado,No realizado'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'observation.max' => 'La observación no debe superar los 1000 caracteres.',
            'status.required' => 'Seleccione un estado.',
            'status.in' => 'El estado seleccionado no es válido.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe estar en formato JPG, JPEG o PNG.',
            'image.max' => 'La imagen no debe superar los 2 MB.',
        ]);

        $oldImagePath = $detail->image_path;
        $newImagePath = $request->hasFile('image')
            ? $request->file('image')->store('maintenance-details', 'public')
            : $oldImagePath;

        $detail->update([
            'observation' => $data['observation'] ?? null,
            'status' => $data['status'],
            'image_path' => $newImagePath,
        ]);

        if ($request->hasFile('image') && $oldImagePath && $oldImagePath !== $newImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return response()->json([
            'message' => 'Detalle actualizado correctamente.',
            'data' => $this->detailData($detail->fresh()),
        ]);
    }

    private function detailData(MaintenanceDetail $detail): array
    {
        return [
            'id' => $detail->id,
            'scheduled_date' => $detail->scheduled_date->format('d/m/Y'),
            'observation' => $detail->observation,
            'status' => $detail->status,
            'image_url' => $detail->image_path ? asset('storage/'.$detail->image_path) : null,
        ];
    }

    public function storeSchedule(Request $request, Maintenance $maintenance)
    {
        $schedule = $this->saveSchedule($request, $maintenance);

        return response()->json([
            'message' => 'Horario registrado correctamente.',
            'data' => $schedule,
        ], 201);
    }

    public function updateSchedule(Request $request, Maintenance $maintenance, MaintenanceSchedule $maintenanceSchedule)
    {
        $schedule = $maintenance->schedules()->findOrFail($maintenanceSchedule->id);
        $schedule = $this->saveSchedule($request, $maintenance, $schedule);

        return response()->json([
            'message' => 'Horario actualizado correctamente.',
            'data' => $schedule,
        ]);
    }

    public function destroySchedule(Maintenance $maintenance, MaintenanceSchedule $maintenanceSchedule)
    {
        $schedule = $maintenance->schedules()->findOrFail($maintenanceSchedule->id);

        DB::transaction(function () use ($schedule) {
            $schedule->details()->delete();
            $schedule->delete();
        });

        return response()->json([
            'message' => 'Horario y fechas generadas eliminados correctamente.',
        ]);
    }

    public function store(Request $request)
    {
        $maintenance = Maintenance::create($this->validatedData($request));

        return response()->json([
            'message' => 'Mantenimiento registrado correctamente.',
            'data' => $maintenance,
        ], 201);
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $maintenance->update($this->validatedData($request, $maintenance));

        return response()->json([
            'message' => 'Mantenimiento actualizado correctamente.',
            'data' => $maintenance->fresh(),
        ]);
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->delete();

        return response()->json([
            'message' => 'Mantenimiento eliminado correctamente.',
        ]);
    }

    private function validatedData(Request $request, ?Maintenance $maintenance = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [
            'name.required' => 'El nombre del mantenimiento es obligatorio.',
            'name.max' => 'El nombre no debe superar los 150 caracteres.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date_format' => 'La fecha de inicio no tiene un formato válido.',
            'end_date.required' => 'La fecha final es obligatoria.',
            'end_date.date_format' => 'La fecha final no tiene un formato válido.',
            'end_date.after_or_equal' => 'La fecha de inicio no puede ser posterior a la fecha final.',
        ]);

        $overlap = Maintenance::query()
            ->where('id', '!=', $maintenance?->id ?? 0)
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'El período se solapa con otro mantenimiento registrado.',
            ]);
        }

        return $data;
    }

    private function saveSchedule(Request $request, Maintenance $maintenance, ?MaintenanceSchedule $schedule = null): MaintenanceSchedule
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'responsible_id' => ['required', 'integer', 'exists:employees,id'],
            'type' => ['required', 'in:Preventivo,Limpieza,Reparación'],
            'weekday' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ], [
            'vehicle_id.required' => 'Seleccione un vehículo.',
            'vehicle_id.exists' => 'El vehículo seleccionado no existe.',
            'responsible_id.required' => 'Seleccione un responsable.',
            'responsible_id.exists' => 'El responsable seleccionado no existe.',
            'type.required' => 'Seleccione el tipo de mantenimiento.',
            'type.in' => 'El tipo de mantenimiento no es válido.',
            'weekday.required' => 'Seleccione un día de la semana.',
            'weekday.between' => 'El día de la semana no es válido.',
            'start_time.required' => 'Ingrese la hora de inicio.',
            'start_time.date_format' => 'La hora de inicio no tiene un formato válido.',
            'end_time.required' => 'Ingrese la hora final.',
            'end_time.date_format' => 'La hora final no tiene un formato válido.',
        ]);

        if ($data['start_time'] >= $data['end_time']) {
            throw ValidationException::withMessages([
                'end_time' => 'La hora de inicio debe ser menor que la hora final.',
            ]);
        }

        $dates = $this->scheduleDates($maintenance, (int) $data['weekday']);
        $responsible = Employee::with('contracts')
            ->where('status', 'Activo')
            ->find($data['responsible_id']);

        if (! $responsible || ! $this->hasContractForDates($responsible, $dates)) {
            throw ValidationException::withMessages([
                'responsible_id' => 'El responsable seleccionado no tiene contrato vigente para todas las fechas programadas.',
            ]);
        }

        return DB::transaction(function () use ($data, $dates, $maintenance, $schedule) {
            $this->ensureScheduleHasNoOverlap($data, $dates, $schedule?->id);

            $schedule = $schedule
                ? $maintenance->schedules()->lockForUpdate()->findOrFail($schedule->id)
                : new MaintenanceSchedule;

            $schedule->fill([
                'maintenance_id' => $maintenance->id,
                'vehicle_id' => $data['vehicle_id'],
                'responsible_id' => $data['responsible_id'],
                'type' => $data['type'],
                'weekday' => $data['weekday'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);
            $schedule->save();

            $this->syncScheduleDetails($schedule, $dates);

            return $schedule->load(['vehicle', 'responsible', 'details']);
        });
    }

    private function scheduleDates(Maintenance $maintenance, int $weekday): array
    {
        return collect(CarbonPeriod::create($maintenance->start_date, $maintenance->end_date))
            ->filter(fn (Carbon $date) => $date->dayOfWeekIso === $weekday)
            ->map(fn (Carbon $date) => $date->toDateString())
            ->values()
            ->all();
    }

    private function responsiblesForSchedule(Maintenance $maintenance, ?int $weekday): array
    {
        if (! $weekday || $weekday < 1 || $weekday > 7) {
            return [];
        }

        $dates = $this->scheduleDates($maintenance, $weekday);

        return Employee::with('contracts')
            ->where('status', 'Activo')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'dni', 'first_name', 'last_name'])
            ->filter(fn (Employee $employee) => $this->hasContractForDates($employee, $dates))
            ->values()
            ->all();
    }

    private function hasContractForDates(Employee $employee, array $dates): bool
    {
        return collect($dates)->every(function (string $date) use ($employee): bool {
            return $employee->contracts->contains(function ($contract) use ($date): bool {
                return $contract->is_active
                    && ! $contract->cancelled_at
                    && $contract->start_date->toDateString() <= $date
                    && (! $contract->end_date || $contract->end_date->toDateString() >= $date);
            });
        });
    }

    private function ensureScheduleHasNoOverlap(array $data, array $dates, ?int $excludedScheduleId = null): void
    {
        $schedules = MaintenanceSchedule::with('maintenance')
            ->where('vehicle_id', $data['vehicle_id'])
            ->when($excludedScheduleId, fn ($query) => $query->where('id', '!=', $excludedScheduleId))
            ->get();

        foreach ($dates as $date) {
            foreach ($schedules as $existing) {
                $existingMaintenance = $existing->maintenance;

                if ($date < $existingMaintenance->start_date->toDateString()
                    || $date > $existingMaintenance->end_date->toDateString()
                    || (int) $existing->weekday !== Carbon::parse($date)->dayOfWeekIso) {
                    continue;
                }

                $newStart = Carbon::parse($date.' '.$data['start_time']);
                $newEnd = Carbon::parse($date.' '.$data['end_time']);
                $existingStart = Carbon::parse($date.' '.substr($existing->start_time, 0, 5));
                $existingEnd = Carbon::parse($date.' '.substr($existing->end_time, 0, 5));

                if ($newStart < $existingEnd && $existingStart < $newEnd) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'El vehículo ya tiene un horario solapado para el '.Carbon::parse($date)->format('d/m/Y').'.',
                    ]);
                }
            }
        }
    }

    private function syncScheduleDetails(MaintenanceSchedule $schedule, array $dates): void
    {
        if ($dates) {
            $schedule->details()->whereNotIn('scheduled_date', $dates)->delete();
        } else {
            $schedule->details()->delete();
        }

        $existingDates = $schedule->details()->pluck('scheduled_date')
            ->map(fn ($date) => substr((string) $date, 0, 10))
            ->all();

        foreach (array_diff($dates, $existingDates) as $date) {
            $schedule->details()->create([
                'scheduled_date' => $date,
                'status' => MaintenanceDetail::STATUS_PENDING,
            ]);
        }
    }
}
