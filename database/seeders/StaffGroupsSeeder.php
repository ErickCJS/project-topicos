<?php

namespace Database\Seeders;

use App\Models\admin\Employee;
use App\Models\admin\Shift;
use App\Models\admin\StaffGroup;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StaffGroupsSeeder extends Seeder
{
    private const NAME_PREFIX = 'SEED-GRUPO-PERSONAL-';

    private const DAY_PATTERNS = [
        [1, 3, 5, 6],
        [2, 4, 7],
    ];

    public function run(): void
    {
        $shifts = Shift::query()->orderBy('id')->pluck('id')->all();
        $jloDistrictId = DB::table('districts')
            ->join('provinces', 'districts.province_id', '=', 'provinces.id')
            ->join('departments', 'provinces.department_id', '=', 'departments.id')
            ->where('departments.code', '14')
            ->where('provinces.code', '1401')
            ->where('districts.code', '140105')
            ->value('districts.id');
        $zones = $jloDistrictId
            ? Zone::query()->where('district_id', $jloDistrictId)->orderBy('id')->pluck('id')->all()
            : [];
        $vehicles = Vehicle::query()->where('occupats_capacity', '>', 0)
            ->orderByDesc('occupats_capacity')->orderBy('id')->get(['id', 'occupats_capacity']);
        $drivers = Employee::query()->whereBetween('dni', ['93000001', '93000025'])
            ->where('status', 'Activo')->withVigenteContract()->orderBy('id')->pluck('id')->all();
        $helpers = Employee::query()->whereBetween('dni', ['93010001', '93010065'])
            ->where('status', 'Activo')->withVigenteContract()->orderBy('id')->pluck('id')->all();

        if ($shifts === [] || $zones === [] || $vehicles->isEmpty() || $drivers === []) {
            throw new RuntimeException('Se requieren turnos, zonas, vehículos y conductores con contrato vigente antes de generar grupos.');
        }

        DB::transaction(function () use ($shifts, $zones, $vehicles, $drivers, $helpers): void {
            $managedIds = StaffGroup::query()->where('name', 'like', self::NAME_PREFIX.'%')->pluck('id');
            $existingGroups = StaffGroup::query()->with(['days:id,staff_group_id,day', 'helpers:id'])
                ->where('state', true)
                ->when($managedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $managedIds))
                ->get();

            $occupiedVehicles = [];
            $occupiedDrivers = [];
            $occupiedHelpers = [];

            foreach ($existingGroups as $group) {
                foreach ($group->days as $groupDay) {
                    $slot = $group->shift_id.':'.$groupDay->day;
                    $occupiedVehicles[$slot][$group->vehicle_id] = true;
                    if ($group->driver_id) {
                        $occupiedDrivers[$slot][$group->driver_id] = true;
                    }
                    foreach ($group->helpers as $helper) {
                        $occupiedHelpers[$slot][$helper->id] = true;
                    }
                }
            }

            $usedDriverIds = [];
            $usedGroupIds = [];
            $driverCursor = 0;
            $helperCursor = 0;
            $createdCount = 0;
            $assignedHelperCount = 0;
            $slotsByPattern = [];

            foreach (self::DAY_PATTERNS as $patternIndex => $days) {
                foreach ($shifts as $shiftId) {
                    foreach ($vehicles as $vehicle) {
                        $slotsByPattern[$patternIndex][] = [
                            'shift_id' => $shiftId,
                            'days' => $days,
                            'vehicle' => $vehicle,
                        ];
                    }
                }
            }

            $slotCursors = array_fill(0, count(self::DAY_PATTERNS), 0);
            while ($createdCount < count($drivers)) {
                $createdThisRound = false;
                $preferredPattern = $createdCount % count(self::DAY_PATTERNS);

                for ($patternOffset = 0; $patternOffset < count(self::DAY_PATTERNS); $patternOffset++) {
                    $patternIndex = ($preferredPattern + $patternOffset) % count(self::DAY_PATTERNS);

                    while ($slotCursors[$patternIndex] < count($slotsByPattern[$patternIndex])) {
                        $candidate = $slotsByPattern[$patternIndex][$slotCursors[$patternIndex]++];
                        $shiftId = $candidate['shift_id'];
                        $days = $candidate['days'];
                        $vehicle = $candidate['vehicle'];
                        $slots = array_map(fn ($day) => $shiftId.':'.$day, $days);

                        if (collect($slots)->contains(fn ($slot) => isset($occupiedVehicles[$slot][$vehicle->id]))) {
                            continue;
                        }

                        $driverId = null;
                        for ($offset = 0; $offset < count($drivers); $offset++) {
                            $candidateIndex = ($driverCursor + $offset) % count($drivers);
                            $candidateId = $drivers[$candidateIndex];
                            $isAvailable = collect($slots)->every(
                                fn ($slot) => ! isset($occupiedDrivers[$slot][$candidateId])
                            );
                            if (! isset($usedDriverIds[$candidateId]) && $isAvailable) {
                                $driverId = $candidateId;
                                $driverCursor = ($candidateIndex + 1) % count($drivers);
                                break;
                            }
                        }
                        if ($driverId === null) {
                            continue;
                        }

                        $helperIds = [];
                        $helperLimit = min(max(0, (int) $vehicle->occupats_capacity - 1), count($helpers));
                        $startingHelperCursor = $helperCursor;
                        $lastHelperIndex = null;
                        for ($offset = 0; $offset < count($helpers) && count($helperIds) < $helperLimit; $offset++) {
                            $candidateIndex = ($startingHelperCursor + $offset) % count($helpers);
                            $candidateId = $helpers[$candidateIndex];
                            $isAvailable = collect($slots)->every(
                                fn ($slot) => ! isset($occupiedHelpers[$slot][$candidateId])
                            );
                            if ($isAvailable) {
                                $helperIds[] = $candidateId;
                                $lastHelperIndex = $candidateIndex;
                            }
                        }
                        if ($lastHelperIndex !== null) {
                            $helperCursor = ($lastHelperIndex + 1) % count($helpers);
                        }

                        $createdCount++;
                        $group = StaffGroup::query()->updateOrCreate(
                            ['name' => self::NAME_PREFIX.sprintf('%02d', $createdCount)],
                            [
                                'shift_id' => $shiftId,
                                'zone_id' => $zones[($createdCount - 1) % count($zones)],
                                'vehicle_id' => $vehicle->id,
                                'driver_id' => $driverId,
                                'state' => true,
                            ]
                        );
                        $group->days()->delete();
                        $group->days()->createMany(array_map(fn ($day) => ['day' => $day], $days));
                        $group->helpers()->sync($helperIds);

                        $usedDriverIds[$driverId] = true;
                        $usedGroupIds[] = $group->id;
                        foreach ($slots as $slot) {
                            $occupiedVehicles[$slot][$vehicle->id] = true;
                            $occupiedDrivers[$slot][$driverId] = true;
                            foreach ($helperIds as $helperId) {
                                $occupiedHelpers[$slot][$helperId] = true;
                            }
                        }
                        $assignedHelperCount += count($helperIds);

                        $createdThisRound = true;
                        break;
                    }

                    if ($createdThisRound) {
                        break;
                    }
                }

                if (! $createdThisRound) {
                    break;
                }
            }

            StaffGroup::query()->whereIn('id', $managedIds->diff($usedGroupIds))->update(['state' => false]);
            $this->command?->info("Grupos generados: {$createdCount}; ayudantes asignados: {$assignedHelperCount}.");
        });
    }
}