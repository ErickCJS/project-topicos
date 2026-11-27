<?php

namespace App\\Support;

final class GeoJsonPerimeter
{
    private const EARTH_RADIUS_METERS = 6371008.8;

    /**
     * Calcula la longitud geodésica del anillo exterior de un GeoJSON Polygon.
     * Las coordenadas GeoJSON se reciben en orden [longitud, latitud].
     */
    public static function meters(mixed $geoJson): ?float
    {
        if (is_string($geoJson)) {
            $geoJson = json_decode($geoJson, true);
        }

        if (! is_array($geoJson)) {
            return null;
        }

        if (($geoJson['type'] ?? null) === 'Feature') {
            $geoJson = $geoJson['geometry'] ?? null;
        }

        if (! is_array($geoJson) || ($geoJson['type'] ?? null) !== 'Polygon') {
            return null;
        }

        $ring = $geoJson['coordinates'][0] ?? null;
        if (! is_array($ring) || count($ring) < 4) {
            return null;
        }

        $total = 0.0;
        $count = count($ring);

        for ($index = 0; $index < $count; $index++) {
            $current = $ring[$index];
            $next = $ring[($index + 1) % $count];

            if (
                ! is_array($current) || count($current) < 2
                || ! is_array($next) || count($next) < 2
                || ! is_numeric($current[0]) || ! is_numeric($current[1])
                || ! is_numeric($next[0]) || ! is_numeric($next[1])
            ) {
                return null;
            }

            $longitude1 = deg2rad((float) $current[0]);
            $latitude1 = deg2rad((float) $current[1]);
            $longitude2 = deg2rad((float) $next[0]);
            $latitude2 = deg2rad((float) $next[1]);

            if (
                ! is_finite($longitude1) || ! is_finite($latitude1)
                || ! is_finite($longitude2) || ! is_finite($latitude2)
            ) {
                return null;
            }

            $deltaLatitude = $latitude2 - $latitude1;
            $deltaLongitude = $longitude2 - $longitude1;

            $a = sin($deltaLatitude / 2) ** 2
                + cos($latitude1) * cos($latitude2) * sin($deltaLongitude / 2) ** 2;
            $a = min(1.0, max(0.0, $a));

            $centralAngle = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $total += self::EARTH_RADIUS_METERS * $centralAngle;
        }

        return $total > 0 && is_finite($total) ? $total : null;
    }
}
