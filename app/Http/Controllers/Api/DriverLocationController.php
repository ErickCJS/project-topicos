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

    // Considera repetida una ubicación que no se ha desplazado más de 5 metros
    // respecto al último punto guardado para esta programación.
    private const DUPLICATE_RADIUS_METERS = 5.0;

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
