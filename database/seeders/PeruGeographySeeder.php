<?php

namespace Database\Seeders;

use App\Models\admin\Department;
use App\Models\admin\District;
use App\Models\admin\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

/**
 * Imports the official INEI UBIGEO catalogue from a local CSV export.
 *
 * Expected columns: ubigeo, departamento, provincia, distrito.
 * The file is deliberately not committed: it must be downloaded from the
 * documented official source and reviewed before being loaded in an environment.
 */
class PeruGeographySeeder extends Seeder
{
    public function run(): void
    {
        $path = env('PERU_UBIGEO_FILE', database_path('data/peru_ubigeo.csv'));

        if (! is_file($path)) {
            $this->command?->warn(
                'No se importó geografía: falta el CSV oficial de UBIGEO en '.$path
                .'. Consulte docs/zones.md para descargarlo y preparar el archivo.'
            );

            return;
        }

        $rows = $this->readRows($path);

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $department = Department::updateOrCreate(
                    ['code' => $row['department_code']],
                    ['name' => $row['department_name']],
                );
                $province = Province::updateOrCreate(
                    ['code' => $row['province_code']],
                    [
                        'department_id' => $department->id,
                        'name' => $row['province_name'],
                    ],
                );
                District::updateOrCreate(
                    ['code' => $row['district_code']],
                    [
                        'province_id' => $province->id,
                        'name' => $row['district_name'],
                    ],
                );
            }
        });

        $this->command?->info('Geografía INEI importada: '.count($rows).' filas UBIGEO procesadas.');
    }

    /** @return list<array{department_code:string,department_name:string,province_code:string,province_name:string,district_code:string,district_name:string}> */
    private function readRows(string $path): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = $file->fgetcsv();
        if (! is_array($header)) {
            throw new RuntimeException('El archivo UBIGEO no tiene encabezado CSV.');
        }
        $header = array_map(fn ($value) => $this->header((string) $value), $header);
        $required = ['ubigeo', 'departamento', 'provincia', 'distrito'];
        if (array_diff($required, $header) !== []) {
            throw new RuntimeException('El CSV UBIGEO debe contener: '.implode(', ', $required).'.');
        }

        $positions = array_flip($header);
        $rows = [];
        foreach ($file as $line => $values) {
            if ($line === 0) {
                continue;
            }
            if (! is_array($values) || count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $code = preg_replace('/\D/', '', trim((string) ($values[$positions['ubigeo']] ?? '')));
            $departmentName = trim((string) ($values[$positions['departamento']] ?? ''));
            $provinceName = trim((string) ($values[$positions['provincia']] ?? ''));
            $districtName = trim((string) ($values[$positions['distrito']] ?? ''));
            if (! is_string($code) || ! preg_match('/^\d{6}$/', $code) || $departmentName === '' || $provinceName === '' || $districtName === '') {
                throw new RuntimeException('Fila UBIGEO inválida en la línea '.((int) $line + 2).'.');
            }

            $rows[$code] = [
                'department_code' => substr($code, 0, 2),
                'department_name' => $departmentName,
                'province_code' => substr($code, 0, 4),
                'province_name' => $provinceName,
                'district_code' => $code,
                'district_name' => $districtName,
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('El CSV UBIGEO no contiene distritos.');
        }

        return array_values($rows);
    }

    private function header(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'ubigeo', 'codigo ubigeo', 'código ubigeo', 'codigo_ubigeo' => 'ubigeo',
            'departamento', 'departamento nombre' => 'departamento',
            'provincia', 'provincia nombre' => 'provincia',
            'distrito', 'distrito nombre' => 'distrito',
            default => $value,
        };
    }
}
