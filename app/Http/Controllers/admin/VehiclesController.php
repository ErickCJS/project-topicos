<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Brand;
use App\Models\admin\Brandmodel;
use App\Models\admin\Color;
use App\Models\admin\Vehicle;
use App\Models\admin\VehicleImage;
use App\Models\admin\Vehicletype;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class VehiclesController extends Controller
{
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
                        : asset('storage/'.$vehicle->logo);

                    return '<button type="button" class="vehicle-logo-preview" data-image="'.e($logo).'" data-vehicle="'.e($vehicle->name).'" title="Ver imagen de '.e($vehicle->name).'" aria-label="Ver imagen de '.e($vehicle->name).'">
                                <img src="'.e($logo).'" alt="Imagen de '.e($vehicle->name).'" loading="lazy" decoding="async">
                            </button>';
                })
                ->editColumn('color', function ($vehicle) {
                    return '<span class="vehicle-color-swatch" style="background-color:'.e($vehicle->color).'" title="'.e($vehicle->color).'" aria-label="Color '.e($vehicle->color).'"></span>';
                })
                ->addColumn('actions', function ($vehicle) {
                    return '<div class="vehicle-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm vehicle-action-btn btnVer" data-id="'.$vehicle->id.'" data-url="'.e(route('vehicles.show', $vehicle->id)).'" title="Ver" aria-label="Ver vehículo">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm vehicle-action-btn btnImagen" data-id="'.$vehicle->id.'" data-url="'.e(route('vehicles.photo.retornar_vista_imagenes', $vehicle->id)).'" title="Imágenes" aria-label="Imágenes del vehículo">
                                    <i class="bi bi-images" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm vehicle-action-btn btnEditar" data-id="'.$vehicle->id.'" data-url="'.e(route('vehicles.edit', $vehicle->id)).'" title="Editar" aria-label="Editar vehículo">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm vehicle-action-btn btnEliminar" data-id="'.$vehicle->id.'" data-url="'.e(route('vehicles.destroy', $vehicle->id)).'" title="Eliminar" aria-label="Eliminar vehículo">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['logo', 'color', 'actions'])
                ->toJson();
        }

        return view('admin.vehicles.index');
    }

    public function create()
    {
        $brands = Brand::has('models')->orderBy('name')->pluck('name', 'id');
        $brandFirst = $brands->keys()->first();
        $models = Brandmodel::where('brand_id', $brandFirst)->pluck('name', 'id');
        $types = Vehicletype::orderBy('name')->pluck('name', 'id');
        $colors = Color::orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.vehicles.create', compact('brands', 'models', 'types', 'colors'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        Vehicle::create($validated);

        return response()->json(['mensaje' => 'Vehículo registrado correctamente'], 201);
    }

    public function show(string $id)
    {
        $vehicle = Vehicle::with(['brand', 'model', 'color', 'type', 'images'])
            ->findOrFail($id);
        $profileImage = $vehicle->images->firstWhere('profile', 1) ?? $vehicle->images->first();

        return view('admin.vehicles.show', compact('vehicle', 'profileImage'));
    }

    public function edit(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $brands = Brand::has('models')->orderBy('name')->pluck('name', 'id');
        $models = Brandmodel::where('brand_id', $vehicle->brand_id)->pluck('name', 'id');
        $types = Vehicletype::orderBy('name')->pluck('name', 'id');
        $colors = Color::orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.vehicles.edit', compact('vehicle', 'brands', 'models', 'types', 'colors'));
    }

    public function update(Request $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update($this->validatedData($request, $vehicle));

        return response()->json(['mensaje' => 'Vehículo actualizado correctamente']);
    }

    public function destroy(string $id)
    {
        $imagePaths = DB::transaction(function () use ($id) {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($id);
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

    public function retonar_models(int $brand_id)
    {
        $models = Brandmodel::where('brand_id', $brand_id)->pluck('name', 'id');

        return response()->json($models);
    }

    public function retonar_rutas_fotos(int $vehicle_id)
    {
        return VehicleImage::where('vehicle_id', $vehicle_id)->orderBy('id')->get();
    }

    public function retornar_vista_imagenes(int $vehicle_id)
    {
        Vehicle::findOrFail($vehicle_id);
        $photos = $this->retonar_rutas_fotos($vehicle_id);

        return view('admin.vehicles.template.imagenes', compact('vehicle_id', 'photos'));
    }

    public function subirimagenes(Request $request, int $vehicle_id)
    {
        $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,png,jpg,webp'],
        ]);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $vehicle_id, &$storedPaths) {
                $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicle_id);
                $hasProfile = $vehicle->images()->where('profile', 1)->exists();

                foreach ($request->file('photos', []) as $photo) {
                    $path = $photo->store('vehiclesphotos', 'public');
                    $storedPaths[] = $path;

                    $vehicle->images()->create([
                        'imagen' => $path,
                        'profile' => $hasProfile ? 0 : 1,
                    ]);
                    $hasProfile = true;
                }
            });

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Imágenes subidas correctamente',
            ]);
        } catch (ModelNotFoundException $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            report($exception);

            return response()->json([
                'codigo' => 0,
                'mensaje' => 'No fue posible subir las imágenes.',
            ], 500);
        }
    }

    public function eliminar_imagen(int $photo_id)
    {
        try {
            $imagePath = DB::transaction(function () use ($photo_id) {
                $currentPhoto = VehicleImage::findOrFail($photo_id);
                Vehicle::query()->lockForUpdate()->findOrFail($currentPhoto->vehicle_id);
                $photo = VehicleImage::query()->lockForUpdate()->findOrFail($photo_id);
                $vehicleId = $photo->vehicle_id;
                $wasProfile = (bool) $photo->profile;
                $path = $photo->imagen;

                $photo->delete();

                if ($wasProfile) {
                    VehicleImage::where('vehicle_id', $vehicleId)->update(['profile' => 0]);
                    $nextPhoto = VehicleImage::where('vehicle_id', $vehicleId)
                        ->orderBy('id')
                        ->first();
                    $nextPhoto?->update(['profile' => 1]);
                }

                return $path;
            });

            if (filled($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'mensaje' => 'Imagen eliminada correctamente',
                'titulo' => 'Éxito',
            ]);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'mensaje' => 'No fue posible eliminar la imagen.',
                'titulo' => 'Error',
            ], 500);
        }
    }

    public function seleccionar_foto_principal(int $id_foto)
    {
        try {
            DB::transaction(function () use ($id_foto) {
                $currentPhoto = VehicleImage::findOrFail($id_foto);
                Vehicle::query()->lockForUpdate()->findOrFail($currentPhoto->vehicle_id);
                $photo = VehicleImage::query()->lockForUpdate()->findOrFail($id_foto);

                VehicleImage::where('vehicle_id', $photo->vehicle_id)->update(['profile' => 0]);
                VehicleImage::whereKey($photo->id)->update(['profile' => 1]);
            });

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Imagen principal actualizada correctamente',
            ]);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'codigo' => 0,
                'mensaje' => 'No fue posible seleccionar la imagen principal.',
            ], 500);
        }
    }

    private function validatedData(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('vehicles', 'code')->ignore($vehicle)],
            'name' => ['required', 'string', 'max:100'],
            'plate' => ['required', 'string', 'max:10', Rule::unique('vehicles', 'plate')->ignore($vehicle)],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
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
}
