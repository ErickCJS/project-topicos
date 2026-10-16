@props(['for'])

@error($for)
    <span class="rsu-livewire-validation-error" data-validation-field="{{ $for }}" hidden>{{ $message }}</span>
@enderror
