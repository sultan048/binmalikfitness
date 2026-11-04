// Alpine components used with the CSP build (@alpinejs/csp).
// The CSP build cannot evaluate inline functions, getters or globals (window, Math, JSON),
// so any logic beyond simple expressions lives here. Server data is passed via data-* attributes.

function readJson(el, key, fallback) {
    try {
        return JSON.parse(el.dataset[key] ?? '') ?? fallback;
    } catch (_) {
        return fallback;
    }
}

export default function registerComponents(Alpine) {
    /**
     * Tabbed dashboard shell (customer, manager and admin dashboards).
     * data-tabs: ["overview", ...]  data-tab-labels: {"overview": "Overview & Today", ...}
     * Panels carry data-tab-panel="<key>"; nav buttons call setTab('<key>').
     */
    Alpine.data('dashboardTabs', () => ({
        sidebarOpen: false,
        tabs: [],
        labels: {},
        tab: null,

        init() {
            this.tabs = readJson(this.$el, 'tabs', []);
            this.labels = readJson(this.$el, 'tabLabels', {});

            if (!this.tabs.length) {
                return;
            }

            this.$el.querySelectorAll('[data-tab-panel]').forEach((panel) => {
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('aria-labelledby', 'tab-btn-' + panel.dataset.tabPanel);
            });

            const id = location.hash.slice(1);
            this.tab = this.tabFor(id) || this.stored() || this.tabs[0];
            this.$nextTick(() => this.reveal(id));

            window.addEventListener('hashchange', () => {
                const hashId = location.hash.slice(1);
                const key = this.tabFor(hashId);
                if (key) {
                    this.tab = key;
                    this.$nextTick(() => this.reveal(hashId));
                }
            });
        },

        get currentLabel() {
            return this.labels[this.tab] || '';
        },

        isTab(key) {
            return this.tab === key;
        },

        // Accepts a tab key or the id of any element inside a panel (keeps old #section links working)
        tabFor(id) {
            if (!id) {
                return null;
            }
            if (this.tabs.includes(id)) {
                return id;
            }
            const key = document.getElementById(id)?.closest('[data-tab-panel]')?.dataset.tabPanel;

            return this.tabs.includes(key) ? key : null;
        },

        reveal(id) {
            if (id && !this.tabs.includes(id)) {
                document.getElementById(id)?.scrollIntoView();
            }
        },

        stored() {
            try {
                const key = sessionStorage.getItem('bmf-tab:' + location.pathname);

                return this.tabs.includes(key) ? key : null;
            } catch (_) {
                return null;
            }
        },

        setTab(key) {
            if (!this.tabs.includes(key)) {
                return;
            }
            this.tab = key;
            this.sidebarOpen = false;
            try {
                sessionStorage.setItem('bmf-tab:' + location.pathname, key);
            } catch (_) {
                // Storage unavailable (private mode); the hash still records the tab
            }
            history.replaceState(null, '', '#' + key);
            window.scrollTo({ top: 0 });
        },

        // Arrow-key navigation between sidebar tabs (WAI-ARIA tabs pattern, vertical)
        moveTab(step) {
            const index = this.tabs.indexOf(this.tab);
            const next = this.tabs[(index + step + this.tabs.length) % this.tabs.length];
            this.setTab(next);
            this.$nextTick(() => document.getElementById('tab-btn-' + next)?.focus());
        },

        firstTab() {
            this.setTab(this.tabs[0]);
            this.$nextTick(() => document.getElementById('tab-btn-' + this.tabs[0])?.focus());
        },

        lastTab() {
            const last = this.tabs[this.tabs.length - 1];
            this.setTab(last);
            this.$nextTick(() => document.getElementById('tab-btn-' + last)?.focus());
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
    }));

    /**
     * Product page variant picker.
     * data-variants: [{id, sku, price_formatted, compare_formatted, available, ...}]  data-selected: id
     */
    Alpine.data('productVariants', () => ({
        variants: [],
        selectedId: null,
        qty: 1,

        init() {
            this.variants = readJson(this.$el, 'variants', []);
            const selected = Number(this.$el.dataset.selected);
            this.selectedId = selected || this.variants[0]?.id || null;
        },

        get current() {
            return this.variants.find((v) => v.id === Number(this.selectedId)) || this.variants[0] || {};
        },

        get maxQty() {
            return Math.max(1, Number(this.current.available) || 0);
        },

        get soldOut() {
            return (Number(this.current.available) || 0) <= 0;
        },
    }));

    /**
     * Checkout form toggles (saved address, shipping and payment method).
     */
    Alpine.data('checkoutForm', () => ({
        useSavedAddress: '',
        paymentMethod: 'cod',
        shippingMethod: 'standard_courier',

        init() {
            this.useSavedAddress = this.$el.dataset.savedAddress || '';
            this.paymentMethod = this.$el.dataset.paymentMethod || 'cod';
            this.shippingMethod = this.$el.dataset.shippingMethod || 'standard_courier';
        },
    }));

    /**
     * Floating WhatsApp button with consent-aware click attribution.
     */
    Alpine.data('whatsappFloat', () => ({
        consentGranted: false,
        panelOpen: false,

        init() {
            try {
                this.consentGranted = localStorage.getItem('bmf_consent_analytics') === 'granted';
            } catch (_) {
                // Storage unavailable
            }
        },

        toggleConsent() {
            this.consentGranted = !this.consentGranted;
            try {
                localStorage.setItem('bmf_consent_analytics', this.consentGranted ? 'granted' : 'denied');
            } catch (_) {
                // Storage unavailable
            }
        },

        trackClick() {
            if (window.bmfTrack) {
                window.bmfTrack('whatsapp_contact_click', { location: window.location.pathname });
            }
        },
    }));
}
