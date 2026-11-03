@php
    $waNumber = \App\Models\Setting::getValue('gym.whatsapp', '923000000000');
@endphp

<div
    x-data="{
        consentGranted: false,
        panelOpen: false,
        init() {
            try {
                this.consentGranted = localStorage.getItem('bmf_consent_analytics') === 'granted';
            } catch (_) {}
        },
        toggleConsent() {
            this.consentGranted = !this.consentGranted;
            try {
                localStorage.setItem('bmf_consent_analytics', this.consentGranted ? 'granted' : 'denied');
            } catch (_) {}
        }
    }"
    class="fixed right-5 bottom-5 z-50 flex flex-col items-end gap-2"
>
    <div
        x-show="panelOpen"
        x-cloak
        class="w-72 rounded-sm border border-white/15 bg-charcoal p-4 text-xs text-ivory shadow-xl"
    >
        <div class="flex items-center justify-between">
            <span class="font-semibold uppercase tracking-wider text-gold">WhatsApp Concierge</span>
            <button type="button" @click="panelOpen = false" class="text-silver hover:text-ivory" aria-label="Close WhatsApp options">✕</button>
        </div>
        <p class="mt-1.5 text-silver leading-relaxed">
            Questions about online coaching intake, bank transfer verification, or supplement stock? Message our team directly.
        </p>
        <label class="mt-3 flex items-start gap-2 text-[11px] text-silver cursor-pointer">
            <input type="checkbox" :checked="consentGranted" @change="toggleConsent()" class="mt-0.5 accent-gold" />
            <span>Allow anonymous click attribution when opening WhatsApp (consent-aware tracking).</span>
        </label>
        <a
            href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Assalam o Alaikum BIN MALIK FITNESS — I would like guidance on coaching / supplements.') }}"
            target="_blank"
            rel="noopener noreferrer"
            @click="window.bmfTrack && window.bmfTrack('whatsapp_contact_click', { location: window.location.pathname })"
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-xs bg-gold px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ink hover:bg-gold/90"
        >
            Open WhatsApp Chat
        </a>
    </div>

    <button
        type="button"
        @click="panelOpen = !panelOpen"
        aria-label="WhatsApp Contact & Coaching Support"
        class="inline-flex items-center gap-2.5 rounded-xs border border-gold bg-charcoal px-4 py-3 text-xs font-semibold uppercase tracking-wider text-ivory shadow-lg hover:bg-ink"
    >
        <span class="h-2.5 w-2.5 rounded-full bg-gold" aria-hidden="true"></span>
        <span>WhatsApp Support</span>
    </button>
</div>
