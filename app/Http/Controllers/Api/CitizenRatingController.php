<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CitizenRatingConflictException;
use App\Exceptions\CitizenRatingNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CitizenRatingResource;
use App\Models\Citizen;
use App\Services\CitizenRatingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CitizenRatingController extends Controller
{
    public function __construct(private CitizenRatingService $ratings) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'programming_id' => ['required', 'integer', 'min:1'],
            'stars' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        try {
            $rating = $this->ratings->create($citizen, (int) $data['programming_id'], (int) $data['stars'], $data['comment'] ?? null);
        } catch (CitizenRatingNotFoundException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 404);
        } catch (CitizenRatingConflictException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 409);
        }

        return (new CitizenRatingResource($rating))->additional([
            'success' => true,
            'message' => 'Calificación creada correctamente.',
        ])->response()->setStatusCode(201);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $this->validateNotFuture($filters);

        return CitizenRatingResource::collection($this->ratings->paginate($request->user(), $filters))->additional([
            'success' => true,
            'message' => 'Calificaciones obtenidas correctamente.',
        ]);
    }

    public function show(Request $request, int $id)
    {
        try {
            $rating = $this->ratings->find($request->user(), $id);
        } catch (CitizenRatingNotFoundException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 404);
        }

        return (new CitizenRatingResource($rating))->additional([
            'success' => true,
            'message' => 'Calificación obtenida correctamente.',
        ]);
    }

    private function validateNotFuture(array $filters): void
    {
        $today = CarbonImmutable::today();
        if (! empty($filters['from']) && CarbonImmutable::parse($filters['from'])->greaterThan($today)) throw ValidationException::withMessages(['from' => 'La fecha inicial no puede ser futura.']);
        if (! empty($filters['to']) && CarbonImmutable::parse($filters['to'])->greaterThan($today)) throw ValidationException::withMessages(['to' => 'La fecha final no puede ser futura.']);
        if (! empty($filters['month']) && CarbonImmutable::createFromFormat('!Y-m', $filters['month'])->startOfMonth()->greaterThan($today->startOfMonth())) throw ValidationException::withMessages(['month' => 'El mes no puede ser futuro.']);
    }
}
