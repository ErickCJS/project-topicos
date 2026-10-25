<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Holiday;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon as Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class HolidaysController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = now()->year;

        if ($request->ajax()) {

            $holidays = Holiday::query();

            if ($request->filled('fechaInicio')) {
                $holidays->whereDate('date', '>=', $request->fechaInicio);
            }

            if ($request->filled('fechaFin')) {
                $holidays->whereDate('date', '<=', $request->fechaFin);
            }

            if ($request->estado !== null && $request->estado !== '2') {
                $holidays->where('state', $request->estado);
            }

            $holidays->orderBy('date');

            return DataTables::eloquent($holidays)
                ->editColumn('date', function ($holiday) {
                    return $holiday->date?->format('d/m/Y');
                })
                ->editColumn('created_at', function ( $holiday) {
                    return $holiday->created_at?->format('d/m/Y H:i');
                })
                ->addColumn('state', function ( $holiday) {
                    if($holiday->state){
                        return '<span class="badge bg-success">Activo</span>';
                    }else{
                        return '<span class="badge bg-secondary">Inactivo</span>';
                    }
                })
                ->addColumn('day', function ( $holiday) {
                    return ucfirst(
                        Carbon::parse($holiday->date)
                            ->locale('es')
                            ->isoFormat('dddd')
                        );
                    })


                ->addColumn('actions', function ($holiday) {
                    $actions = '<div class="holidays-actions">';
                    // Editar solo si el feriado es desde mañana
                    if ($holiday->date->greaterThanOrEqualTo(today())) {
                        $actions .= '<button type="button"
                                        class="btn btn-outline-warning btn-sm holidays-action-btn btnEditar me-2"
                                        data-id="' . $holiday->id . '"
                                        data-url="' . e(route('holidays.edit', $holiday)) . '"
                                        title="Editar"
                                        aria-label="Editar feriado">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </button>';
                            }

                        // Eliminar permanece disponible
                        $actions .= '<button type="button"
                                        class="btn btn-outline-danger btn-sm holidays-action-btn btnEliminar"
                                        data-id="' . $holiday->id . '"
                                        data-url="' . e(route('holidays.destroy', $holiday)) . '"
                                        title="Eliminar"
                                        aria-label="Eliminar feriado">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>';

                        $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['day','state','actions'])
                ->toJson();
        }
        $feriados = $this->obtenerResumenFeriados();
        return view('admin.holidays.index', compact('year', 'feriados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $no_mostrar = true;
        return view('admin.holidays.create', compact('no_mostrar'));
    }

    /**
     * Store a newly created resource in storage.
     */

    private function validarFeriado(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('holidays', 'name')
                    ->where(function ($query) use ($request) {
                        $query->whereYear('date', Carbon::parse($request->date)->year);
                    })
                    ->ignore($id),
            ],
            'description' => ['nullable', 'string'],
            'state' => ['sometimes', 'required', 'boolean'],
        ], [
            'date.required' => 'La fecha del feriado es obligatoria.',
            'date.date_format' => 'Ingresa una fecha válida.',
            'date.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'name.required' => 'El nombre del feriado es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'name.unique' => 'Ya existe un feriado con ese nombre en el mismo año.',
            'state.boolean' => 'El estado seleccionado no es válido.',
        ]);
    }
    public function store(Request $request)
    {
        $validated = $this->validarFeriado($request);

        try {

            $holiday = Holiday::create($validated);

            return response()->json([
                'mensaje' => 'Feriado registrado correctamente.',
                'data' => $holiday,
            ], 201);
         
        } catch (\Throwable $e) {

            return response()->json([
                'mensaje' => 'No se pudo registrar el feriado.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $holiday = Holiday::find($id);
        return view('admin.holidays.edit', compact('holiday'));
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, string $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $this->validarFeriado($request, (int)   $id);

        try {
            $holiday->update($validated);
            return response()->json([
                'mensaje' => 'Feriado actualizado correctamente.',
                'data' => $holiday,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'mensaje' => 'No se pudo actualizar el feriado.',
            ], 500);
        }
    }



    public function obtenerResumenFeriados(): array
    {
        $anioActual = now()->year;

        return [
            'totalFeriados' => Holiday::whereYear('date', $anioActual)
                ->count(),

            'feriadosActivos' => Holiday::whereYear('date', $anioActual)
                ->where('state', true)
                ->count(),

            'proximosFeriados' => Holiday::whereYear('date', $anioActual)
                ->where('state', true)
                ->whereDate('date', '>=', today())
                ->count(),
        ];
    }

    public function resumenFeriados()
    {
        return response()->json($this->obtenerResumenFeriados());
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            $holiday->delete();

            return response()->json([
                'mensaje' => 'Se eliminó correctamente este feriado.',
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'mensaje' => 'No se pudo eliminar el feriado.',
            ], 500);
        }
    }
    //cargas de feriados 
    public function cargaFeriado()
    {
        try {
            $anio = now()->year;
            $apiKey = config('services.feriados.key');
            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'La API key no está configurada.'
                ], 500);
            }
            $response = Http::timeout(10)
                ->withToken($apiKey)
                ->get(
                    config('services.feriados.base_url')
                        . "/PE/holidays/{$anio}"
                );

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Error al consultar la API de feriados.',
                    'status' => $response->status(),
                    'detalle' => $response->json('error.message')
                        ?? 'La API devolvió un error.'
                ], 502);
            }

            $feriados = $response->json('data');

            if (!is_array($feriados)) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'La respuesta de la API no contiene una lista válida.'
                ], 502);
            }

            //casteamos
            $feriados = collect($feriados);

            //guardamos feriados
            $feriados_base = Holiday::whereYear('date', $anio)->get();
            $creados = 0;
            foreach ($feriados as $feriado){
                $existe = $feriados_base->contains(function ($base) use ($feriado) {
                    return $base->name === $feriado['name']
                        || $base->date->format('Y-m-d') === $feriado['date'];
                });

                if ($existe) {
                    continue;
                }
                Holiday::create([
                    'name' => $feriado['name'],
                    'date' => $feriado['date'],
                    'description' => null,
                    'state' => true,
                ]);
                $creados++;

            }
            return response()->json([
                'success' => $creados > 0,
                'mensaje' => $creados > 0
                    ? "Se insertaron {$creados} feriados correctamente."
                    : 'No se crearon feriados nuevos porque ya estaban registrados.',
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se pudo conectar con la API de feriados.'
            ], 502);
        }
    }
    

}
