@php
    $defaultVariant = $product->defaultVariant ?? $product->variants->first();
    $primaryImg = $product->primaryImage?->path ?? '/images/demo/product-whey-800.webp';
    $seoService = app(\App\Domain\Platform\SeoAndStructuredDataService::class);
    $seoMeta = $seoService->resolveMetaForModel(
        model: $product,
        defaultTitle: $product->name.' — BIN MALIK FITNESS Shop',
        defaultDescription: (string) ($product->short_description ?? $product->subtitle ?? $product->name),
        defaultCanonical: route('products.show', $product->slug),
        defaultOgImage: url($primaryImg),
    );
    $breadcrumbItems = [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Shop', 'url' => route('shop.index')],
    ];
    if ($product->category) {
        $breadcrumbItems[] = ['name' => $product->category->name, 'url' => route('shop.index', ['category' => $product->category->slug])];
    }
    $breadcrumbItems[] = ['name' => $product->name, 'url' => route('products.show', $product->slug)];
    $productSchemas = [
        $seoService->productSchema($product),
        $seoService->breadcrumbSchema($breadcrumbItems),
    ];
@endphp

<x-layouts.public
    :title="$seoMeta['title']"
    :description="$seoMeta['description']"
    :canonical="$seoMeta['canonical']"
    :og-image="$seoMeta['og_image']"
    :robots="$seoMeta['robots']"
    :json-ld-schemas="$productSchemas"
>
    <section class="surface-paper py-10 sm:py-14 border-b border-graphite/12">
        <div class="container-site space-y-8">
            {{-- Breadcrumbs --}}
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-graphite/70">
                <a href="{{ route('home') }}" class="hover:underline">Home</a>
                <span>/</span>
                <a href="{{ route('shop.index') }}" class="hover:underline">Shop</a>
                @if ($product->category)
                    <span>/</span>
                    <a href="{{ route('shop.index', ['category' => $product->category->slug]) }}" class="hover:underline">{{ $product->category->name }}</a>
                @endif
                <span>/</span>
                <span class="font-semibold text-graphite">{{ $product->name }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                {{-- Gallery --}}
                <div class="lg:col-span-5">
                    <div class="overflow-hidden rounded-sm border border-graphite/15 bg-white p-6">
                        <img
                            src="{{ $primaryImg }}"
                            alt="{{ $product->name }}"
                            width="800"
                            height="800"
                            class="aspect-square w-full object-cover"
                        />
                    </div>
                </div>

                {{-- Details & Variant Picker --}}
                <div class="lg:col-span-7 space-y-6" x-data="{ selectedSku: '{{ $defaultVariant?->sku }}' }">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="eyebrow text-gold-dark">{{ $product->brand?->name ?? 'Unbranded Demo' }}</span>
                        <x-ui.demo-flag label="Demo Product Specification" />
                        <x-ui.badge tone="light">SKU: {{ $product->sku }}</x-ui.badge>
                    </div>

                    <h1 class="display-l text-graphite">{{ $product->name }}</h1>

                    @if ($product->subtitle)
                        <p class="text-base text-graphite/80">{{ $product->subtitle }}</p>
                    @endif

                    <div class="rounded-sm border border-graphite/15 bg-white p-5 space-y-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-4">
                            @if ($defaultVariant)
                                <x-ui.price :paisa="$defaultVariant->price" :compare-at-paisa="$defaultVariant->compare_at_price" surface="light" size="lg" />
                            @endif
                            <span class="text-xs font-semibold text-success">
                                ● Available Stock: {{ $defaultVariant?->availableStock() ?? 0 }} units (Atomic Reservation Protected)
                            </span>
                        </div>

                        <div>
                            <label for="variant-select" class="field-label text-graphite">Select Variant (Size / Flavor / Variant SKU)</label>
                            <select id="variant-select" x-model="selectedSku" class="field-input">
                                @foreach ($product->variants as $v)
                                    <option value="{{ $v->sku }}">
                                        {{ $v->size }} — {{ $v->flavor }} ({{ $v->sku }}) · {{ \App\Support\Money::formatPaisa($v->price) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 pt-2">
                            <x-ui.button :href="route('cart.index', ['add' => $product->slug])" variant="graphite" size="lg">
                                Add to Cart & Proceed
                            </x-ui.button>
                            <x-ui.button :href="route('shop.index')" variant="outline-dark" size="lg">
                                Continue Shopping
                            </x-ui.button>
                        </div>
                    </div>

                    {{-- Authenticity, Batch, Expiry & Regulatory Disclosure --}}
                    <div class="rounded-sm border border-graphite/15 bg-white p-5 space-y-3 text-xs">
                        <h2 class="font-bold uppercase tracking-wider text-gold-dark">Lot, Expiry & Regulatory Documentation Status</h2>
                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-graphite/60">Manufacturer</dt>
                                <dd class="font-semibold text-graphite">{{ $product->manufacturer }}</dd>
                            </div>
                            <div>
                                <dt class="text-graphite/60">Importer / Supply</dt>
                                <dd class="font-semibold text-graphite">{{ $product->importer }}</dd>
                            </div>
                            <div>
                                <dt class="text-graphite/60">Sample Batch / Expiry</dt>
                                <dd class="font-semibold text-graphite">
                                    Lot {{ $defaultVariant?->sample_lot_number }} · Exp {{ $defaultVariant?->sample_expiry_date?->format('M Y') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-graphite/60">Regulatory Documentation</dt>
                                <dd class="font-semibold text-graphite uppercase">{{ str_replace('_', ' ', $product->regulatory_status) }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Nutrition Facts, Ingredients, Usage, Allergens --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-sm border border-graphite/15 bg-white p-5 space-y-2 text-xs">
                            <h2 class="font-bold uppercase tracking-wider text-graphite">Nutrition Facts (Sample Panel)</h2>
                            @if (! empty($product->nutrition_facts))
                                <dl class="divide-y divide-graphite/10">
                                    @foreach ($product->nutrition_facts as $k => $v)
                                        <div class="flex justify-between py-1.5">
                                            <dt class="capitalize text-graphite/70">{{ str_replace('_', ' ', $k) }}</dt>
                                            <dd class="font-semibold text-graphite">{{ $v }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @else
                                <p class="text-graphite/70">Non-consumable gym accessory (no nutrition panel required).</p>
                            @endif
                        </div>

                        <div class="rounded-sm border border-graphite/15 bg-white p-5 space-y-3 text-xs">
                            <div>
                                <h3 class="font-bold uppercase tracking-wider text-graphite">Ingredients</h3>
                                <p class="mt-1 text-graphite/80">
                                    {{ ! empty($product->ingredients) ? implode(', ', $product->ingredients) : 'See physical product specification.' }}
                                </p>
                            </div>
                            <div>
                                <h3 class="font-bold uppercase tracking-wider text-graphite">Approved Label Usage</h3>
                                <p class="mt-1 text-graphite/80">{{ $product->usage_instructions }}</p>
                            </div>
                            <div>
                                <h3 class="font-bold uppercase tracking-wider text-danger">Warnings & Allergens</h3>
                                <p class="mt-1 text-graphite/80">{{ $product->warnings }} {{ $product->allergens }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Related Products --}}
            <div class="pt-8 border-t border-graphite/12 space-y-6">
                <h2 class="font-display text-3xl uppercase text-graphite">Related Products</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedProducts as $rel)
                        <x-shop.product-card :product="$rel" />
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
