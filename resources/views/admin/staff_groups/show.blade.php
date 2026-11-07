
@php
    $dias = [
        1 => ['L', 'Lun', 'Lunes'],
        2 => ['M', 'Mar', 'Martes'],
        3 => ['X', 'Mié', 'Miércoles'],
        4 => ['J', 'Jue', 'Jueves'],
        5 => ['V', 'Vie', 'Viernes'],
        6 => ['S', 'Sáb', 'Sábado'],
        7 => ['D', 'Dom', 'Domingo'],
    ];

    $diasActivos = $staffgroup->days
        ->pluck('day')
        ->map(fn ($d) => (int) $d)
        ->all();

    $totalAyudantes = $staffgroup->helpers->count();
    $totalPersonal = ($staffgroup->driver ? 1 : 0) + $totalAyudantes;

    $iniciales = fn ($p) => mb_strtoupper(
        mb_substr($p->first_name ?? '', 0, 1)
        . mb_substr($p->last_name ?? '', 0, 1)
    );

    $vehiculo = $staffgroup->vehicle
        ? trim(($staffgroup->vehicle->code ?? '') . ' ' . ($staffgroup->vehicle->name ?? ''))
        : null;
@endphp



<div class="container-fluid p-0 sg-view">

    {{-- Identidad del grupo --}}
    <div class="sg-hero mb-3">
        <div class="sg-hero-icon">
            <i class="bi bi-people"></i>
        </div>

        <div class="flex-grow-1 min-w-0">
            <div class="sg-name">{{ $staffgroup->name }}</div>
            <div class="sg-subtitle">
                Grupo de personal <span class="mx-1">·</span> #{{ $staffgroup->id }}
            </div>
        </div>

        <div class="sg-status">
            <span class="sg-status-dot {{ $staffgroup->state ? 'active' : '' }}"></span>
            {{ $staffgroup->state ? 'Activo' : 'Inactivo' }}
        </div>
    </div>

    {{-- Datos del servicio --}}
    <div class="sg-metrics mb-4">
        <div class="sg-metric">
            <div class="sg-metric-icon"><i class="bi bi-clock"></i></div>
            <div class="sg-metric-label">Turno</div>
            <div class="sg-metric-value">
                {{ $staffgroup->shift->name ?? 'No asignado' }}
            </div>
        </div>

        <div class="sg-metric">
            <div class="sg-metric-icon"><i class="bi bi-geo-alt"></i></div>
            <div class="sg-metric-label">Zona</div>
            <div class="sg-metric-value">
                {{ $staffgroup->zone->name ?? 'No asignada' }}
            </div>
        </div>

        <div class="sg-metric">
            <div class="sg-metric-icon"><i class="bi bi-truck"></i></div>
            <div class="sg-metric-label">Vehículo</div>
            <div class="sg-metric-value">
                {{ $vehiculo ?: 'No asignado' }}
            </div>
        </div>
    </div>

    {{-- Jornada --}}
    <section class="mb-4">
        <div class="sg-section-heading">
            <h6 class="sg-section-title">Jornada semanal</h6>
            <span class="sg-section-caption">
                {{ count($diasActivos) }} de 7 días
            </span>
        </div>

        <div class="sg-week">
            @foreach ($dias as $num => [$letra, $corto, $nombre])
                @php $activo = in_array($num, $diasActivos, true); @endphp

                <div class="sg-week-day {{ $activo ? 'selected' : '' }}"
                     title="{{ $nombre }}">
                    <div class="sg-day-letter">{{ $letra }}</div>
                    <div class="sg-day-name">{{ $corto }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Equipo de trabajo --}}
    <section>
        <div class="sg-section-heading">
            <h6 class="sg-section-title">Equipo de trabajo</h6>
            <span class="sg-section-caption">
                {{ $totalPersonal }} {{ $totalPersonal === 1 ? 'integrante' : 'integrantes' }}
            </span>
        </div>

        <div class="row g-3">

            {{-- Conductor --}}
            <div class="col-md-6">
                <div class="sg-team-card">
                    <div class="sg-team-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge text-body-secondary"></i>
                            <span class="fw-semibold">Conductor</span>
                        </div>
                        <span class="sg-team-count">
                            {{ $staffgroup->driver ? 1 : 0 }}
                        </span>
                    </div>

                    @if ($staffgroup->driver)
                        <div class="sg-person">
                            <div class="sg-avatar">
                                {{ $iniciales($staffgroup->driver) }}
                            </div>

                            <div class="sg-person-info">
                                <div class="sg-person-name">
                                    {{ $staffgroup->driver->first_name }}
                                    {{ $staffgroup->driver->last_name }}
                                </div>
                                <div class="sg-person-dni">
                                    DNI {{ $staffgroup->driver->dni }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="sg-empty">No hay conductor asignado.</div>
                    @endif
                </div>
            </div>

            {{-- Ayudantes --}}
            <div class="col-md-6">
                <div class="sg-team-card">
                    <div class="sg-team-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-people text-body-secondary"></i>
                            <span class="fw-semibold">Ayudantes</span>
                        </div>
                        <span class="sg-team-count">{{ $totalAyudantes }}</span>
                    </div>

                    @forelse ($staffgroup->helpers as $ayudante)
                        <div class="sg-person">
                            <div class="sg-avatar">
                                {{ $iniciales($ayudante) }}
                            </div>

                            <div class="sg-person-info">
                                <div class="sg-person-name">
                                    {{ $ayudante->first_name }}
                                    {{ $ayudante->last_name }}
                                </div>
                                <div class="sg-person-dni">
                                    DNI {{ $ayudante->dni }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sg-empty">No hay ayudantes asignados.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </section>

</div>

<div class="modal-footer px-0 pb-0 mt-4">
    <button type="button" class="btn btn-sm btn-outline-secondary px-3"
            data-bs-dismiss="modal">
        Cerrar
    </button>
</div>
