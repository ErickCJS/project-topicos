<?php

namespace Tests\Unit;

use App\Support\GeoJsonPerimeter;
use PHPUnit\Framework\TestCase;

class GeoJsonPerimeterTest extends TestCase
{
    public function test_it_calculates_the_perimeter_of_a_geojson_polygon_in_meters(): void
    {
        $polygon = [
            'type' => 'Polygon',
            'coordinates' => [[
                [0, 0],
                [0.001, 0],
                [0.001, 0.001],
                [0, 0.001],
                [0, 0],
            ]],
        ];

        $perimeter = GeoJsonPerimeter::meters($polygon);

        $this->assertNotNull($perimeter);
        $this->assertEqualsWithDelta(444.78, $perimeter, 1.0);
    }

    public function test_it_returns_null_for_invalid_or_missing_geometry(): void
    {
        $this->assertNull(GeoJsonPerimeter::meters(null));
        $this->assertNull(GeoJsonPerimeter::meters(['type' => 'Point', 'coordinates' => [0, 0]]));
    }
}
