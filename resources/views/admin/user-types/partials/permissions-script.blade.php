<script>
(() => {
    const form = document.getElementById('permissionMatrixForm');
    if (!form) return;
    const options = [...form.querySelectorAll('.permission-option')];
    const selectors = [...form.querySelectorAll('.permission-select-all')];
    const count = items => items.filter(item => item.checked).length;
    const refresh = () => {
        selectors.forEach(control => {
            const scoped = control.dataset.scope === 'all' ? options : [...form.querySelectorAll('.' + control.dataset.scope)];
            const selected = count(scoped);
            control.checked = scoped.length > 0 && selected === scoped.length;
            control.indeterminate = selected > 0 && selected < scoped.length;
        });
        form.querySelectorAll('[data-module-card]').forEach(card => {
            const moduleOptions = [...card.querySelectorAll('.permission-option')];
            card.querySelector('[data-module-selected]').textContent = count(moduleOptions);
            card.dataset.configured = count(moduleOptions) > 0 ? '1' : '0';
        });
        form.querySelectorAll('[data-submodule-card]').forEach(card => {
            card.querySelector('[data-submodule-selected]').textContent = count([...card.querySelectorAll('.permission-option')]);
        });
        const selected = count(options);
        const percent = options.length ? Math.round(selected * 100 / options.length) : 0;
        document.querySelector('[data-permission-selected]').textContent = selected;
        document.querySelector('[data-sticky-count]').textContent = selected;
        document.querySelector('[data-module-count]').textContent = form.querySelectorAll('[data-module-card][data-configured="1"]').length;
        document.querySelector('[data-permission-percent]').textContent = percent + '%';
        const progress = document.querySelector('[data-permission-progress]');
        progress.style.width = percent + '%';
        progress.setAttribute('aria-valuenow', percent);
    };
    selectors.forEach(control => control.addEventListener('change', () => {
        const scoped = control.dataset.scope === 'all' ? options : [...form.querySelectorAll('.' + control.dataset.scope)];
        scoped.forEach(option => option.checked = control.checked);
        refresh();
    }));
    options.forEach(option => option.addEventListener('change', refresh));
    refresh();
})();
</script>
