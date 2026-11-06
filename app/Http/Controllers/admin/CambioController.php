<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\ProgrammingChange;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class CambioController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $filters = $request->validate([
                'filter_start' => ['nullable', 'date_format:Y-m-d'],
                'filter_end' => ['nullable', 'date_format:Y-m-d'],
                'change_type' => ['nullable', 'in:bulk,shift,driver,helper,vehicle,rescheduling'],
            ]);
            $start = $filters['filter_start'] ?? null;
            $end = $filters['filter_end'] ?? null;
            if ($start && $end && $start > $end) {
                throw ValidationException::withMessages(['filter_end' => 'La fecha final no puede ser anterior a la fecha inicial.']);
            }

            $query = ProgrammingChange::query()
                ->with(['user', 'programming', 'originProgramming', 'destinationProgramming'])
                ->select('programming_changes.*');

            if ($start) $query->whereDate('programming_changes.created_at', '>=', $start);
            if ($end) $query->whereDate('programming_changes.created_at', '<=', $end);

            if ($search = trim((string) $request->input('search.value'))) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('programming_changes.action', 'like', '%'.$search.'%')
                        ->orWhere('programming_changes.reason', 'like', '%'.$search.'%')
                        ->orWhere('programming_changes.description', 'like', '%'.$search.'%')
                        ->orWhere('programming_changes.programming_id', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            }

            if ($type = ($filters['change_type'] ?? null)) {
                $labels = [
                    'bulk' => 'Cambio masivo de',
                    'shift' => 'turno',
                    'driver' => 'conductor',
                    'helper' => 'ayudante',
                    'vehicle' => 'vehículo',
                    'rescheduling' => 'reprogramación',
                ];
                $query->where(function ($typeQuery) use ($type, $labels) {
                    $typeQuery->whereJsonContains('after->type', $type)
                        ->orWhere('action', 'like', $type === 'bulk' ? 'Cambio masivo de%' : '%'.$labels[$type].'%');
                });
            }

            return DataTables::eloquent($query)
                ->editColumn('id', fn (ProgrammingChange $change) => '#'.$change->id)
                ->addColumn('change_type', fn (ProgrammingChange $change) => $this->typeLabel($change))
                ->addColumn('performed_at', fn (ProgrammingChange $change) => $change->created_at?->format('d/m/Y H:i'))
                ->addColumn('performed_by', fn (ProgrammingChange $change) => $change->user?->name ?? 'Usuario no disponible')
                ->addColumn('programming_label', fn (ProgrammingChange $change) => $this->programmingLabel($change))
                ->addColumn('before_summary', fn (ProgrammingChange $change) => $this->summary($change->before))
                ->addColumn('after_summary', fn (ProgrammingChange $change) => $this->summary($change->after))
                ->addColumn('reason_label', fn (ProgrammingChange $change) => $change->reason ?: ($change->after['reason'] ?? '—'))
                ->addColumn('actions', fn (ProgrammingChange $change) => '<a class="btn btn-outline-primary btn-sm" href="'.e(route('cambios.show', $change)).'" title="Ver detalle" aria-label="Ver detalle del cambio"><i class="bi bi-eye" aria-hidden="true"></i></a>')
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('admin.change-management.cambios.index');
    }

    public function create() { return redirect()->route('cambios.index'); }

    public function store(Request $request) { return redirect()->route('cambios.index')->with('success', 'Cambio registrado correctamente.'); }

    public function show(ProgrammingChange $cambio)
    {
        $cambio->load(['user', 'programming', 'originProgramming', 'destinationProgramming']);
        $type = $this->typeKey($cambio);
        $typeLabel = $this->typeLabel($cambio);
        $before = $cambio->before ?? [];
        $after = $cambio->after ?? [];
        $reason = $cambio->reason ?: ($after['reason'] ?? null);
        $description = $cambio->description ?: ($after['description'] ?? null);
        $operationId = $cambio->operation_id ?: ($after['bulk_operation_id'] ?? null);

        return view('admin.change-management.cambios.show', compact('cambio', 'type', 'typeLabel', 'before', 'after', 'reason', 'description', 'operationId'));
    }

    public function edit(string $id) { return redirect()->route('cambios.index'); }

    public function update(Request $request, string $id) { return redirect()->route('cambios.index')->with('success', 'Los historiales no son editables.'); }

    public function destroy(string $id) { return redirect()->route('cambios.index')->with('error', 'Los cambios registrados forman parte del historial y no se pueden eliminar.'); }

    private function typeKey(ProgrammingChange $change): string
    {
        $type = data_get($change->after, 'type');
        if (in_array($type, ['shift', 'driver', 'helper', 'vehicle'], true)) return $type;

        $action = mb_strtolower((string) $change->action);
        if (str_contains($action, 'reprogram')) return 'rescheduling';
        if (str_contains($action, 'turno') || str_contains($action, 'shift')) return 'shift';
        if (str_contains($action, 'conductor') || str_contains($action, 'driver')) return 'driver';
        if (str_contains($action, 'ayudante') || str_contains($action, 'helper') || str_contains($action, 'ocupante')) return 'helper';
        if (str_contains($action, 'vehículo') || str_contains($action, 'vehiculo') || str_contains($action, 'vehicle')) return 'vehicle';
        if (str_contains($action, 'masivo') || str_contains($action, 'bulk')) return 'bulk';

        return 'other';
    }

    private function typeLabel(ProgrammingChange $change): string
    {
        return [
            'bulk' => 'Cambio masivo', 'shift' => 'Turno', 'driver' => 'Conductor',
            'helper' => 'Ayudante', 'vehicle' => 'Vehículo', 'rescheduling' => 'Reprogramación',
            'other' => $change->action ?: 'Cambio de programación',
        ][$this->typeKey($change)];
    }

    private function summary(?array $values): string
    {
        if (! $values) return '—';
        if (array_key_exists('value', $values) && $values['value'] !== null && $values['value'] !== '') {
            return is_array($values['value']) ? implode(', ', array_map('strval', $values['value'])) : (string) $values['value'];
        }
        foreach (['date', 'status', 'name', 'id'] as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') return (string) $values[$key];
        }
        return '—';
    }

    private function programmingLabel(ProgrammingChange $change): string
    {
        $programming = $change->programming ?: ($change->originProgramming ?: $change->destinationProgramming);
        if (! $programming) return '#'.$change->programming_id;
        return '#'.$programming->id.' · '.($programming->date?->format('d/m/Y') ?? 'Sin fecha');
    }
}
