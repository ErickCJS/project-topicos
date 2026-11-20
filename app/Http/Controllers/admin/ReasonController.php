<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

use App\Models\admin\Reason;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ReasonController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::eloquent(Reason::query())
                ->editColumn('name', fn(Reason $reason) => ucfirst($reason->name))
                ->editColumn('description', fn(Reason $reason) => $reason->description ?: '—')
                ->editColumn('created_at', fn(Reason $reason) => $reason->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn(Reason $reason) => $reason->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (Reason $reason) {
                    return '<div class="reason-actions d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-outline-warning btn-sm reason-action-btn btnEditar" data-id="' . $reason->id . '" data-url="' . e(route('reason.edit', ['reason' => $reason->id])) . '" title="Editar motivo" aria-label="Editar motivo">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm reason-action-btn btnEliminar" data-id="' . $reason->id . '" data-url="' . e(route('reason.destroy', ['reason' => $reason->id])) . '" title="Eliminar motivo" aria-label="Eliminar motivo">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.reason.index');
    }

    public function create()
    {
        return view('admin.reason.form', [
            'motivo' => null,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $motivo = Reason::create($data);

        return response()->json([
            'message' => 'Motivo registrado correctamente.',
            'data' => $motivo,
        ], 201);
    }

    public function show($reason)
    {
        $motivo = Reason::findOrFail($reason);

        return view('admin.reason.show', compact('motivo'));
    }

    public function edit($reason)
    {
        $motivo = Reason::findOrFail($reason);

        return view('admin.reason.form', [
            'motivo' => $motivo,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, $reason)
    {
        $motivo = Reason::findOrFail($reason);
        $data = $this->validatedData($request, $motivo);
        $motivo->update($data);

        return response()->json([
            'message' => 'Motivo actualizado correctamente.',
            'data' => $motivo->fresh(),
        ]);
    }

    public function destroy($reason)
    {
        $motivo = Reason::findOrFail($reason);
        $motivo->delete();

        return response()->json([
            'message' => 'Motivo eliminado correctamente.',
        ]);
    }

    private function validatedData(Request $request, ?Reason $motivo = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('reason', 'name')->ignore($motivo?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'El nombre del motivo es obligatorio.',
            'name.string' => 'El nombre del motivo debe ser texto.',
            'name.max' => 'El nombre del motivo no debe superar los 100 caracteres.',
            'name.unique' => 'Este motivo ya existe.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no debe superar los 1000 caracteres.',
        ]);
    }
}
