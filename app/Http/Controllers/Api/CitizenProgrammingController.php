<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CitizenZoneConflictException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CitizenProgrammingResource;
use App\Models\Citizen;
use App\Services\CitizenProgrammingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenProgrammingController extends Controller
{
    public function __construct(private CitizenProgrammingService $programming) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        try {
            $results = $this->programming->paginate($citizen, $filters);
        } catch (CitizenZoneConflictException $exception) {
            return $this->zoneConflict($exception);
        }

        return CitizenProgrammingResource::collection($results)
            ->additional([
                'success' => true,
                'message' => 'Programaciones obtenidas correctamente.',
            ]);
    }

    public function show(Request $request, int $id)
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        try {
            $programming = $this->programming->find($citizen, $id);
        } catch (CitizenZoneConflictException $exception) {
            return $this->zoneConflict($exception);
        }

        return (new CitizenProgrammingResource($programming))->additional([
            'success' => true,
            'message' => 'Programación obtenida correctamente.',
        ]);
    }

    private function zoneConflict(CitizenZoneConflictException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 409);
    }
}
