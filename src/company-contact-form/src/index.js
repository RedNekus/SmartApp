/**
 * Company Contact Form — Block + Frontend logic
 */
import './jsx-shim'; 
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.scss';

/* ---------------------------------------------------------------------
 * Gutenberg block registration (EDITOR ONLY)
 * ------------------------------------------------------------------ */
    if (typeof window !== 'undefined' && window.wp && window.wp.blockEditor) {
    registerBlockType(metadata.name, {
        ...metadata,
        edit: Edit,
        save,
    });
}

/* ---------------------------------------------------------------------
 * Frontend logic (FORM HANDLER)
 * ------------------------------------------------------------------ */

(function () {
    // Guard: frontend only
    if (typeof document === 'undefined') {
        return;
    }

    const FORM_SELECTOR = '.ccf-form';
    const HONEYPOT_NAME = 'ccf_company'; // must NOT exist for humans
    const TIME_FIELD   = 'ccf_ts';
    const MIN_TIME_MS  = 3000; // 3 seconds

    /**
     * Inject anti-spam fields into form
     */
    function injectAntiSpam(form) {
        // Honeypot
        if (!form.querySelector(`[name="${HONEYPOT_NAME}"]`)) {
            const honeypot = document.createElement('input');
            honeypot.type = 'text';
            honeypot.name = HONEYPOT_NAME;
            honeypot.tabIndex = -1;
            honeypot.autocomplete = 'off';
            honeypot.style.position = 'absolute';
            honeypot.style.left = '-9999px';
            form.appendChild(honeypot);
        }

        // Time trap
        if (!form.querySelector(`[name="${TIME_FIELD}"]`)) {
            const ts = document.createElement('input');
            ts.type = 'hidden';
            ts.name = TIME_FIELD;
            ts.value = Date.now().toString();
            form.appendChild(ts);
        }
    }

    /**
     * Handle submit
     */
    async function handleSubmit(event) {
        const form = event.target;

        if (!form.matches(FORM_SELECTOR)) {
            return;
        }

        event.preventDefault();

        const responseEl = form.querySelector('.ccf-response');
        if (responseEl) {
            responseEl.textContent = '';
        }

        const formData = new FormData(form);

        // Frontend time-trap check (defense in depth)
        const ts = parseInt(formData.get(TIME_FIELD), 10);
        if (!ts || Date.now() - ts < MIN_TIME_MS) {
            if (responseEl) {
                responseEl.textContent = 'Please wait a moment before submitting.';
            }
            return;
        }

        try {
            const res = await fetch(ccfSettings.apiUrl, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': ccfSettings.nonce,
                },
                body: formData,
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.message || 'Submission failed.');
            }

            if (responseEl) {
                responseEl.textContent = data.message || 'Message sent successfully.';
            }

            form.reset();
            injectAntiSpam(form); // re-inject after reset
        } catch (error) {
            if (responseEl) {
                responseEl.textContent = error.message;
            }
        }
    }

    /**
     * Init on DOM ready
     */
    function init() {
        document.querySelectorAll(FORM_SELECTOR).forEach(injectAntiSpam);
        document.addEventListener('submit', handleSubmit);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
