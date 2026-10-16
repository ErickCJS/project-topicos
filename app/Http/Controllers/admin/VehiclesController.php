<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Brand;
use App\Models\admin\Brandmodel;
use App\Models\admin\Color;
use App\Models\admin\Vehicle;
use App\Models\admin\VehicleImage;
use App\Models\admin\Vehicletype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class VehiclesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $vehicles = Vehicle::query()
                ->join('brands', 'vehicles.brand_id', '=', 'brands.id')
                ->join('brandmodels', 'vehicles.model_id', '=', 'brandmodels.id')
                ->join('color', 'vehicles.color_id', '=', 'color.id')
                ->join('vehicletypes', 'vehicles.type_id', '=', 'vehicletypes.id')
                ->leftJoin('vehicle_images', function ($join) {
                    $join->on('vehicle_images.vehicle_id', '=', 'vehicles.id')
                        ->where('vehicle_images.profile', '=', 1);
                })
                ->select([
                    'vehicle_images.imagen as logo',
                    'vehicles.id',
                    'vehicles.code',
                    'vehicles.name',
                    'vehicles.plate',
                    'vehicles.year',
                    'vehicles.load_capacity',
                    'vehicles.created_at',
                    'vehicles.updated_at',
                    'brands.name as brand',
                    'brandmodels.name as model',
                    'color.code as color',
                    'vehicletypes.name as type',
                ]);
            return DataTables::of($vehicles)
                ->addColumn('logo', function ($vehicle) {
                    $logo = empty($vehicle->logo)
                        ? asset('storage/images/no_logo.png')
                        : asset('storage/' . $vehicle->logo);

                    return '<button type="button" class="vehicle-logo-preview" data-image="' . e($logo) . '" data-vehicle="' . e($vehicle->name) . '" title="Ver imagen de ' . e($vehicle->name) . '" aria-label="Ver imagen de ' . e($vehicle->name) . '">
                                <img src="' . e($logo) . '" alt="Imagen de ' . e($vehicle->name) . '">
                            </button>';
                    })
                ->addColumn('color', function ($vehicle) {
                    return '<div style="
                        width: 30px;
                        height: 30px;
                        background-color: ' . e($vehicle->color) . ';
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        display: inline-block;
                    "></div>';
                })
                ->addColumn('actions', function ($vehicle) {
                    return '<div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary btn-sm btnImagen" data-id="' . $vehicle->id . '" title="Imágenes" aria-label="Imágenes del vehículo">
                            <i class="fas fa-image"></i>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm btnEditar" data-id="' . $vehicle->id . '" title="Editar" aria-label="Editar vehículo">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btnEliminar" data-id="' . $vehicle->id . '" title="Eliminar" aria-label="Eliminar vehículo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['logo', 'color', 'actions'])
                ->make(true);
        }

        return view('admin.vehicles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $brands = Brand::has('models')->pluck('name', 'id');
        $brand_first = $brands->keys()->first();
        $models = Brandmodel::where('brand_id', $brand_first)->pluck('name', 'id');
        $types = Vehicletype::orderBy('name')->pluck('name', 'id');
        $colors = Color::orderBy('name')->pluck('name', 'id');
        return view('admin.vehicles.create', compact('brands', 'models', 'types', 'colors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:vehicles,code'],
            'name' => ['required', 'string', 'max:100'],
            'plate' => ['required', 'string', 'max:10', 'unique:vehicles,plate'],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'load_capacity' => ['required', 'integer', 'min:0'],
            'occupats_capacity' => ['required', 'integer', 'min:0'],
            'fuel_capacity' => ['required', 'integer', 'min:0'],
            'compact_capacity' => ['required', 'integer', 'min:0'],
            'type_id' => ['required', 'exists:vehicletypes,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'model_id' => [
                'required',
                Rule::exists('brandmodels', 'id')->where('brand_id', $request->input('brand_id')),
            ],
            'color_id' => ['required', 'exists:color,id'],
            'description' => ['nullable', 'string'],
        ], $this->validationMessages());

        Vehicle::create($validated);

        return response()->json(['mensaje' => 'Vehículo registrado correctamente'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $brands = Brand::has('models')->pluck('name', 'id');
        $models = Brandmodel::where('brand_id', $vehicle->brand_id)->pluck('name', 'id');
        $types = Vehicletype::orderBy('name')->pluck('name', 'id');
        $colors = Color::orderBy('name')->pluck('name', 'id');
        return view('admin.vehicles.edit', compact('vehicle','brands', 'models', 'types', 'colors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('vehicles', 'code')->ignore($vehicle->id)],
            'name' => ['required', 'string', 'max:100'],
            'plate' => ['required', 'string', 'max:10', Rule::unique('vehicles', 'plate')->ignore($vehicle->id)],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'load_capacity' => ['required', 'integer', 'min:0'],
            'occupats_capacity' => ['required', 'integer', 'min:0'],
            'fuel_capacity' => ['required', 'integer', 'min:0'],
            'compact_capacity' => ['required', 'integer', 'min:0'],
            'type_id' => ['required', 'exists:vehicletypes,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'model_id' => [
                'required',
                Rule::exists('brandmodels', 'id')->where('brand_id', $request->input('brand_id')),
            ],
            'color_id' => ['required', 'exists:color,id'],
            'description' => ['nullable', 'string'],
        ], $this->validationMessages());

        $vehicle->update($validated);

        return response()->json(['mensaje' => 'Vehículo actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $imagePaths = DB::transaction(function () use ($id) {
            $vehicle = Vehicle::findOrFail($id);
            $imagePaths = $vehicle->images()
                ->pluck('imagen')
                ->filter(fn ($path) => filled($path))
                ->all();

            $vehicle->images()->delete();
            $vehicle->delete();

            return $imagePaths;
        });

        if ($imagePaths) {
            Storage::disk('public')->delete($imagePaths);
        }

        return response()->json(['mensaje' => 'Vehículo y todas sus imágenes eliminados correctamente']);
    }

    private function validationMessages(): array
    {
        return [
            'code.required' => 'El código del vehículo es obligatorio.',
            'code.unique' => 'Ya existe un vehículo con ese código.',
            'name.required' => 'El nombre del vehículo es obligatorio.',
            'plate.required' => 'La placa del vehículo es obligatoria.',
            'plate.unique' => 'Ya existe un vehículo con esa placa.',
            'year.required' => 'El año del vehículo es obligatorio.',
            'year.max' => 'El año no puede ser posterior al próximo año.',
            'load_capacity.required' => 'La capacidad de carga es obligatoria.',
            'occupats_capacity.required' => 'La capacidad de personas es obligatoria.',
            'fuel_capacity.required' => 'La capacidad de combustible es obligatoria.',
            'compact_capacity.required' => 'La capacidad de compactación es obligatoria.',
            'type_id.required' => 'Selecciona el tipo de vehículo.',
            'type_id.exists' => 'El tipo de vehículo seleccionado no existe.',
            'brand_id.required' => 'Selecciona la marca del vehículo.',
            'brand_id.exists' => 'La marca seleccionada no existe.',
            'model_id.required' => 'Selecciona el modelo del vehículo.',
            'model_id.exists' => 'El modelo no pertenece a la marca seleccionada.',
            'color_id.required' => 'Selecciona el color del vehículo.',
            'color_id.exists' => 'El color seleccionado no existe.',
        ];
    }

    public function retonar_models(int $brand_id)
    {
        $models = Brandmodel::where('brand_id', $brand_id)->pluck('name', 'id');
        return response()->json($models);
    }

    public function retonar_rutas_fotos(int $vehicle_id)
    {
        return VehicleImage::where('vehicle_id', $vehicle_id)->get();
    }

    public function retornar_vista_imagenes(int $vehicle_id)
    {
        $photos = $this->retonar_rutas_fotos($vehicle_id);

        return view('admin.vehicles.template.imagenes', compact('vehicle_id', 'photos'));
    }



    public function subirimagenes(Request $request, int $vehicle_id)
    {
        try {
            //validamos que todas las fotos que suban sea un arraya
            //validamos que cada archivo cumpa con un tipo de imagen
            $request->validate([
                'photos' => 'required|array',
                'photos.*' => 'image|mimes:jpeg,png,jpg,webp'
            ]);

            $vehicle = Vehicle::findOrFail($vehicle_id);
            $profile = $vehicle->images()->where('profile', 1)->exists();

            foreach ($request->file('photos') as $photo) {
                $ruta = $photo->store('vehiclesphotos', 'public');

                $vehicle->images()->create([
                    'imagen' => $ruta,
                    'profile' => !$profile
                ]);
                $profile = true;
            }

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Imágenes subidas correctamente'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al subir las imágenes' . $e,
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function eliminar_imagen(int $photo_id)
    {
        try {
            $foto = VehicleImage::find($photo_id);

            if (!$foto) {
                return response()->json([
                    'mensaje' => 'La imagen no existe',
                    'titulo' => 'Error'
                ], 404);
            }

            $id_vehiculo = $foto->vehicle_id;
            $era_perfil = $foto->profile == 1;
            $ruta_imagen = $foto->url_photo
                ? public_path($foto->url_photo)
                : null;

            if ($ruta_imagen && file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }

            $foto->delete();

            if ($era_perfil) {
                $nueva_imagen = VehicleImage::where('vehicle_id', $id_vehiculo)
                    ->orderBy('id')
                    ->first();

                if ($nueva_imagen) {
                    $nueva_imagen->update([
                        'profile' => 1
                    ]);
                }
            }

            return response()->json([
                'mensaje' => 'Imagen eliminada correctamente',
                'titulo' => 'Éxito'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'mensaje' => 'Error al eliminar la imagen: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function seleccionar_foto_principal(int $id_foto)
    {
        try {
            $foto = VehicleImage::findOrFail($id_foto);
            //de todas las fotos de ese producto actualizamos a 0
            VehicleImage::where('vehicle_id', $foto->vehicle_id)->update(['profile' => 0]);
            // le asignamos el estado de perfil
            $foto->update(['profile' => 1]);

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Imagen principal actualizada correctamente'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al seleccionar la imagen principal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
