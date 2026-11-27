<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReasonSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $reasons = [
            ['name' => 'Avería mecánica', 'description' => 'El vehículo asignado presenta una falla mecánica.'],
            ['name' => 'Mantenimiento preventivo', 'description' => 'El vehículo requiere mantenimiento preventivo.'],
            ['name' => 'Accidente', 'description' => 'El recurso asignado se vio involucrado en un accidente.'],
            ['name' => 'Indisponibilidad del vehículo', 'description' => 'El vehículo asignado no está disponible para el servicio.'],
            ['name' => 'Reasignación operativa', 'description' => 'Cambio requerido por la operación del servicio.'],
            ['name' => 'Cambio de requerimiento operativo', 'description' => 'Se modificó el requerimiento operativo del servicio.'],
            ['name' => 'Reprogramación del servicio', 'description' => 'Se requiere reprogramar el servicio.'],
            ['name' => 'Modificación de horario', 'description' => 'Se modificó el horario requerido para el servicio.'],
            ['name' => 'Necesidad operativa', 'description' => 'Cambio requerido para atender una necesidad operativa.'],
            ['name' => 'Descanso médico', 'description' => 'El trabajador se encuentra con descanso médico.'],
            ['name' => 'Inasistencia', 'description' => 'El trabajador asignado no asistirá al servicio.'],
            ['name' => 'Cambio de disponibilidad', 'description' => 'Cambió la disponibilidad del recurso asignado.'],
            ['name' => 'Reasignación de personal', 'description' => 'Cambio requerido en la asignación del personal.'],
            ['name' => 'Ajuste de capacidad del vehículo', 'description' => 'Se retira un ayudante para adaptar el equipo a la capacidad del vehículo.'],
            ['name' => 'Solicitud del trabajador', 'description' => 'Cambio solicitado por el trabajador asignado.'],
            ['name' => 'Otro', 'description' => 'Motivo distinto; requiere especificar el detalle.'],
        ];

        foreach ($reasons as $reason) {
            DB::table('reason')->updateOrInsert(
                ['name' => $reason['name']],
                ['description' => $reason['description'], 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }
}
