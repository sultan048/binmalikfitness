import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

Alpine.plugin(collapse);
Alpine.plugin(focus);

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

Alpine.start();
