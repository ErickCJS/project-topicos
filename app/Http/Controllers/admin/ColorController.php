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

                    return '<span class="color-swatch" title="'.e($code).'" style="background-color:'.e($code).'"></span>';
                })
                ->addColumn('actions', fn (Color $color) => '<div class="color-actions"><button type="button" class="btn btn-sm btn-warning btnEditar" data-id="'.$color->id.'" aria-label="Editar '.e($color->name).'" title="Editar"><i class="bi bi-pencil-square"></i></button><button type="button" class="btn btn-sm btn-danger btnEliminar" data-id="'.$color->id.'" aria-label="Eliminar '.e($color->name).'" title="Eliminar"><i class="bi bi-trash"></i></button></div>')
                ->rawColumns(['preview', 'actions'])
                ->toJson();
        }

        return view('admin.colors.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateColorData($request);
        $color = Color::create($data);

        return response()->json(['mensaje' => 'Color creado correctamente.', 'data' => $color], 201);
    }

    public function edit(string $id)
    {
        $color = Color::findOrFail($id);

        return response()->json($color);
    }

    public function update(Request $request, string $id)
    {
        $color = Color::findOrFail($id);
        $data = $this->validateColorData($request, $color);
        $color->update($data);

        return response()->json(['mensaje' => 'Color actualizado correctamente.', 'data' => $color->fresh()]);
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
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.regex' => 'Usa letras, espacios, puntos, apóstrofos o guiones en el nombre.',
            'name.unique' => 'Ya existe un color con ese nombre.',
            'code.required' => 'Ingresa un código hexadecimal.',
            'code.max' => 'El código debe tener 3 o 6 dígitos hexadecimales, además del #.',
            'code.regex' => 'Usa el formato #RGB o #RRGGBB; por ejemplo, #735D56.',
            'code.unique' => 'Ya existe un color con ese código hexadecimal.',
            'description.max' => 'La descripción no puede superar los 100 caracteres.',
            'description.regex' => 'La descripción no puede contener etiquetas HTML.',
        ]);
    }

    public function destroy(string $id)
    {
        $color = Color::findOrFail($id);
        $color->delete();

        return response()->json(['mensaje' => 'Color eliminado correctamente.']);
    }
}
