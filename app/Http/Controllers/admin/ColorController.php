<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Color;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::eloquent(Color::query())
                ->editColumn('code', fn (Color $color) => $this->withHexPrefix((string) $color->code))
                ->addColumn('preview', function (Color $color) {
                    $code = $this->withHexPrefix((string) $color->code);

                    return '<span class="color-swatch" title="'.e($code).'" style="background-color:'.e($code).'" aria-label="Vista previa de '.e($color->name).'"></span>';
                })
                ->addColumn('actions', function (Color $color) {
                    return '<div class="color-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm color-action-btn btnVer" data-id="'.$color->id.'" data-url="'.e(route('vehiclescolor.show', $color)).'" title="Ver" aria-label="Ver color">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm color-action-btn btnEditar" data-id="'.$color->id.'" data-url="'.e(route('vehiclescolor.edit', $color)).'" data-update-url="'.e(route('vehiclescolor.update', $color)).'" title="Editar" aria-label="Editar color">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm color-action-btn btnEliminar" data-id="'.$color->id.'" data-url="'.e(route('vehiclescolor.destroy', $color)).'" title="Eliminar" aria-label="Eliminar color">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['preview', 'actions'])
                ->toJson();
        }

        return view('admin.colors.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateColorData($request);
        $color = Color::create($data);

        return response()->json([
            'mensaje' => 'Color creado correctamente.',
            'data' => $color,
        ], 201);
    }

    public function show(string $id)
    {
        $color = Color::findOrFail($id);

        return response()->json($this->colorResponse($color));
    }

    public function edit(string $id)
    {
        $color = Color::findOrFail($id);

        return response()->json($this->colorResponse($color));
    }

    public function update(Request $request, string $id)
    {
        $color = Color::findOrFail($id);
        $data = $this->validateColorData($request, $color);
        $color->update($data);

        return response()->json([
            'mensaje' => 'Color actualizado correctamente.',
            'data' => $this->colorResponse($color->fresh()),
        ]);
    }

    public function destroy(string $id)
    {
        $color = Color::findOrFail($id);

        if ($color->vehicles()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar el color porque tiene vehículos relacionados.',
            ], 409);
        }

        $color->delete();

        return response()->json([
            'mensaje' => 'Color eliminado correctamente.',
        ]);
    }

    private function withHexPrefix(string $code): string
    {
        $code = strtoupper(trim($code));

        return $code !== '' && ! str_starts_with($code, '#') ? '#'.$code : $code;
    }

    private function validateColorData(Request $request, ?Color $color = null): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => $this->withHexPrefix((string) $request->input('code')),
            'description' => trim((string) $request->input('description')),
        ]);

        $uniqueName = Rule::unique('color', 'name');
        $uniqueCode = Rule::unique('color', 'code');
        if ($color) {
            $uniqueName->ignore($color->getKey());
            $uniqueCode->ignore($color->getKey());
        }

        return $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                'regex:/^[\pL\pM]+(?:[ .\'-][\pL\pM]+)*$/u',
                $uniqueName,
            ],
            'code' => [
                'required', 'string', 'max:7',
                'regex:/^#(?:[A-F0-9]{3}|[A-F0-9]{6})$/',
                $uniqueCode,
            ],
            'description' => ['nullable', 'string', 'max:100', 'regex:/^[^<>]*$/u'],
        ], [
            'name.required' => 'Escribe el nombre del color.',
            'name.string' => 'El nombre del color debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.regex' => 'Usa letras, espacios, puntos, apóstrofos o guiones en el nombre.',
            'name.unique' => 'Ya existe un color con ese nombre.',
            'code.required' => 'Ingresa un código hexadecimal.',
            'code.string' => 'El código hexadecimal debe ser texto.',
            'code.max' => 'El código debe tener 3 o 6 dígitos hexadecimales, además del #.',
            'code.regex' => 'Usa el formato #RGB o #RRGGBB; por ejemplo, #735D56.',
            'code.unique' => 'Ya existe un color con ese código hexadecimal.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los 100 caracteres.',
            'description.regex' => 'La descripción no puede contener etiquetas HTML.',
        ]);
    }

    private function colorResponse(Color $color): array
    {
        return [
            'id' => $color->id,
            'name' => $color->name,
            'code' => $this->withHexPrefix((string) $color->code),
            'description' => $color->description,
            'created_at' => $color->created_at?->toISOString(),
            'updated_at' => $color->updated_at?->toISOString(),
        ];
    }
}
