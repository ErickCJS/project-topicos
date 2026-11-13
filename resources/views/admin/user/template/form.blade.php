@php
    $existingLogo = isset($brand) && $brand->logo ? asset($brand->logo) : '';
@endphp

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="row g-3">
            <div class="col-12">
                {!! Form::label('name', 'Nombre', ['class' => 'form-label fw-semibold']) !!}
                {!! Form::text('name', null, [
                    'class' => 'form-control',
                    'placeholder' => 'Nombre de la marca',
                    'maxlength' => 100,
                    'required',
                ]) !!}
            </div>

            <div class="col-12">
                {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
                {!! Form::textarea('description', null, [
                    'class' => 'form-control',
                    'placeholder' => 'Agregue una descripción (opcional)',
                    'rows' => 4,
                ]) !!}
            </div>

            <div class="col-12">
                {!! Form::label('brandLogoInput', 'Logo de la marca', ['class' => 'form-label fw-semibold']) !!}
                {!! Form::file('logo', [
                    'class' => 'form-control',
                    'id' => 'brandLogoInput',
                    'accept' => 'image/*',
                    'aria-describedby' => 'brandLogoHelp',
                ]) !!}
                <div class="form-text" id="brandLogoHelp">Seleccione una imagen para identificar la marca.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="brand-preview-panel">
            <h6 class="fw-semibold mb-3">Vista previa del logo</h6>
            <div class="brand-preview-frame">
                <img
                    id="preview-logo"
                    class="brand-preview-image {{ $existingLogo ? '' : 'd-none' }}"
                    src="{{ $existingLogo }}"
                    data-existing-src="{{ $existingLogo }}"
                    alt="Vista previa del logo de la marca"
                >
                <div id="preview-placeholder" class="brand-preview-placeholder {{ $existingLogo ? 'd-none' : '' }}">
                    <i class="bi bi-image" aria-hidden="true"></i>
                    <p class="mb-0">No se ha seleccionado una imagen</p>
                </div>
            </div>
        </div>
    </div>
</div>
