(function ($) {
    'use strict';

    const config = window.coloresConfig;
    const endpoint = config.endpoint.replace(/\/$/, '');
    let tabla;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': config.token,
            Accept: 'application/json'
        }
    });

    function mensaje(texto, error = false) {
        if (error) return window.RSUValidation.error(texto);
        return Swal.fire({
            icon: 'success',
            title: 'Proceso exitoso',
            text: texto,
            confirmButtonText: 'Entendido'
        });
    }

    function mostrarModal(selector) {
        bootstrap.Modal.getOrCreateInstance(document.querySelector(selector)).show();
    }

    function ocultarModal(selector) {
        bootstrap.Modal.getOrCreateInstance(document.querySelector(selector)).hide();
    }

    function normalizarHexadecimal(valor) {
        const codigo = String(valor || '').trim().toUpperCase();

        return codigo && codigo.charAt(0) !== '#' ? '#' + codigo : codigo;
    }

    function hexadecimalParaSelector(valor) {
        const codigo = normalizarHexadecimal(valor);

        if (/^#[A-F0-9]{6}$/.test(codigo)) return codigo;
        if (/^#[A-F0-9]{3}$/.test(codigo)) {
            return '#' + codigo.slice(1).split('').map(function (digito) {
                return digito + digito;
            }).join('');
        }

        return null;
    }

    function aplicarContraste(elemento, codigo) {
        const completo = hexadecimalParaSelector(codigo);
        if (!completo) return;

        const rojo = parseInt(completo.slice(1, 3), 16);
        const verde = parseInt(completo.slice(3, 5), 16);
        const azul = parseInt(completo.slice(5, 7), 16);
        const luminosidad = (rojo * 299 + verde * 587 + azul * 114) / 1000;
        const textoOscuro = luminosidad >= 160;

        $(elemento).css({
            color: textoOscuro ? '#212529' : '#FFFFFF',
            textShadow: textoOscuro ? 'none' : '0 1px 2px rgba(0, 0, 0, .65)'
        });
    }

    function actualizarVistaPrevia(input, picker, preview, agregarPrefijo = false) {
        let codigo = String($(input).val() || '').trim().toUpperCase();

        if (agregarPrefijo) codigo = normalizarHexadecimal(codigo);
        $(input).val(codigo);

        const codigoNormalizado = normalizarHexadecimal(codigo);
        const codigoSelector = hexadecimalParaSelector(codigoNormalizado);
        const $preview = $(preview);

        if (!codigoSelector) {
            $preview
                .addClass('is-incomplete')
                .text(codigoNormalizado || 'Código incompleto')
                .attr('aria-label', 'Código hexadecimal incompleto');
            return;
        }

        $(picker).val(codigoSelector);
        $preview
            .removeClass('is-incomplete')
            .text(codigoNormalizado)
            .css('background-color', codigoSelector)
            .attr('aria-label', 'Vista previa ' + codigoNormalizado);
        aplicarContraste($preview, codigoSelector);
    }

    function identificarColor(valor) {
        const hex = normalizarHexadecimal(valor).slice(1);
        let completo = hex;

        if (/^[0-9A-F]{3}$/.test(hex)) {
            completo = hex.split('').map(function (digito) {
                return digito + digito;
            }).join('');
        }
        if (!/^[0-9A-F]{6}$/.test(completo)) return null;

        const rgb = completo.match(/.{2}/g).map(function (canal) {
            return parseInt(canal, 16) / 255;
        });
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
        const texto = data.mensaje || (xhr.status === 404
            ? 'No se encontró el color solicitado.'
            : (xhr.status === 419 ? 'La sesión ha expirado. Recargue la página.' : 'No se pudo completar la operación.'));
        mensaje(texto, true);
    }

    function mostrarErrores(errores) {
        window.RSUValidation.show(errores);
    }

    function validarFormulario($form) {
        const $nombre = $form.find('[name="name"]');
        const $codigo = $form.find('[name="code"]');
        const $descripcion = $form.find('[name="description"]');
        const nombre = String($nombre.val() || '').trim();
        const codigo = normalizarHexadecimal($codigo.val());
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
            mostrarErrores(errores);
            return false;
        }

        return true;
    }

    function manejarErrorFormulario(xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            mostrarErrores(xhr.responseJSON.errors);
            return;
        }
        errorMensaje(xhr);
    }

    function actualizarContador(textarea) {
        $('[data-counter-for="' + textarea.id + '"]').text(String(textarea.value.length));
    }

    function fechaLatinoamericana(valor) {
        if (!valor) return 'Sin fecha';
        const fecha = new Date(String(valor).replace(' ', 'T'));
        if (Number.isNaN(fecha.getTime())) return valor;

        return new Intl.DateTimeFormat('es-419', {
            dateStyle: 'short',
            timeStyle: 'medium'
        }).format(fecha);
    }

    function completarDetalle(color) {
        const codigo = normalizarHexadecimal(color.code);
        const selector = hexadecimalParaSelector(codigo);
        $('#verColorName').text(color.name);
        $('#verColorCode').text(codigo);
        $('#verColorDescription').text(color.description || 'Sin descripción');
        $('#verColorCreatedAt').text(fechaLatinoamericana(color.created_at));
        $('#verColorUpdatedAt').text(fechaLatinoamericana(color.updated_at));

        const $preview = $('#verColorPreview')
            .text(codigo)
            .attr('aria-label', 'Vista previa ' + codigo);
        if (selector) {
            $preview.css('background-color', selector);
            aplicarContraste($preview, selector);
        }
    }

    $(function () {
        const sugerenciasCrear = { nombre: '', descripcion: '' };
        const sugerenciasEditar = { nombre: '', descripcion: '' };

        tabla = $('#tablaColores').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 350,
            ajax: endpoint,
            columns: [
                { data: 'preview', name: 'code', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
                { data: 'description', name: 'description', defaultContent: '' },
                { data: 'created_at', name: 'created_at', defaultContent: '', render: fechaLatinoamericana },
                { data: 'updated_at', name: 'updated_at', defaultContent: '', render: fechaLatinoamericana },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
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
            const $form = $('#formCrearColor');
            $form[0].reset();
                sugerenciasCrear.nombre = '';
            sugerenciasCrear.descripcion = '';
            $('#crearCode, #crearPicker').val('#000000');
            actualizarContador(document.getElementById('crearDescription'));
            actualizarVistaPrevia('#crearCode', '#crearPicker', '#crearPreview');
            mostrarModal('#modalCrearColor');
        });

        $('#crearCode').on('input', function () {
            actualizarVistaPrevia('#crearCode', '#crearPicker', '#crearPreview');
            sugerirNombreYDescripcion('#crearCode', '#crearName', '#crearDescription', sugerenciasCrear);
        }).on('blur', function () {
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
        }).on('blur', function () {
            actualizarVistaPrevia('#editarCode', '#editarPicker', '#editarPreview', true);
        });

        $('#editarPicker').on('input change', function () {
            $('#editarCode').val(this.value.toUpperCase());
            actualizarVistaPrevia('#editarCode', '#editarPicker', '#editarPreview');
            sugerirNombreYDescripcion('#editarCode', '#editarName', '#editarDescription', sugerenciasEditar);
        });

        $('#formCrearColor, #formEditarColor').on('input change', 'input, textarea', function () {
            if (this.tagName === 'TEXTAREA') actualizarContador(this);
        });

        $('#crearDescription, #editarDescription').each(function () {
            actualizarContador(this);
        });

        $('#formCrearColor').on('submit', function (event) {
            event.preventDefault();
            const $form = $(this);
            if (!validarFormulario($form)) return;
            const $submit = $form.find('[type="submit"]').prop('disabled', true);

            $.ajax({ url: endpoint, method: 'POST', data: $form.serialize() })
                .done(function (response) {
                    ocultarModal('#modalCrearColor');
                    tabla.ajax.reload(null, false);
                    mensaje(response.mensaje || 'Color creado correctamente.');
                })
                .fail(function (xhr) {
                    manejarErrorFormulario(xhr);
                })
                .always(function () {
                    $submit.prop('disabled', false);
                });
        });

        $('#tablaColores').on('click', '.btnVer', function () {
            $.ajax({ url: $(this).data('url'), method: 'GET' })
                .done(function (color) {
                    completarDetalle(color);
                    mostrarModal('#modalVerColor');
                })
                .fail(errorMensaje);
        });

        $('#tablaColores').on('click', '.btnEditar', function () {
            const $button = $(this);
            $.ajax({ url: $button.data('url'), method: 'GET' })
                .done(function (color) {
                    const $form = $('#formEditarColor');
                                const tipoActual = identificarColor(color.code);
                    sugerenciasEditar.nombre = tipoActual && color.name === tipoActual.name ? tipoActual.name : '';
                    sugerenciasEditar.descripcion = tipoActual && color.description === tipoActual.description ? tipoActual.description : '';
                    $form.data('update-url', $button.attr('data-update-url'));
                    $('#editarId').val(color.id);
                    $('#editarName').val(color.name);
                    $('#editarCode').val(normalizarHexadecimal(color.code));
                    $('#editarDescription').val(color.description || '');
                    actualizarContador(document.getElementById('editarDescription'));
                    actualizarVistaPrevia('#editarCode', '#editarPicker', '#editarPreview');
                    mostrarModal('#modalEditarColor');
                })
                .fail(errorMensaje);
        });

        $('#formEditarColor').on('submit', function (event) {
            event.preventDefault();
            const $form = $(this);
            if (!validarFormulario($form)) return;
            const $submit = $form.find('[type="submit"]').prop('disabled', true);

            $.ajax({ url: $form.data('update-url'), method: 'PUT', data: $form.serialize() })
                .done(function (response) {
                    ocultarModal('#modalEditarColor');
                    tabla.ajax.reload(null, false);
                    mensaje(response.mensaje || 'Color actualizado correctamente.');
                })
                .fail(function (xhr) {
                    manejarErrorFormulario(xhr);
                })
                .always(function () {
                    $submit.prop('disabled', false);
                });
        });

        $('#tablaColores').on('click', '.btnEliminar', function () {
            const url = $(this).data('url');
            const eliminar = function () {
                $.ajax({ url: url, method: 'DELETE' })
                    .done(function (response) {
                        tabla.ajax.reload(null, false);
                        mensaje(response.mensaje || 'Color eliminado correctamente.');
                    })
                    .fail(errorMensaje);
            };

            Swal.fire({
                title: '¿Eliminar este color?',
                text: 'Esta acción no se puede revertir.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then(function (result) {
                if (result.isConfirmed) eliminar();
            });
        });
    });
})(jQuery);
