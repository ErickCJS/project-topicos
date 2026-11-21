<?php

namespace App\Services;

use App\Models\admin\Zone;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CitizenZoneDetectionService
{
    public function detect(float $latitude, float $longitude, float $accuracy): Collection
    {
        $this->validateCoordinates($latitude, $longitude, $accuracy);

        return Zone::query()
            ->whereNotNull('perimeter')
            ->orderBy('id')
            ->get(['id', 'name', 'district_id', 'perimeter'])
            ->filter(function (Zone $zone) use ($latitude, $longitude): bool {
                $polygon = $this->polygonFor($zone->perimeter);

                return $this->containsPoint($polygon, $longitude, $latitude);
            })
            ->values();
    }

    private function validateCoordinates(float $latitude, float $longitude, float $accuracy): void
    {
        if (! is_finite($latitude) || $latitude < -90 || $latitude > 90) {
            throw ValidationException::withMessages(['latitude' => 'La latitud no es válida.']);
        }
        if (! is_finite($longitude) || $longitude < -180 || $longitude > 180) {
            throw ValidationException::withMessages(['longitude' => 'La longitud no es válida.']);
        }
        if (! is_finite($accuracy) || $accuracy < 0) {
            throw ValidationException::withMessages(['accuracy' => 'La precisión GPS no es válida.']);
        }

        $maximum = (float) config('citizen.gps.max_accuracy_meters', 100);
        if ($maximum <= 0 || $accuracy > $maximum) {
            throw ValidationException::withMessages([
                'accuracy' => 'La precisión GPS no es suficiente para detectar la zona.',
            ]);
        }
    }

    /** @return array<int, array{0:float,1:float}> */
    private function polygonFor(mixed $perimeter): array
    {
        if (is_string($perimeter)) {
            $perimeter = json_decode($perimeter, true);
        }

        if (is_array($perimeter) && ($perimeter['type'] ?? null) === 'Feature') {
            $perimeter = $perimeter['geometry'] ?? null;
        }

        $coordinates = is_array($perimeter) && ($perimeter['type'] ?? null) === 'Polygon'
            ? ($perimeter['coordinates'] ?? null)
            : null;
        $ring = is_array($coordinates) && count($coordinates) === 1 && is_array($coordinates[0])
            ? $coordinates[0]
            : null;

        if (! is_array($ring) || count($ring) < 4) {
            $this->invalidPolygon();
        }

        $normalized = [];
        foreach ($ring as $coordinate) {
            if (! is_array($coordinate) || count($coordinate) < 2 || ! is_numeric($coordinate[0]) || ! is_numeric($coordinate[1])) {
                $this->invalidPolygon();
            }

            $longitude = (float) $coordinate[0];
            $latitude = (float) $coordinate[1];
            if (! is_finite($longitude) || ! is_finite($latitude)
                || $longitude < -180 || $longitude > 180
                || $latitude < -90 || $latitude > 90) {
                $this->invalidPolygon();
            }
            $normalized[] = [$longitude, $latitude];
        }

        $first = $normalized[0];
        $last = $normalized[count($normalized) - 1];
        if (abs($first[0] - $last[0]) > 0.000000001 || abs($first[1] - $last[1]) > 0.000000001) {
            $this->invalidPolygon();
        }

        $distinct = [];
        foreach (array_slice($normalized, 0, -1) as $coordinate) {
            $distinct[$coordinate[0].':'.$coordinate[1]] = true;
        }
        if (count($distinct) < 3) {
            $this->invalidPolygon();
        }

        $area = 0.0;
        foreach ($normalized as $index => $coordinate) {
            $next = $normalized[($index + 1) % count($normalized)];
            $area += ($coordinate[0] * $next[1]) - ($next[0] * $coordinate[1]);
        }
        if (abs($area) < 0.000000000001) {
            $this->invalidPolygon();
        }

        $this->validateSimpleRing($normalized);

        return $normalized;
    }

    /** @param array<int, array{0:float,1:float}> $ring */
    private function validateSimpleRing(array $ring): void
    {
        $edgeCount = count($ring) - 1;

        for ($first = 0; $first < $edgeCount; $first++) {
            for ($second = $first + 1; $second < $edgeCount; $second++) {
                if ($second === $first + 1 || ($first === 0 && $second === $edgeCount - 1)) {
                    continue;
                }

                if ($this->segmentsIntersect(
                    $ring[$first], $ring[$first + 1],
                    $ring[$second], $ring[$second + 1],
                )) {
                    $this->invalidPolygon();
                }
            }
        }
    }

    /** @param array{0:float,1:float} $a */
    private function segmentsIntersect(array $a, array $b, array $c, array $d): bool
    {
        $abC = $this->orientation($a, $b, $c);
        $abD = $this->orientation($a, $b, $d);
        $cdA = $this->orientation($c, $d, $a);
        $cdB = $this->orientation($c, $d, $b);
        $epsilon = 0.0000000001;

        if (($abC > $epsilon && $abD < -$epsilon || $abC < -$epsilon && $abD > $epsilon)
            && ($cdA > $epsilon && $cdB < -$epsilon || $cdA < -$epsilon && $cdB > $epsilon)) {
            return true;
        }

        return (abs($abC) <= $epsilon && $this->onSegment($c[0], $c[1], $a[0], $a[1], $b[0], $b[1]))
            || (abs($abD) <= $epsilon && $this->onSegment($d[0], $d[1], $a[0], $a[1], $b[0], $b[1]))
            || (abs($cdA) <= $epsilon && $this->onSegment($a[0], $a[1], $c[0], $c[1], $d[0], $d[1]))
            || (abs($cdB) <= $epsilon && $this->onSegment($b[0], $b[1], $c[0], $c[1], $d[0], $d[1]));
    }

    /** @param array{0:float,1:float} $a */
    private function orientation(array $a, array $b, array $c): float
    {
        return (($b[0] - $a[0]) * ($c[1] - $a[1])) - (($b[1] - $a[1]) * ($c[0] - $a[0]));
    }

    /** @param array<int, array{0:float,1:float}> $ring */
    private function containsPoint(array $ring, float $longitude, float $latitude): bool
    {
        $inside = false;
        $last = count($ring) - 1;

        for ($index = 0; $index < count($ring); $index++) {
            [$x1, $y1] = $ring[$last];
            [$x2, $y2] = $ring[$index];

            if ($this->onSegment($longitude, $latitude, $x1, $y1, $x2, $y2)) {
                return true;
            }

            $intersects = (($y1 > $latitude) !== ($y2 > $latitude))
                && ($longitude < (($x2 - $x1) * ($latitude - $y1) / ($y2 - $y1)) + $x1);
            if ($intersects) {
                $inside = ! $inside;
            }
            $last = $index;
        }

        return $inside;
    }

    private function onSegment(float $px, float $py, float $x1, float $y1, float $x2, float $y2): bool
    {
        $cross = (($py - $y1) * ($x2 - $x1)) - (($px - $x1) * ($y2 - $y1));
        if (abs($cross) > 0.0000000001) {
            return false;
        }

        return $px >= min($x1, $x2) - 0.0000000001
            && $px <= max($x1, $x2) + 0.0000000001
            && $py >= min($y1, $y2) - 0.0000000001
            && $py <= max($y1, $y2) + 0.0000000001;
    }

    private function invalidPolygon(): never
    {
        throw ValidationException::withMessages([
            'zone' => 'Una zona tiene un perímetro GeoJSON inválido.',
        ]);
    }
}
