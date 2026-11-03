<x-layouts.public
    title="Brand Design System — BIN MALIK FITNESS"
    description="Design tokens, color palette, WCAG 2.2 AA contrast pairs, typography scale, and reusable Blade UI components."
>
    <section class="section-y border-b border-white/10 bg-ink">
        <div class="container-site space-y-10">
            <x-ui.section-heading
                eyebrow="Stage 1 Design Foundation · Athletic Editorial Identity"
                title="BIN MALIK FITNESS Design System"
                subtitle="Masculine, disciplined, contemporary visual identity built on deep charcoal/black, warm off-white ecommerce surfaces, and flat gold accents (~10% budget)."
                surface="dark"
            />

            {{-- Color Palette Swatches --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                @foreach ([
                    ['name' => '--brand-black', 'hex' => '#0B0B0C', 'bg' => 'bg-ink border-white/20', 'text' => 'text-ivory'],
                    ['name' => '--brand-charcoal', 'hex' => '#1A1A1D', 'bg' => 'bg-charcoal border-white/20', 'text' => 'text-ivory'],
                    ['name' => '--brand-gold', 'hex' => '#F2B705', 'bg' => 'bg-gold border-gold', 'text' => 'text-ink'],
                    ['name' => '--brand-ivory', 'hex' => '#F5F5F4', 'bg' => 'bg-ivory border-white/20', 'text' => 'text-ink'],
                    ['name' => '--brand-silver', 'hex' => '#B4B4BB', 'bg' => 'bg-silver border-white/20', 'text' => 'text-ink'],
                    ['name' => '--shop-background', 'hex' => '#F7F5F0', 'bg' => 'bg-paper border-graphite/20', 'text' => 'text-graphite'],
                    ['name' => '--shop-text', 'hex' => '#1F1F22', 'bg' => 'bg-graphite border-white/20', 'text' => 'text-ivory'],
                    ['name' => '--gold-dark', 'hex' => '#7A5C00', 'bg' => 'bg-gold-dark border-gold', 'text' => 'text-ivory'],
                    ['name' => '--success', 'hex' => '#15803D', 'bg' => 'bg-success border-success', 'text' => 'text-white'],
                    ['name' => '--danger', 'hex' => '#DC2626', 'bg' => 'bg-danger border-danger', 'text' => 'text-white'],
                ] as $token)
                    <div class="rounded-sm border p-4 {{ $token['bg'] }} {{ $token['text'] }}">
                        <p class="font-mono text-xs font-bold">{{ $token['name'] }}</p>
                        <p class="mt-4 font-display text-2xl">{{ $token['hex'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Typography & Dark Surface Buttons --}}
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-4">
                    <span class="eyebrow text-gold">Typography — Bebas Neue + Inter</span>
                    <p class="display-l text-ivory">Bebas Neue Display Headline</p>
                    <p class="text-base text-silver leading-relaxed">
                        Inter Variable for navigation, body copy, product nutrition facts, and operational dashboards with <span class="tabular-nums font-semibold text-ivory">PKR 24,500</span> tabular numerals.
                    </p>
                </div>

                <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-4">
                    <span class="eyebrow text-gold">Buttons on Dark Surfaces (WCAG AAA 10.8:1)</span>
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button variant="gold">Primary on Dark (Gold)</x-ui.button>
                        <x-ui.button variant="outline-light">Secondary Outline</x-ui.button>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <x-ui.badge tone="gold">Gold Badge</x-ui.badge>
                        <x-ui.badge tone="dark">Dark Badge</x-ui.badge>
                        <x-ui.badge tone="success">Verified</x-ui.badge>
                        <x-ui.badge tone="danger">Warning / Error</x-ui.badge>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Light Surface Component Verification --}}
    <section class="section-y surface-paper">
        <div class="container-site space-y-6">
            <x-ui.section-heading
                eyebrow="Warm Off-White Surface (#F7F5F0) · Accessible Dark-Gold Token (#7A5C00)"
                title="Ecommerce & Light Surface Components"
                subtitle="Primary buttons on light surfaces use graphite (#1F1F22) with gold text (9.1:1 AAA). Gold body/eyebrow text on light surfaces uses --gold-dark (#7A5C00, 5.7:1 AA)."
                surface="light"
            />
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="graphite">Primary on Light (Graphite + Gold)</x-ui.button>
                <x-ui.button variant="outline-dark">Secondary on Light</x-ui.button>
            </div>
        </div>
    </section>
</x-layouts.public>
