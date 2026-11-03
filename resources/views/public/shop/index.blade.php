<x-layouts.public
    title="Supplement Shop — BIN MALIK FITNESS (PKR)"
    description="Whey protein, creatine, pre-workout, vitamins, and gym accessories in Pakistan with batch/lot and expiry tracking."
>
    {{-- Header banner on dark --}}
    <section class="border-b border-white/10 bg-ink py-12">
        <div class="container-site flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <x-ui.section-heading
                eyebrow="Ecommerce Storefront · 12 Configurable Categories"
                title="Supplements & Training Gear"
                subtitle="Every supplement listing tracks SKU, variant SKU, batch/lot number, expiry date, importer disclosure, and regulatory status."
                surface="dark"
            />
            <x-ui.demo-flag label="Demonstration Catalog · Sample Prices in PKR" />
        </div>
    </section>

    {{-- Main shop on warm off-white surface (#F7F5F0) --}}
    <section class="surface-paper py-12">
        <div class="container-site grid grid-cols-1 gap-8 lg:grid-cols-12">
            {{-- Filters sidebar --}}
            <aside aria-label="Catalog Filters" class="lg:col-span-3 space-y-6">
                <form method="GET" action="{{ route('shop.index') }}" class="rounded-sm border border-graphite/12 bg-white p-5 space-y-4">
                    <div>
                        <label for="shop-search" class="field-label text-graphite">Search SKU or Name</label>
                        <input
                            id="shop-search"
                            type="search"
                            name="q"
                            value="{{ $searchQuery }}"
                            placeholder="e.g. Whey, Creatine, Shaker"
                            class="field-input"
                        />
                    </div>

                    <div>
                        <label for="shop-brand" class="field-label text-graphite">Brand</label>
                        <select id="shop-brand" name="brand" class="field-input">
                            <option value="">All Brands</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->slug }}" @selected($activeBrand === $b->slug)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($activeCategory)
                        <input type="hidden" name="category" value="{{ $activeCategory }}" />
                    @endif

                    <div class="flex gap-2">
                        <x-ui.button type="submit" variant="graphite" size="sm" class="flex-1">Apply Filter</x-ui.button>
                        <x-ui.button :href="route('shop.index')" variant="outline-dark" size="sm">Reset</x-ui.button>
                    </div>
                </form>

                <div class="rounded-sm border border-graphite/12 bg-white p-5">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gold-dark">All 12 Product Categories</h2>
                    <ul class="mt-3 space-y-1.5 text-xs">
                        <li>
                            <a href="{{ route('shop.index') }}" class="flex items-center justify-between rounded-xs px-2.5 py-1.5 {{ $activeCategory === '' ? 'bg-graphite text-gold font-semibold' : 'text-graphite hover:bg-paper' }}">
                                <span>All Categories</span>
                                <span>{{ $categories->count() }}</span>
                            </a>
                        </li>
                        @foreach ($categories as $cat)
                            <li>
                                <a
                                    href="{{ route('shop.index', ['category' => $cat->slug]) }}"
                                    class="flex items-center justify-between rounded-xs px-2.5 py-1.5 {{ $activeCategory === $cat->slug ? 'bg-graphite text-gold font-semibold' : 'text-graphite hover:bg-paper' }}"
                                >
                                    <span>{{ $cat->name }}</span>
                                    <span class="text-[10px] opacity-70">→</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            {{-- Product Grid --}}
            <div class="lg:col-span-9 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-sm border border-graphite/12 bg-white px-5 py-3.5 text-xs">
                    <p class="font-medium text-graphite">
                        Showing <strong>{{ $products->count() }}</strong> active demonstration products
                        @if ($activeCategory)
                            in category <strong class="uppercase text-gold-dark">{{ $activeCategory }}</strong>
                        @endif
                    </p>
                    <div class="flex items-center gap-2 text-graphite/70">
                        <span>Payment Support:</span>
                        <x-ui.badge tone="light">Cash on Delivery</x-ui.badge>
                        <x-ui.badge tone="light">Verified Bank Transfer</x-ui.badge>
                    </div>
                </div>

                @if ($products->isEmpty())
                    <x-ui.empty-state
                        title="No Demonstration Products Match Your Filter"
                        description="Try clearing the category or search query to inspect the seeded Stage 1 catalog items."
                        action-label="View All Demo Products"
                        :action-href="route('shop.index')"
                        surface="light"
                    />
                @else
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $product)
                            <x-shop.product-card :product="$product" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.public>
