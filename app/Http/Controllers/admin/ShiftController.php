<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Programming;
use App\Models\admin\Shift;
use App\Models\admin\StaffGroup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return DataTables::eloquent(Shift::query())
                ->editColumn('description', fn (Shift $shift) => $shift->description ?: '—')
                ->editColumn('start_time', fn (Shift $shift) => substr((string) $shift->start_time, 0, 5))
                ->editColumn('end_time', fn (Shift $shift) => substr((string) $shift->end_time, 0, 5))
                ->editColumn('created_at', fn (Shift $shift) => $shift->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn (Shift $shift) => $shift->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (Shift $shift) {
                    return '<div class="shift-actions">'
                        .'<button type="button" class="btn btn-outline-primary btn-sm shift-action-btn btnVer" data-url="'.e(route('shifts.show', $shift)).'" title="Ver detalle" aria-label="Ver turno"><i class="bi bi-eye" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-outline-warning btn-sm shift-action-btn btnEditar" data-url="'.e(route('shifts.edit', $shift)).'" title="Editar" aria-label="Editar turno"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-outline-danger btn-sm shift-action-btn btnEliminar" data-url="'.e(route('shifts.destroy', $shift)).'" title="Eliminar" aria-label="Eliminar turno"><i class="bi bi-trash" aria-hidden="true"></i></button>'
                        .'</div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.shifts.index');
    }

    public function create()
    {
        return view('admin.shifts.create');
    }

    public function store(Request $request)
    {
        DB::transaction(fn () => Shift::create($this->validatedData($request)));

        return response()->json([
            'message' => 'Turno registrado correctamente.',
        ], 201);
    }

    public function show(Shift $shift)
    {
        return view('admin.shifts.show', compact('shift'));
    }

    public function edit(Shift $shift)
    {
        return view('admin.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        DB::transaction(fn () => $shift->update($this->validatedData($request, $shift)));

        return response()->json([
            'message' => 'Turno actualizado correctamente.',
        ]);
    }

    public function destroy(Shift $shift)
    {
        try {
            DB::transaction(function () use ($shift) {
                $locked = Shift::whereKey($shift->id)->lockForUpdate()->firstOrFail();
                if ($locked->attendances()->exists() || StaffGroup::where('shift_id', $shift->id)->exists()
                    || Programming::withTrashed()->where('shift_id', $shift->id)->exists()) {
                    throw ValidationException::withMessages(['shift' => 'Este turno está utilizado en asistencias, grupos de personal o recorridos y no se puede eliminar.']);
                }
                $locked->delete();
            });
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23503'], true)) {
                throw $exception;
            }
            throw ValidationException::withMessages(['shift' => 'Este turno tiene registros relacionados y no se puede eliminar.']);
        }

        return response()->json([
            'message' => 'Turno eliminado correctamente.',
        ]);
    }

    private function validatedData(Request $request, ?Shift $shift = null): array
    {
        $existingShifts = Shift::orderBy('id')->lockForUpdate()->get();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('shifts', 'name')->ignore($shift?->id),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'El nombre del turno es obligatorio.',
            'name.string' => 'El nombre del turno debe ser texto.',
            'name.max' => 'El nombre del turno no debe superar los 50 caracteres.',
            'name.unique' => 'Ya existe un turno con ese nombre.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:mm.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:mm.',
            'end_time.different' => 'La hora de fin debe ser diferente a la hora de inicio.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no debe superar los 1000 caracteres.',
        ]);

        $requestedIntervals = $this->timeIntervals($data['start_time'], $data['end_time']);
        foreach ($existingShifts as $existing) {
            if ($existing->id === $shift?->id) {
                continue;
            }
            foreach ($requestedIntervals as [$start, $end]) {
                foreach ($this->timeIntervals($existing->start_time, $existing->end_time) as [$otherStart, $otherEnd]) {
                    if ($start < $otherEnd && $otherStart < $end) {
                        throw ValidationException::withMessages([
                            'start_time' => 'El horario se cruza con el turno '.$existing->name.' ('
                                .substr($existing->start_time, 0, 5).' - '.substr($existing->end_time, 0, 5).').',
                        ]);
                    }
                }
            }
        }

        return $data;
    }

    private function timeIntervals(string $startTime, string $endTime): array
    {
        $seconds = static function (string $time): int {
            $parts = array_map('intval', explode(':', $time));

            return $parts[0] * 3600 + $parts[1] * 60 + ($parts[2] ?? 0);
        };
        $start = $seconds($startTime);
        $end = $seconds($endTime);

        // Overnight shifts occupy both the end and the beginning of the day.
        return $start < $end ? [[$start, $end]] : [[$start, 86400], [0, $end]];
    }
}
