<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: '¡Ocurrió un error!',
            text: 'Verifique los datos ingresados e intente nuevamente.',
            confirmButtonColor: '#2563EB',
            confirmButtonText: 'Entendido'
        });
    </script>
@endif
