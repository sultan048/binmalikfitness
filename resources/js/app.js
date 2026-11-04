// CSP build: evaluates Alpine expressions without eval/new Function, so the
// Content-Security-Policy can drop 'unsafe-eval' and 'unsafe-inline' for scripts.
import Alpine from '@alpinejs/csp';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import registerComponents from './components';

Alpine.plugin(collapse);
Alpine.plugin(focus);
registerComponents(Alpine);

window.Alpine = Alpine;

// Consent-aware tracking helper for WhatsApp & CTA events
window.bmfTrack = function (eventName, payload = {}) {
    try {
        const consent = localStorage.getItem('bmf_consent_analytics');
        if (consent === 'granted') {
            window.dispatchEvent(new CustomEvent('bmf:analytics', {
                detail: { event: eventName, payload, timestamp: new Date().toISOString() },
            }));
        }
    } catch (_) {
        // Storage unavailable; fail silently without blocking user navigation
    }
};

// Print buttons (e.g. invoice "Print / Save PDF") without inline onclick handlers
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-print]')) {
        event.preventDefault();
        window.print();
    }
});

Alpine.start();
