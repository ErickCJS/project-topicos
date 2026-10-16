(function (window, document) {
    'use strict';

    const labels = {
        email: 'Correo electrónico', name: 'Nombre', password: 'Contraseña',
        code: 'Código', description: 'Descripción', photo: 'Foto', photos: 'Fotografías',
        current_password: 'Contraseña actual', password_confirmation: 'Confirmación de contraseña',
        tipo_personal_id: 'Tipo de personal', fecha_nacimiento: 'Fecha de nacimiento',
        dni: 'DNI', nombres: 'Nombres', apellidos: 'Apellidos', direccion: 'Dirección',
        telefono: 'Teléfono', licencia: 'Licencia', estado: 'Estado',
        employee_id: 'Empleado', employee_display: 'Empleado', brand_id: 'Marca',
        model_id: 'Modelo', type_id: 'Tipo', color_id: 'Color', load_capacity: 'Capacidad de carga',
        compact_capacity: 'Capacidad compactadora', fuel_capacity: 'Capacidad de combustible',
        occupats_capacity: 'Capacidad de ocupantes', profile_photo_path: 'Foto de perfil',
    };

    function labelFor(field) {
        return labels[field] || field.replace(/[_\.]/g, ' ').replace(/\b\w/g, function (letter) {
            return letter.toLocaleUpperCase();
        });
    }

    function show(errors, title) {
        if (!window.Swal) return;

        const list = document.createElement('ul');
        list.className = 'rsu-validation-list';

        Object.entries(errors || {}).forEach(function (entry) {
            const field = entry[0];
            const messages = Array.isArray(entry[1]) ? entry[1] : [entry[1]];

            messages.filter(Boolean).forEach(function (message) {
                const item = document.createElement('li');
                if (field !== 'general') {
                    const label = document.createElement('strong');
                    label.textContent = labelFor(field) + ': ';
                    item.appendChild(label);
                }
                item.appendChild(document.createTextNode(String(message)));
                list.appendChild(item);
            });
        });

        if (!list.childElementCount) return;

        return window.Swal.fire({
            icon: 'error',
            title: title || 'Revisa los datos ingresados',
            html: list.outerHTML,
            confirmButtonText: 'Entendido',
            width: 540
        });
    }

    window.RSUValidation = {
        show: show,
        error: function (message, title) {
            return show({ general: [message] }, title || 'Ocurrió un error');
        },
        success: function (message, title) {
            if (!window.Swal || !message) return;

            return window.Swal.fire({
                icon: 'success',
                title: title || 'Operación exitosa',
                text: message,
                confirmButtonText: 'Entendido',
                width: 540
            });
        }
    };

    function disableNativeValidation(root) {
        const forms = [];
        if (root.matches && root.matches('form')) forms.push(root);
        forms.push.apply(forms, root.querySelectorAll ? root.querySelectorAll('form') : []);
        forms.forEach(function (form) {
            form.noValidate = true;
        });
    }

    disableNativeValidation(document);
    new MutationObserver(function (records) {
        records.forEach(function (record) {
            record.addedNodes.forEach(function (node) {
                if (node.nodeType === Node.ELEMENT_NODE) disableNativeValidation(node);
            });
        });
    }).observe(document.documentElement, {
        childList: true,
        subtree: true
    });

    new MutationObserver(function (records) {
        const errors = {};
        records.forEach(function (record) {
            const candidates = [];
            if (record.target.nodeType === Node.ELEMENT_NODE && record.target.matches('.rsu-livewire-validation-error')) {
                candidates.push(record.target);
            } else if (record.target.parentElement) {
                const marker = record.target.parentElement.closest('.rsu-livewire-validation-error');
                if (marker) candidates.push(marker);
            }
            record.addedNodes.forEach(function (node) {
                if (node.nodeType !== Node.ELEMENT_NODE) return;
                if (node.matches('.rsu-livewire-validation-error')) candidates.push(node);
                candidates.push.apply(candidates, node.querySelectorAll('.rsu-livewire-validation-error'));
            });
            candidates.forEach(function (marker) {
                const field = marker.dataset.validationField;
                const message = marker.textContent.trim();
                if (field && message) (errors[field] = errors[field] || []).push(message);
            });
        });
        if (Object.keys(errors).length) show(errors);
    }).observe(document.documentElement, {
        childList: true,
        characterData: true,
        subtree: true
    });

    if (window.RSUInitialValidationErrors) {
        show(window.RSUInitialValidationErrors);
        window.RSUInitialValidationErrors = null;
    }
})(window, document);
