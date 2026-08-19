<script data-request-feedback>
(() => {
    if (window.requestFeedbackInitialized) {
        return;
    }

    window.requestFeedbackInitialized = true;

    const activeForms = new Map();
    const activeLinks = new Map();
    const submitSelector = 'button[type="submit"], input[type="submit"], button:not([type])';
    const liveRegion = document.createElement('div');

    liveRegion.className = 'visually-hidden';
    liveRegion.setAttribute('role', 'status');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    document.body.appendChild(liveRegion);

    const escapeHtml = (text) => {
        const element = document.createElement('span');
        element.textContent = text;

        return element.innerHTML;
    };

    const loadingMarkup = (text) => [
        '<span class="spinner-border spinner-border-sm request-feedback-spinner" aria-hidden="true"></span>',
        `<span>${escapeHtml(text)}</span>`,
    ].join(' ');

    const controlState = (control) => ({
        control,
        disabled: control.disabled,
        ariaDisabled: control.getAttribute('aria-disabled'),
        html: control instanceof HTMLButtonElement ? control.innerHTML : null,
        value: control instanceof HTMLInputElement ? control.value : null,
        minWidth: control.style.minWidth,
    });

    const setControlLoading = (control, text) => {
        const width = control.getBoundingClientRect().width;

        if (width > 0) {
            control.style.minWidth = `${Math.ceil(width)}px`;
        }

        if (control instanceof HTMLButtonElement) {
            control.innerHTML = loadingMarkup(text);
        } else if (control instanceof HTMLInputElement) {
            control.value = text;
        }

        control.classList.add('request-feedback-loading');
    };

    const restoreControl = (state) => {
        const {control} = state;

        control.disabled = state.disabled;
        control.style.minWidth = state.minWidth;
        control.classList.remove('request-feedback-loading');

        if (state.ariaDisabled === null) {
            control.removeAttribute('aria-disabled');
        } else {
            control.setAttribute('aria-disabled', state.ariaDisabled);
        }

        if (control instanceof HTMLButtonElement) {
            control.innerHTML = state.html;
        } else if (control instanceof HTMLInputElement) {
            control.value = state.value;
        }
    };

    const restoreForm = (form) => {
        const state = activeForms.get(form);

        if (!state) {
            return;
        }

        state.controls.forEach(restoreControl);
        state.statuses.forEach(({element, wasHidden}) => {
            element.hidden = wasHidden;
        });

        form.removeAttribute('aria-busy');
        delete form.dataset.loadingActive;
        activeForms.delete(form);

        if (activeForms.size === 0 && activeLinks.size === 0) {
            liveRegion.textContent = '';
        }
    };

    const startForm = (form, submitter) => {
        const controls = [...form.querySelectorAll(submitSelector)].map(controlState);
        const statuses = [...form.querySelectorAll('[data-loading-status]')].map((status) => ({
            element: status,
            wasHidden: status.hidden,
        }));
        const loadingText = submitter?.dataset.loadingText
            || form.dataset.loadingText
            || 'Aguarde...';

        activeForms.set(form, {
            controls,
            statuses,
        });

        form.dataset.loadingActive = 'true';
        form.setAttribute('aria-busy', 'true');

        if (submitter) {
            setControlLoading(submitter, loadingText);
        }

        controls.forEach(({control}) => {
            control.disabled = true;
            control.setAttribute('aria-disabled', 'true');
        });

        statuses.forEach(({element}) => {
            element.hidden = false;
        });

        liveRegion.textContent = loadingText;

        const resetAfter = Number(form.dataset.loadingResetAfter || 0);
        if (resetAfter > 0) {
            window.setTimeout(() => restoreForm(form), resetAfter);
        }
    };

    const restoreLink = (link) => {
        const state = activeLinks.get(link);

        if (!state) {
            return;
        }

        link.innerHTML = state.html;
        link.style.minWidth = state.minWidth;
        link.classList.remove('request-feedback-loading', 'disabled');

        if (state.ariaDisabled === null) {
            link.removeAttribute('aria-disabled');
        } else {
            link.setAttribute('aria-disabled', state.ariaDisabled);
        }

        delete link.dataset.loadingActive;
        activeLinks.delete(link);

        if (activeForms.size === 0 && activeLinks.size === 0) {
            liveRegion.textContent = '';
        }
    };

    const startLink = (link) => {
        const loadingText = link.dataset.loadingText || 'Preparando arquivo...';
        const width = link.getBoundingClientRect().width;

        activeLinks.set(link, {
            html: link.innerHTML,
            minWidth: link.style.minWidth,
            ariaDisabled: link.getAttribute('aria-disabled'),
        });

        if (width > 0) {
            link.style.minWidth = `${Math.ceil(width)}px`;
        }

        link.innerHTML = loadingMarkup(loadingText);
        link.classList.add('request-feedback-loading', 'disabled');
        link.dataset.loadingActive = 'true';
        link.setAttribute('aria-disabled', 'true');
        liveRegion.textContent = loadingText;

        if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-loading-reset-after')) {
            window.setTimeout(() => restoreLink(link), Number(link.dataset.loadingResetAfter || 2500));
        }
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.hasAttribute('data-loading-ignore')) {
            return;
        }

        if (form.dataset.loadingActive === 'true') {
            event.preventDefault();
            return;
        }

        if (event.submitter instanceof HTMLElement && event.submitter.hasAttribute('data-loading-ignore')) {
            return;
        }

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;

        startForm(form, submitter);
    });

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element
            ? event.target.closest('a[data-server-action]')
            : null;

        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        if (link.dataset.loadingActive === 'true') {
            event.preventDefault();
            return;
        }

        startLink(link);
    });

    window.addEventListener('pageshow', () => {
        [...activeForms.keys()].forEach(restoreForm);
        [...activeLinks.keys()].forEach(restoreLink);
        liveRegion.textContent = '';
    });
})();
</script>
