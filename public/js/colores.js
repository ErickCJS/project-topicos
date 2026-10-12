(function ($) {
    'use strict';

    const config = window.coloresConfig;
    const endpoint = config.endpoint.replace(/\/$/, '');
    let tabla;

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': config.token } });

    function mensaje(texto, error = false) {
        if (window.Swal) {
            Swal.fire({ icon: error ? 'error' : 'success', text: texto });
        } else {
            window.alert(texto);
        }
    }

    function mostrarModal(selector) {
        const elemento = document.querySelector(selector);
        bootstrap.Modal.getOrCreateInstance(elemento).show();
    }

    function ocultarModal(selector) {
        const elemento = document.querySelector(selector);
        bootstrap.Modal.getOrCreateInstance(elemento).hide();
    }

    function normalizarHexadecimal(valor) {
        valor = String(valor || '').trim().toUpperCase();
        return valor && valor.charAt(0) !== '#' ? '#' + valor : valor;
    }

    function actualizarVistaPrevia(input, picker, preview, agregarPrefijo = false) {
        let codigo = $(input).val().trim();
        if (agregarPrefijo) {
            codigo = normalizarHexadecimal(codigo);
            $(input).val(codigo);
        }
        const codigoCss = normalizarHexadecimal(codigo);
        $(preview).text(codigoCss || '#000000').css('background-color', codigoCss || '#000000');
        if (/^#[0-9a-f]{6}$/i.test(codigoCss)) {
            $(picker).val(codigoCss);
        }
    }

    function identificarColor(valor) {
        const hex = normalizarHexadecimal(valor).slice(1);
        let completo = hex;

        if (/^[0-9A-F]{3}$/.test(hex)) {
            completo = hex.split('').map(function (digito) { return digito + digito; }).join('');
        }
        if (!/^[0-9A-F]{6}$/.test(completo)) return null;

        const rgb = completo.match(/.{2}/g).map(function (canal) { return parseInt(canal, 16) / 255; });
        const maximo = Math.max.apply(null, rgb);
        const minimo = Math.min.apply(null, rgb);
        const delta = maximo - minimo;
        const luminosidad = (maximo + minimo) / 2;
        let saturacion = 0;
        let tono = 0;

        if (delta !== 0) {
            saturacion = delta / (1 - Math.abs(2 * luminosidad - 1));
            if (maximo === rgb[0]) tono = 60 * (((rgb[1] - rgb[2]) / delta) % 6);
            else if (maximo === rgb[1]) tono = 60 * ((rgb[2] - rgb[0]) / delta + 2);
            else tono = 60 * ((rgb[0] - rgb[1]) / delta + 4);
            if (tono < 0) tono += 360;
        }

        let nombre;
        if (luminosidad <= 0.12) nombre = 'Negro';
        else if (luminosidad >= 0.94) nombre = 'Blanco';
        else if (saturacion < 0.13) nombre = luminosidad < 0.38 ? 'Gris oscuro' : (luminosidad > 0.72 ? 'Gris claro' : 'Gris');
        else if (tono < 38 && luminosidad < 0.58) nombre = 'Marrón';
        else if (tono < 15 || tono >= 345) nombre = 'Rojo';
        else if (tono < 42) nombre = 'Naranja';
        else if (tono < 68) nombre = 'Amarillo';
        else if (tono < 165) nombre = 'Verde';
        else if (tono < 195) nombre = 'Cian';
        else if (tono < 255) nombre = 'Azul';
        else if (tono < 290) nombre = 'Violeta';
        else if (tono < 330) nombre = 'Magenta';
        else nombre = 'Rosa';

        return {
            name: nombre,
            description: 'Tonalidad ' + nombre.toLowerCase() + '.'
        };
    }

    function sugerirNombreYDescripcion(inputCodigo, inputNombre, inputDescripcion, estado) {
        const sugerencia = identificarColor($(inputCodigo).val());
        if (!sugerencia) return;

        const $nombre = $(inputNombre);
        const $descripcion = $(inputDescripcion);
        if (!$nombre.val().trim() || $nombre.val() === estado.nombre) {
            $nombre.val(sugerencia.name);
            estado.nombre = sugerencia.name;
        }
        if (!$descripcion.val().trim() || $descripcion.val() === estado.descripcion) {
            $descripcion.val(sugerencia.description);
            estado.descripcion = sugerencia.description;
            actualizarContador($descripcion[0]);
        }
    }

    function errorMensaje(xhr) {
        const data = xhr.responseJSON || {};
        const texto = data.message || data.mensaje || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Ocurrió un error.');
        mensaje(texto, true);
    }

    function limpiarErrores($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-error-for]').text('').removeClass('d-block');
    }

    function mostrarErrores($form, errores) {
        limpiarErrores($form);
        Object.keys(errores).forEach(function (campo) {
            const $input = $form.find('[name="' + campo + '"]');
            $input.addClass('is-invalid');
            $form.find('[data-error-for="' + campo + '"]')
                .text(errores[campo][0])
                .addClass('d-block');
        });
        const $primerError = $form.find('.is-invalid').first();
        if ($primerError.length) $primerError.trigger('focus');
    }

    function validarFormulario($form) {
        limpiarErrores($form);
        const $nombre = $form.find('[name="name"]');
        const $codigo = $form.find('[name="code"]');
        const $descripcion = $form.find('[name="description"]');
        const nombre = String($nombre.val() || '').trim();
        const codigo = normalizarHexadecimal($codigo.val()).toUpperCase();
        const descripcion = String($descripcion.val() || '').trim();
        const errores = {};

        $nombre.val(nombre);
        $codigo.val(codigo);
        $descripcion.val(descripcion);

        if (!nombre) errores.name = ['Escribe el nombre del color.'];
        else if (nombre.length > 100) errores.name = ['El nombre no puede superar los 100 caracteres.'];
        else if (!/^[\p{L}\p{M}]+(?:[ .'-][\p{L}\p{M}]+)*$/u.test(nombre)) {
            errores.name = ['Usa letras, espacios, puntos, apóstrofos o guiones en el nombre.'];
        }

        if (!codigo) errores.code = ['Ingresa un código hexadecimal.'];
        else if (!/^#(?:[A-F0-9]{3}|[A-F0-9]{6})$/.test(codigo)) {
            errores.code = ['Usa el formato #RGB o #RRGGBB; por ejemplo, #735D56.'];
        }

        if (descripcion.length > 100) errores.description = ['La descripción no puede superar los 100 caracteres.'];
        else if (/[<>]/.test(descripcion)) errores.description = ['La descripción no puede contener etiquetas HTML.'];

        if (Object.keys(errores).length) {
            mostrarErrores($form, errores);
            return false;
        }
        return true;
    }

    function manejarErrorFormulario(xhr, $form) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            mostrarErrores($form, xhr.responseJSON.errors);
            return;
        }
        errorMensaje(xhr);
    }

    function actualizarContador(textarea) {
        const id = textarea.id;
        $('[data-counter-for="' + id + '"]').text(String(textarea.value.length));
    }

    function fechaLatinoamericana(valor) {
        if (!valor) return '';
        const fecha = new Date(String(valor).replace(' ', 'T'));
        if (Number.isNaN(fecha.getTime())) return valor;
        return new Intl.DateTimeFormat('es-419', {
            dateStyle: 'short',
            timeStyle: 'short'
        }).format(fecha);
    }

    $(function () {
        const sugerenciasCrear = { nombre: '', descripcion: '' };
        const sugerenciasEditar = { nombre: '', descripcion: '' };

        tabla = $('#tablaColores').DataTable({
            processing: true,
            serverSide: true,
            ajax: endpoint,
            columns: [
                { data: 'preview', name: 'code', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
                { data: 'description', name: 'description', defaultContent: '' },
                { data: 'created_at', name: 'created_at', defaultContent: '', render: fechaLatinoamericana },
                { data: 'updated_at', name: 'updated_at', defaultContent: '', render: fechaLatinoamericana },
                { data: 'actions', orderable: false, searchable: false }
            ],
            language: {
                processing: 'Cargando información...',
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                loadingRecords: 'Cargando registros...',
                zeroRecords: 'No se encontraron registros',
                emptyTable: 'Aún no hay colores registrados',
                paginate: {
                    first: 'Primero',
                    previous: 'Anterior',
                    next: 'Siguiente',
                    last: 'Último'
                }
            }
        });

        $('#btnNuevoColor').on('click', function () {
            $('#formCrearColor')[0].reset();
            limpiarErrores($('#formCrearColor'));
            sugerenciasCrear.nombre = '';
            sugerenciasCrear.descripcion = '';
            $('#crearCode').val('#000000');
            $('#crearPicker').val('#000000');
            actualizarContador(document.getElementById('crearDescription'));
            actualizarVistaPrevia('#crearCode', '#crearPicker', '#crearPreview');
            mostrarModal('#modalCrearColor');
        });

        $('#crearCode').on('input', function () {
            actualizarVistaPrevia('#crearCode', '#crearPicker', '#crearPreview');
            sugerirNombreYDescripcion('#crearCode', '#crearName', '#crearDescription', sugerenciasCrear);
        });
        $('#crearCode').on('blur', function () {
            actualizarVistaPrevia('#crearCode', '#crearPicker', '#crearPreview', true);
        });
        $('#crearPicker').on('input change', function () {
            $('#crearCode').val(this.value.toUpperCase());
            actualizarVistaPrevia('#crearCode', '#crearPicker', '#crearPreview');
            sugerirNombreYDescripcion('#crearCode', '#crearName', '#crearDescription', sugerenciasCrear);
        });
        $('#editarCode').on('input', function () {
            actualizarVistaPrevia('#editarCode', '#editarPicker', '#editarPreview');
            sugerirNombreYDescripcion('#editarCode', '#editarName', '#editarDescription', sugerenciasEditar);
        });
        $('#editarCode').on('blur', function () {
            actualizarVistaPrevia('#editarCode', '#editarPicker', '#editarPreview', true);
        });
        $('#editarPicker').on('input change', function () {
            $('#editarCode').val(this.value.toUpperCase());
            actualizarVistaPrevia('#editarCode', '#editarPicker', '#editarPreview');
            sugerirNombreYDescripcion('#editarCode', '#editarName', '#editarDescription', sugerenciasEditar);
        });

        $('#formCrearColor, #formEditarColor').on('input change', 'input, textarea', function () {
            const $form = $(this).closest('form');
            const name = this.name;
            $form.find('[name="' + name + '"]').removeClass('is-invalid');
            $form.find('[data-error-for="' + name + '"]').text('').removeClass('d-block');
            if (this.tagName === 'TEXTAREA') actualizarContador(this);
        });
        $('#crearDescription, #editarDescription').each(function () { actualizarContador(this); });

        $('#formCrearColor').on('submit', function (event) {
            event.preventDefault();
            const $form = $(this);
            if (!validarFormulario($form)) return;
            $.ajax({ url: endpoint, method: 'POST', data: $(this).serialize() })
                .done(function (response) {
                    ocultarModal('#modalCrearColor');
                    tabla.ajax.reload(null, false);
                    mensaje(response.mensaje || 'Color creado.');
                }).fail(function (xhr) { manejarErrorFormulario(xhr, $form); });
        });

        $('#tablaColores').on('click', '.btnEditar', function () {
            const id = $(this).data('id');
            $.ajax({ url: endpoint + '/' + id + '/edit', method: 'GET' })
                .done(function (color) {
                    limpiarErrores($('#formEditarColor'));
                    const tipoActual = identificarColor(color.code);
                    sugerenciasEditar.nombre = tipoActual && color.name === tipoActual.name ? tipoActual.name : '';
                    sugerenciasEditar.descripcion = tipoActual && color.description === tipoActual.description ? tipoActual.description : '';
                    $('#editarId').val(color.id);
                    $('#editarName').val(color.name);
                    $('#editarCode').val(normalizarHexadecimal(color.code).toUpperCase());
                    $('#editarDescription').val(color.description || '');
                    actualizarContador(document.getElementById('editarDescription'));
                    actualizarVistaPrevia('#editarCode', '#editarPicker', '#editarPreview');
                    mostrarModal('#modalEditarColor');
                }).fail(errorMensaje);
        });

        $('#formEditarColor').on('submit', function (event) {
            event.preventDefault();
            const $form = $(this);
            if (!validarFormulario($form)) return;
            const id = $('#editarId').val();
            $('#editarCode').val(normalizarHexadecimal($('#editarCode').val()).toUpperCase());
            $.ajax({ url: endpoint + '/' + id, method: 'PUT', data: $(this).serialize() })
                .done(function (response) {
                    ocultarModal('#modalEditarColor');
                    tabla.ajax.reload(null, false);
                    mensaje(response.mensaje || 'Color actualizado.');
                }).fail(function (xhr) { manejarErrorFormulario(xhr, $form); });
        });

        $('#tablaColores').on('click', '.btnEliminar', function () {
            const id = $(this).data('id');
            const eliminar = function () {
                $.ajax({ url: endpoint + '/' + id, method: 'DELETE' })
                    .done(function (response) {
                        tabla.ajax.reload(null, false);
                        mensaje(response.mensaje || 'Color eliminado.');
                    }).fail(errorMensaje);
            };

            if (window.Swal) {
                Swal.fire({ title: '¿Eliminar este color?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' })
                    .then(function (result) { if (result.isConfirmed) eliminar(); });
            } else if (window.confirm('¿Eliminar este color?')) {
                eliminar();
            }
        });
    });
})(jQuery);
