<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\CitizenZoneConflictException;
use App\Models\Citizen;
use App\Services\CitizenZoneAssignmentService;
use App\Services\CitizenZoneDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenZoneController extends Controller
{
    public function __construct(
        private CitizenZoneDetectionService $detection,
        private CitizenZoneAssignmentService $assignments,
    ) {}

    public function current(Request $request): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();
        $assignment = $citizen->currentZoneAssignment()->with('zone')->first();
        $zone = $assignment?->zone ?? $citizen->zone;

        if (! $zone) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una zona confirmada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'zone' => $this->zone($zone),
                'assigned_at' => $assignment?->assigned_at?->toIso8601String(),
                'coordinates' => $assignment ? [
                    'latitude' => $assignment->latitude,
                    'longitude' => $assignment->longitude,
                    'accuracy' => $assignment->accuracy,
                ] : null,
            ],
        ]);
    }

    public function detect(Request $request): JsonResponse
    {
        $coordinates = $this->coordinates($request);
        $zones = $this->detection->detect(...$coordinates);

        if ($zones->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una zona para las coordenadas indicadas.',
            ], 404);
        }

        if ($zones->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Las coordenadas coinciden con más de una zona.',
                'zones' => $zones->map(fn ($zone) => $this->zone($zone))->values(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'confirmed' => false,
            'requires_confirmation' => true,
            'data' => [
                'zone' => $this->zone($zones->first()),
                'coordinates' => $this->coordinateResponse($coordinates),
            ],
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'confirmed' => ['required', 'accepted'],
        ]);

        /** @var Citizen $citizen */
        $citizen = $request->user();
        try {
            $result = $this->assignments->confirm(
                $citizen,
                (float) $data['latitude'],
                (float) $data['longitude'],
                (float) $data['accuracy'],
            );
        } catch (CitizenZoneConflictException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 409);
        }

        if ($result['status'] === 404) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una zona para las coordenadas indicadas.',
            ], 404);
        }

        if ($result['status'] === 409) {
            return response()->json([
                'success' => false,
                'message' => 'Las coordenadas coinciden con más de una zona.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'confirmed' => true,
            'changed' => $result['changed'],
            'data' => [
                'zone' => $this->zone($result['zone']),
                'coordinates' => [
                    'latitude' => (float) $data['latitude'],
                    'longitude' => (float) $data['longitude'],
                    'accuracy' => (float) $data['accuracy'],
                ],
            ],
        ]);
    }

    /** @return array{0:float,1:float,2:float} */
    private function coordinates(Request $request): array
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
        ]);

        return [(float) $data['latitude'], (float) $data['longitude'], (float) $data['accuracy']];
    }

    private function coordinateResponse(array $coordinates): array
    {
        return ['latitude' => $coordinates[0], 'longitude' => $coordinates[1], 'accuracy' => $coordinates[2]];
    }

    private function zone(object $zone): array
    {
        return ['id' => $zone->id, 'name' => $zone->name];
    }
}
