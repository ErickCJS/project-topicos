<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CitizenHistoryAccessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CitizenHistoryResource;
use App\Models\Citizen;
use App\Services\CitizenHistoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonImmutable;

class CitizenHistoryController extends Controller
{
    public function __construct(private CitizenHistoryService $history) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'zone_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $this->validateNotFuture($filters);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        try {
            $results = $this->history->paginate($citizen, $filters);
        } catch (CitizenHistoryAccessException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 404);
        }

        return CitizenHistoryResource::collection($results)->additional([
            'success' => true,
            'message' => 'Historial obtenido correctamente.',
        ]);
    }

    public function show(Request $request, int $id)
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        $programming = $this->history->find($citizen, $id);

        return (new CitizenHistoryResource($programming))->additional([
            'success' => true,
            'message' => 'Servicio histórico obtenido correctamente.',
        ]);
    }

    private function validateNotFuture(array $filters): void
    {
        $today = CarbonImmutable::today();

        if (! empty($filters['from']) && CarbonImmutable::parse($filters['from'])->greaterThan($today)) {
            throw ValidationException::withMessages(['from' => 'La fecha inicial no puede ser futura.']);
        }

        if (! empty($filters['to']) && CarbonImmutable::parse($filters['to'])->greaterThan($today)) {
            throw ValidationException::withMessages(['to' => 'La fecha final no puede ser futura.']);
        }

        if (! empty($filters['month'])) {
            $month = CarbonImmutable::createFromFormat('!Y-m', $filters['month']);
            if ($month->startOfMonth()->greaterThan($today->startOfMonth())) {
                throw ValidationException::withMessages(['month' => 'El mes no puede ser futuro.']);
            }
        }
    }
}
