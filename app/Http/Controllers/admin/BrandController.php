<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::eloquent(Brand::query())
                ->addColumn('logo', function (Brand $brand) {
                    $logo = $this->logoUrl($brand);

                    return '<button type="button" class="brand-logo-preview" data-image="'.e($logo).'" data-brand="'.e($brand->name).'" title="Ver logo de '.e($brand->name).'" aria-label="Ver logo de '.e($brand->name).'">
                                <img src="'.e($logo).'" alt="Logo de '.e($brand->name).'" loading="lazy" decoding="async">
                            </button>';
                })
                ->editColumn('created_at', fn (Brand $brand) => $brand->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn (Brand $brand) => $brand->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (Brand $brand) {
                    return '<div class="brand-actions">
                                <button type="button" class="btn btn-info btn-sm brand-action-btn btnVer" data-id="'.$brand->id.'" data-url="'.e(route('brands.show', $brand)).'" title="Ver" aria-label="Ver marca">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-warning btn-sm brand-action-btn btnEditar" data-id="'.$brand->id.'" data-url="'.e(route('brands.edit', $brand)).'" title="Editar" aria-label="Editar marca">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm brand-action-btn btnEliminar" data-id="'.$brand->id.'" data-url="'.e(route('brands.destroy', $brand)).'" title="Eliminar" aria-label="Eliminar marca">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['logo', 'actions'])
                ->toJson();
        }

        return view('admin.brands.index');
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        try {
            $logo = null;

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('brand_logo', 'public');
                $logo = Storage::url($path);
            }

            $brand = Brand::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'logo' => $logo,
            ]);

            return response()->json([
                'mensaje' => 'Marca registrada correctamente.',
                'data' => ['id' => $brand->id],
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'mensaje' => 'No fue posible registrar la marca.',
            ], 500);
        }
    }

    public function show(Brand $brand)
    {
        return view('admin.brands.show', [
            'brand' => $brand,
            'logoUrl' => $this->logoUrl($brand),
        ]);
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validatedData($request, $brand);

        try {
            $logo = $brand->logo;

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('brand_logo', 'public');
                $logo = Storage::url($path);
            }

            $brand->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'logo' => $logo,
            ]);

            return response()->json([
                'mensaje' => 'Marca actualizada correctamente.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'mensaje' => 'No fue posible actualizar la marca.',
            ], 500);
        }
    }

    public function destroy(Brand $brand)
    {
        try {
            if ($brand->models()->exists() || $brand->vehicles()->exists()) {
                return response()->json([
                    'mensaje' => 'No se puede eliminar esta marca porque tiene relación con otras tablas.',
                ], 409);
            }

            $brand->delete();

            return response()->json([
                'mensaje' => 'Marca eliminada correctamente.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'mensaje' => 'No fue posible eliminar la marca.',
            ], 500);
        }
    }

    private function validatedData(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('brands', 'name')->ignore($brand),
            ],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image'],
        ], [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.string' => 'El nombre de la marca debe ser texto.',
            'name.max' => 'El nombre de la marca no puede tener más de 100 caracteres.',
            'name.unique' => 'Esta marca ya se encuentra registrada.',
            'description.string' => 'La descripción debe ser texto.',
            'logo.image' => 'El logo debe ser una imagen válida.',
        ]);
    }

    private function logoUrl(Brand $brand): string
    {
        return $brand->logo
            ? asset($brand->logo)
            : asset('storage/images/no_logo.png');
    }
}
