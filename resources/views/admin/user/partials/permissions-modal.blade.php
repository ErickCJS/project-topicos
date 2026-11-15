<div class="modal-header users-modal-header"><div class="users-modal-heading"><span class="users-modal-icon"><i class="bi bi-shield-lock"></i></span><h2 class="modal-title fs-5">Permisos de {{ $user->name }}</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
<div class="modal-body permission-modal-body">@include('admin.user.partials.permissions-matrix')</div>
<script>
(() => {
 const form=document.getElementById('permissionMatrixForm'); if(!form)return;
 form.addEventListener('submit',e=>{e.preventDefault();const data=new FormData(form);fetch(form.action,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':form.querySelector('[name=_token]').value},body:data}).then(async r=>{const result=await r.json();if(!r.ok)throw new Error(result.message||'No se pudieron guardar los permisos.');bootstrap.Modal.getInstance(document.getElementById('usersModal')).hide();$('#usersTable').DataTable().ajax.reload(null,false);$('#usersNotice').html('<div class="alert alert-success">'+result.message+'</div>')}).catch(error=>$('#usersNotice').html('<div class="alert alert-danger">'+error.message+'</div>'))});
})();
</script>
