<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use App\Models\admin\Programming;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverLocationController extends Controller
{
    private const TIMEZONE = 'America/Lima';

    // El GPS puede oscilar varios metros aunque el vehículo permanezca quieto.
    // Se exige un desplazamiento real de 5 m desde el último punto ACEPTADO.
    private const DUPLICATE_RADIUS_METERS = 5.0;

    /**
     * Devuelve el recorrido histórico completo de una programación del conductor.
     * Este endpoint también permite consultar recorridos finalizados.
     */
    public function index(Request $request, int $programmingId): JsonResponse
    {
        $employee = $request->user();

        if (
            !$employee instanceof Employee ||
            (int) $employee->employee_type_id !== 1
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes autorización para consultar ubicaciones.',
            ], 403);
        }

        $programming = Programming::query()
            ->where('driver_id', $employee->id)
            ->whereKey($programmingId)
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->first();

        if (!$programming) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la programación solicitada.',
            ], 404);
        }

        $locations = DB::table('programming_locations')
            ->where('programming_id', $programming->id)
            ->where('employee_id', $employee->id)
            ->orderBy('id')
            ->get([
                'id',
                'latitude',
                'longitude',
                'accuracy',
                'recorded_at',
            ])
            ->map(fn ($location) => [
                'id' => (int) $location->id,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'accuracy' => $location->accuracy === null
                    ? null
                    : (float) $location->accuracy,
                'recorded_at' => CarbonImmutable::parse($location->recorded_at, 'UTC')
                    ->timezone(self::TIMEZONE)
                    ->toIso8601String(),
            ])
            ->values()
            // Purga también las posiciones antiguas repetidas para que la API
            // histórica nunca vuelva a entregar tramos con puntos casi iguales.
            ->reduce(function ($accepted, $location) {
                $last = $accepted->last();
                if ($last && $this->distanceInMeters(
                    $last['latitude'],
                    $last['longitude'],
                    $location['latitude'],
                    $location['longitude']
                ) <= self::DUPLICATE_RADIUS_METERS) {
                    return $accepted;
                }

                $accepted->push($location);
                return $accepted;
            }, collect())
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'programming_id' => $programming->id,
                'route_started' => $programming->started_at !== null,
                'route_finished' => $programming->finished_at !== null
                    || $programming->status === Programming::STATUS_FINISHED,
                'total_points' => $locations->count(),
                'locations' => $locations,
            ],
        ]);
    }

    /**
     * Guarda una posición GPS de un recorrido abierto.
     * Los puntos consecutivos dentro de un radio de 5 m se reciben, pero no se duplican en BD.
     */
    public function store(Request $request, int $programmingId): JsonResponse
    {
        $employee = $request->user();

        if (
            !$employee instanceof Employee ||
            (int) $employee->employee_type_id !== 1
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes autorización para registrar ubicaciones.',
            ], 403);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        return DB::transaction(function () use ($employee, $programmingId, $validated) {
            // Bloquear la programación serializa los envíos simultáneos del mismo recorrido.
            $programming = Programming::query()
                ->where('driver_id', $employee->id)
                ->whereKey($programmingId)
                ->lockForUpdate()
                ->first();

            if (!$programming || $programming->status === Programming::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la programación solicitada.',
                ], 404);
            }

            if (
                $programming->started_at === null ||
                $programming->finished_at !== null ||
                $programming->status === Programming::STATUS_FINISHED
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes enviar ubicaciones mientras el recorrido esté iniciado y no haya finalizado.',
                ], 409);
            }

            $latitude = (float) $validated['latitude'];
            $longitude = (float) $validated['longitude'];

            $lastLocation = DB::table('programming_locations')
                ->where('programming_id', $programming->id)
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $distanceFromLast = null;

            if ($lastLocation) {
                $distanceFromLast = $this->distanceInMeters(
                    (float) $lastLocation->latitude,
                    (float) $lastLocation->longitude,
                    $latitude,
                    $longitude
                );

                if ($distanceFromLast <= self::DUPLICATE_RADIUS_METERS) {
                    return response()->json([
                        'success' => true,
                        'stored' => false,
                        'duplicate' => true,
                        'message' => 'Ubicación repetida. No se guardó un punto duplicado.',
                        'location_id' => $lastLocation->id,
                        'distance_from_last_meters' => round($distanceFromLast, 2),
                        'minimum_distance_meters' => self::DUPLICATE_RADIUS_METERS,
                    ]);
                }
            }

            // El timestamp lo genera el servidor para evitar fechas manipuladas por el dispositivo.
            $recordedAt = CarbonImmutable::now('UTC');

            $locationId = DB::table('programming_locations')->insertGetId([
                'programming_id' => $programming->id,
                'employee_id' => $employee->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $validated['accuracy'] ?? null,
                'recorded_at' => $recordedAt->toDateTimeString(),
                'created_at' => $recordedAt->toDateTimeString(),
                'updated_at' => $recordedAt->toDateTimeString(),
            ]);

            return response()->json([
                'success' => true,
                'stored' => true,
                'duplicate' => false,
                'message' => 'Ubicación registrada correctamente.',
                'location_id' => $locationId,
                'recorded_at' => $recordedAt->timezone(self::TIMEZONE)->toIso8601String(),
                'distance_from_last_meters' => $distanceFromLast === null
                    ? null
                    : round($distanceFromLast, 2),
            ], 201);
        }, 3);
    }

    private function distanceInMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB
    ): float {
        $earthRadius = 6371000.0;

        $latA = deg2rad($latitudeA);
        $latB = deg2rad($latitudeB);
        $deltaLat = deg2rad($latitudeB - $latitudeA);
        $deltaLon = deg2rad($longitudeB - $longitudeA);

        $a = sin($deltaLat / 2) ** 2
            + cos($latA) * cos($latB) * sin($deltaLon / 2) ** 2;

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }
}
