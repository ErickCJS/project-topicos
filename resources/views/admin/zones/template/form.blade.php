@php
    $isEdit = $zone !== null;
    $district = $zone?->district;
    $province = $district?->province;
    $departmentId = $isEdit ? $province?->department_id : old('department_id', $defaultDepartment?->id);
    $provinceId = $isEdit ? $district?->province_id : old('province_id', $defaultProvince?->id);
    $districtId = $isEdit ? $zone?->district_id : old('district_id', $defaultDistrict?->id);
    $selectedProvince = $isEdit ? $province : ($defaultProvince ?? null);
    $selectedDistrict = $isEdit ? $district : ($defaultDistrict ?? null);
    $perimeter = old('perimeter');
    if (is_array($perimeter)) {
        $perimeter = json_encode($perimeter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } elseif ($perimeter === null && $zone?->perimeter) {
        $perimeter = json_encode($zone->perimeter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
@endphp

<form method="POST" action="{{ $isEdit ? route('zones.update', $zone) : route('zones.store') }}"
    data-zone-form data-zone-id="{{ $zone?->id ?? '' }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    <div class="modal-header zone-modal-header">
        <div class="zone-modal-heading">
            <span class="zone-modal-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
            <div><h5 class="modal-title mb-0">{{ $isEdit ? 'Editar zona' : 'Nueva zona' }}</h5></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body zone-modal-body">
        <div class="alert alert-danger zone-general-error d-none" role="alert"></div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="zone_name" class="form-label fw-semibold">Nombre *</label>
                <input id="zone_name" name="name" type="text" class="form-control" maxlength="120" required
                    value="{{ old('name', $zone?->name) }}">
                <div class="invalid-feedback d-none" data-error-for="name"></div>
            </div>
            <div class="col-12 col-md-6">
                <label for="zone_department_id" class="form-label fw-semibold">Departamento *</label>
                <select id="zone_department_id" name="department_id" class="form-select" required>
                    <option value="">Seleccione un departamento</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) $departmentId === (string) $department->id)>
                            {{ $department->name }} ({{ $department->code }})
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback d-none" data-error-for="department_id"></div>
            </div>
            <div class="col-12 col-md-6">
                <label for="zone_province_id" class="form-label fw-semibold">Provincia *</label>
                <select id="zone_province_id" name="province_id" class="form-select" data-selected="{{ $provinceId }}" required>
                    <option value="">Seleccione una provincia</option>
                    @if($selectedProvince)
                        <option value="{{ $selectedProvince->id }}" selected>{{ $selectedProvince->name }} ({{ $selectedProvince->code }})</option>
                    @endif
                </select>
                <div class="invalid-feedback d-none" data-error-for="province_id"></div>
            </div>
            <div class="col-12 col-md-6">
                <label for="zone_district_id" class="form-label fw-semibold">Distrito *</label>
                <select id="zone_district_id" name="district_id" class="form-select" data-selected="{{ $districtId }}" required>
                    <option value="">Seleccione un distrito</option>
                    @if($selectedDistrict)
                        <option value="{{ $selectedDistrict->id }}" selected>{{ $selectedDistrict->name }} ({{ $selectedDistrict->code }})</option>
                    @endif
                </select>
                <div class="invalid-feedback d-none" data-error-for="district_id"></div>
            </div>
            <div class="col-12">
                <label for="zone_description" class="form-label fw-semibold">Descripción</label>
                <textarea id="zone_description" name="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $zone?->description) }}</textarea>
                <div class="invalid-feedback d-none" data-error-for="description"></div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Perímetro *</label>
                <div class="form-text mb-2">Dibuje un polígono cerrado con al menos tres vértices. Las coordenadas se guardan como [longitud, latitud].</div>
                <div class="zone-map" data-zone-map></div>
                <input type="hidden" name="perimeter" value="{{ $perimeter ?? '' }}" data-zone-perimeter>
                <div class="invalid-feedback d-none" data-error-for="perimeter"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer zone-modal-footer">
        <button type="submit" class="btn btn-success"><i class="bi bi-floppy me-1"></i>Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancelar</button>
    </div>
</form>
