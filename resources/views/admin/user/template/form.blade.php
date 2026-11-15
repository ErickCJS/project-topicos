@csrf
@if(isset($user)) @method('PUT') @endif
<div class="modal-body"><div class="row g-3">
@foreach([['name','Nombres','text',true],['last_name','Apellidos','text',true],['dni','DNI','text',true],['email','Correo electrónico','email',true],['birthdate','Fecha de nacimiento','date',false],['phone','Teléfono','text',false],['license','Licencia','text',false]] as [$field,$label,$type,$required])
<div class="col-12 col-md-6"><label class="form-label" for="user_{{ $field }}">{{ $label }}</label><input type="{{ $type }}" class="form-control @error($field)is-invalid @enderror" id="user_{{ $field }}" name="{{ $field }}" @if($required) required @endif @if($field==='dni') inputmode="numeric" pattern="[0-9]{8}" maxlength="8" @endif value="{{ old($field, isset($user) ? ($field==='birthdate' ? $user->birthdate?->format('Y-m-d') : $user->{$field}) : '') }}" >@error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
@endforeach
<div class="col-12">
@if(isset($user))
<div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="user_change_password" name="change_password" value="1" @checked(old('change_password'))><label class="form-check-label" for="user_change_password">Cambiar contraseña</label></div>
<label class="form-label" for="user_password">Nueva contraseña</label><input type="password" class="form-control @error('password')is-invalid @enderror" id="user_password" name="password" minlength="8" autocomplete="new-password" @disabled(!old('change_password'))>@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
@else
<label class="form-label" for="user_password">Contraseña</label><input type="password" class="form-control @error('password')is-invalid @enderror" id="user_password" name="password" required minlength="8" autocomplete="new-password">@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
@endif
</div>
@if(isset($user) || auth()->user()->is_admin)<div class="col-12"><input type="hidden" name="is_admin" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_admin" id="user_is_admin" value="1" @checked(old('is_admin', $user->is_admin ?? false))><label class="form-check-label" for="user_is_admin">Administrador (acceso total)</label></div></div>@endif
<div class="col-12 col-md-6"><label class="form-label" for="user_profile_photo">Fotografía</label><input type="file" class="form-control @error('profile_photo')is-invalid @enderror" id="user_profile_photo" name="profile_photo" accept="image/*">@error('profile_photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
<div class="col-12"><label class="form-label" for="user_address">Dirección</label><textarea class="form-control @error('address')is-invalid @enderror" id="user_address" name="address" rows="2">{{ old('address', $user->address ?? '') }}</textarea>@error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
@if(isset($user))<div class="col-12"><input type="hidden" name="is_active" value="0"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="user_is_active" value="1" @checked(old('is_active', $user->is_active))><label class="form-check-label" for="user_is_active">Usuario activo</label></div>@error('is_active')<div class="text-danger small">{{ $message }}</div>@enderror</div>@endif
</div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success"><i class="bi bi-floppy me-1"></i>{{ isset($user) ? 'Guardar cambios' : 'Registrar' }}</button></div>
