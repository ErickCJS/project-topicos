<?php

namespace App\Services;

use App\Exceptions\CitizenRatingConflictException;
use App\Exceptions\CitizenRatingNotFoundException;
use App\Models\Citizen;
use App\Models\CitizenRating;
use App\Models\admin\Programming;
use App\Models\admin\Zone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CitizenRatingService
{
    public function create(Citizen $citizen, int $programmingId, int $stars, ?string $comment): CitizenRating
    {
        return DB::transaction(function () use ($citizen, $programmingId, $stars, $comment): CitizenRating {
            $programming = Programming::query()->whereKey($programmingId)->lockForUpdate()->first();

            if (! $programming) {
                throw new CitizenRatingNotFoundException('No se encontró la programación solicitada.');
            }

            $this->assertHistoricalAuthorization($citizen, $programming);

            if ($programming->status === Programming::STATUS_CANCELLED
                || $programming->status === Programming::STATUS_RESCHEDULED
                || $programming->hasChildProgramming()) {
                throw new CitizenRatingConflictException('La programación no admite calificaciones.');
            }

            if ($programming->status !== Programming::STATUS_FINISHED || $programming->finished_at === null) {
                throw new CitizenRatingConflictException('Solo se pueden calificar servicios completados.');
            }

            if ($programming->date->isFuture()) {
                throw new CitizenRatingConflictException('No se puede calificar un servicio futuro.');
            }

            if ($citizen->ratings()->where('programming_id', $programming->id)->exists()) {
                throw new CitizenRatingConflictException('Esta programación ya fue calificada.');
            }

            try {
                // citizen_id is deliberately not fillable: it must come from
                // the authenticated model, never from the request payload.
                $rating = new CitizenRating([
                    'programming_id' => $programming->id,
                    'stars' => $stars,
                    'comment' => $comment,
                ]);
                $rating->citizen_id = $citizen->id;
                $rating->save();

                $rating->load(['programming.shift', 'programming.vehicle']);
                $this->attachZones(collect([$rating]));

                return $rating;
            } catch (QueryException $exception) {
                if (! $this->isDuplicateRatingException($exception)) {
                    throw $exception;
                }

                throw new CitizenRatingConflictException('Esta programación ya fue calificada.', 0, $exception);
            }
        });
    }

    public function paginate(Citizen $citizen, array $filters): LengthAwarePaginator
    {
        $ratings = CitizenRating::query()
            ->with([
                'programming.shift:id,name,start_time,end_time',
                'programming.vehicle:id,name,plate',
            ])
            ->where('citizen_id', $citizen->id)
            ->when(! empty($filters['month']), function ($query) use ($filters): void {
                $start = $filters['month'].'-01';
                $query->whereHas('programming', fn ($programming) => $programming->whereBetween('date', [$start, date('Y-m-t', strtotime($start))]));
            })
            ->when(! empty($filters['from']), fn ($query) => $query->whereHas('programming', fn ($programming) => $programming->whereDate('date', '>=', $filters['from'])))
            ->when(! empty($filters['to']), fn ($query) => $query->whereHas('programming', fn ($programming) => $programming->whereDate('date', '<=', $filters['to'])))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15));

        $this->attachZones($ratings->getCollection());

        return $ratings;
    }

    public function find(Citizen $citizen, int $id): CitizenRating
    {
        $rating = CitizenRating::query()
            ->with(['programming.shift', 'programming.vehicle'])
            ->where('citizen_id', $citizen->id)
            ->whereKey($id)
            ->first();

        if (! $rating) {
            throw new CitizenRatingNotFoundException('No se encontró la calificación solicitada.');
        }

        $this->attachZones(collect([$rating]));

        return $rating;
    }

    private function assertHistoricalAuthorization(Citizen $citizen, Programming $programming): void
    {
        $count = $citizen->zoneAssignments()
            ->where('zone_id', $programming->zone_id)
            ->whereRaw('DATE(assigned_at) <= DATE(?)', [$programming->date->toDateString()])
            ->where(function ($query) use ($programming): void {
                $query->whereNull('ended_at')
                    ->orWhereRaw('DATE(ended_at) >= DATE(?)', [$programming->date->toDateString()]);
            })
            ->count();

        if ($count === 0) {
            throw new CitizenRatingNotFoundException('La programación no pertenece al historial del ciudadano.');
        }

        if ($count > 1) {
            Log::notice('Citizen rating blocked by ambiguous historical zone assignments.', [
                'citizen_id' => $citizen->id,
                'programming_id' => $programming->id,
            ]);
            throw new CitizenRatingConflictException('No se puede determinar una única zona histórica para la programación.');
        }
    }

    private function attachZones($ratings): void
    {
        $zoneIds = $ratings->pluck('programming.zone_id')->filter()->unique()->values();
        $zones = Zone::query()->whereIn('id', $zoneIds)->get()->keyBy('id');

        $ratings->each(function (CitizenRating $rating) use ($zones): void {
            $rating->programming?->setRelation('zone', $zones->get($rating->programming->zone_id));
        });
    }

    private function isDuplicateRatingException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            return ($errorInfo[0] ?? null) === '23000' && (int) ($errorInfo[1] ?? 0) === 1062;
        }

        return $driver === 'sqlite'
            && ($errorInfo[0] ?? null) === '23000'
            && str_contains(strtolower($exception->getMessage()), 'unique constraint failed');
    }
}
