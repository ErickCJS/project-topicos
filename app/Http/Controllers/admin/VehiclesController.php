<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Brand;
use App\Models\admin\Brandmodel;
use App\Models\admin\Color;
use App\Models\admin\Vehicle;
use App\Models\admin\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

                    return '<img src="' . $logo . '"
                            style="width:70px; height:50px; object-fit:contain;">';
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
                ->addColumn('image', function ($vehicle) {
                    return '<button class="btn btn-secondary btn-sm btnImagen" data-id="' . $vehicle->id . '">
                        <i class="fas fa-image"></i>
                    </button>';
                })
                ->addColumn('edit', function ($vehicle) {
                    return '<button class="btn btn-primary btn-sm btnEditar" data-id="' . $vehicle->id . '">
                        <i class="fas fa-pencil-alt"></i>
                    </button>';
                })
                ->addColumn('delete', function ($vehicle) {
                    return '<button class="btn btn-danger btn-sm btnEliminar" data-id="' . $vehicle->id . '">
                        <i class="fas fa-trash"></i>
                    </button>';
                })
                ->rawColumns(['logo','image','color','edit', 'delete'])
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
        $types = Vehicle::all()->pluck('name', 'id');
        $colors = Color::all()->pluck('name', 'id');
        return view('admin.vehicles.create', compact('brands', 'models', 'types', 'colors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        $vehicle = Vehicle::find($id);
        $brands = Brand::has('models')->pluck('name', 'id');
        $brand_first = $brands->keys()->first();
        $models = Brandmodel::where('brand_id', $brand_first)->pluck('name', 'id');
        $types = Vehicle::all()->pluck('name', 'id');
        $colors = Color::all()->pluck('name', 'id');
        return view('admin.vehicles.edit', compact('vehicle','brands', 'models', 'types', 'colors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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
            

            foreach ($request->file('photos') as $photo) {
                $ruta = $photo->store('vehiclesphotos', 'public');

                $vehicle->images()->create([
                    'imagen' => $ruta,
                    'profile' => 0
                ]);
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

            if ($foto->url_photo) {
                $ruta = public_path($foto->url_photo);
                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }
            $foto->delete();

            return response()->json([
                'mensaje' => 'Imagen eliminada correctamente',
                'titulo' => 'Éxito'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'mensaje' => 'Error al eliminar la imagen' . $e->getMessage(),
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
