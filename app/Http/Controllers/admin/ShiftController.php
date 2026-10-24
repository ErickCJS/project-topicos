<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return DataTables::eloquent(Shift::query())
                ->editColumn('start_time', fn (Shift $shift) => substr((string) $shift->start_time, 0, 5))
                ->editColumn('end_time', fn (Shift $shift) => substr((string) $shift->end_time, 0, 5))
                ->editColumn('created_at', fn (Shift $shift) => $shift->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn (Shift $shift) => $shift->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (Shift $shift) {
                    return '<div class="shift-actions">'
                        .'<button type="button" class="btn btn-info btn-sm shift-action-btn btnVer" data-url="'.e(route('shifts.show', $shift)).'" title="Ver" aria-label="Ver turno"><i class="bi bi-eye" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-warning btn-sm shift-action-btn btnEditar" data-url="'.e(route('shifts.edit', $shift)).'" title="Editar" aria-label="Editar turno"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-danger btn-sm shift-action-btn btnEliminar" data-url="'.e(route('shifts.destroy', $shift)).'" title="Eliminar" aria-label="Eliminar turno"><i class="bi bi-trash" aria-hidden="true"></i></button>'
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
        Shift::create($this->validatedData($request));

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
        $shift->update($this->validatedData($request, $shift));

        return response()->json([
            'message' => 'Turno actualizado correctamente.',
        ]);
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return response()->json([
            'message' => 'Turno eliminado correctamente.',
        ]);
    }

    private function validatedData(Request $request, ?Shift $shift = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('shifts', 'name')->ignore($shift?->id),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
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
        ]);
    }
}
