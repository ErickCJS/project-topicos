<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CitizenZoneConflictException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CitizenTrackingResource;
use App\Models\Citizen;
use App\Services\CitizenTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenTrackingController extends Controller
{
    public function __construct(private CitizenTrackingService $tracking) {}

    public function show(Request $request, int $id)
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        try {
            $result = $this->tracking->locate($citizen, $id);
        } catch (CitizenZoneConflictException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 409);
        }

        if ($result['status'] === 404) {
            return response()->json(['success' => false, 'message' => 'No se encontró la programación solicitada.'], 404);
        }

        if ($result['status'] === 409) {
            return response()->json(['success' => false, 'message' => 'El recorrido no está disponible para seguimiento.'], 409);
        }

        return (new CitizenTrackingResource($result['data']))->additional([
            'success' => true,
            'message' => 'Ubicación obtenida correctamente.',
        ]);
    }
}
