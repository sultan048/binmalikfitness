@php use App\Support\Money; @endphp
<x-layouts.public
    title="Supplement Shop — BIN MALIK FITNESS"
    description="Shop batch-tracked whey protein, creatine, pre-workout, amino acids, vitamins, and lifting accessories in Pakistan (PKR)."
>
    <section class="bg-ink text-ivory border-b border-white/10 py-10">
        <div class="container-site flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-gold">
                    Stage 3 Supplement Ecommerce · Batch & Expiry Tracked · PKR
                </p>
                <h1 class="mt-2 font-display text-5xl uppercase tracking-wide text-ivory sm:text-6xl">
                    Supplement & Gear Shop
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-silver">
                    Every consumable supplement tracks lot number, expiry date, and importer verification status. Free standard Pakistan delivery on orders over PKR 10,000.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('orders.track') }}" class="rounded-xs border border-white/20 bg-charcoal px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-ivory hover:border-gold hover:text-gold">
                    Track Order Status
                </a>
                <a href="{{ route('cart.index') }}" class="rounded-xs bg-gold px-4 py-2 text-xs font-bold uppercase tracking-wider text-ink hover:bg-gold/90">
                    View Cart ({{ $cartTotals['item_count'] ?? 0 }} Items · {{ $cartTotals['total_formatted'] ?? 'PKR 0' }})
                </a>
            </div>
        </div>
    </section>

    <section class="surface-shop bg-paper text-graphite py-12">
        <div class="container-site">
            @if (session('status'))
                <div role="status" class="mb-6 rounded-xs border border-success/40 bg-success/10 px-4 py-3 text-xs font-semibold text-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-xs border border-danger/40 bg-danger/10 px-4 py-3 text-xs text-danger">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Sidebar Filters -->
                <aside class="lg:col-span-3 space-y-6">
                    <form method="GET" action="{{ route('shop.index') }}" class="rounded-sm border border-graphite/12 bg-white p-5 space-y-5">
                        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                            <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">Filter Catalog</h2>
                            <a href="{{ route('shop.index') }}" class="text-xs font-semibold text-gold-dark hover:underline">Reset</a>
                        </div>

                        <div>
                            <label for="shop-search" class="field-label">Search Name or SKU</label>
                            <input
                                id="shop-search"
                                type="search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Whey, Creatine, Shaker..."
                                class="field-input"
                            />
                        </div>

                        <div>
                            <label for="shop-category" class="field-label">Category ({{ $categories->count() }})</label>
                            <select id="shop-category" name="category" class="field-input">
                                <option value="">All Categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->slug }}" @selected(request('category') === $cat->slug)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="shop-brand" class="field-label">Brand</label>
                            <select id="shop-brand" name="brand" class="field-input">
                                <option value="">All Verified Brands</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->slug }}" @selected(request('brand') === $brand->slug)>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label for="min-price" class="field-label">Min PKR</label>
                                <input id="min-price" type="number" name="min_price" value="{{ request('min_price') }}" placeholder="0" min="0" class="field-input" />
                            </div>
                            <div>
                                <label for="max-price" class="field-label">Max PKR</label>
                                <input id="max-price" type="number" name="max_price" value="{{ request('max_price') }}" placeholder="50000" min="0" class="field-input" />
                            </div>
                        </div>

                        <div>
                            <label for="shop-sort" class="field-label">Sort By</label>
                            <select id="shop-sort" name="sort" class="field-input">
                                <option value="featured" @selected($sort === 'featured')>Featured & Bestsellers</option>
                                <option value="price_asc" @selected($sort === 'price_asc')>Price: Low to High (PKR)</option>
                                <option value="price_desc" @selected($sort === 'price_desc')>Price: High to Low (PKR)</option>
                                <option value="newest" @selected($sort === 'newest')>Newest Arrivals</option>
                            </select>
                        </div>

                        <label class="flex items-center gap-2 text-xs font-medium text-graphite cursor-pointer">
                            <input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock')) class="rounded-xs border-graphite/30 text-ink focus:ring-gold" />
                            <span>In-stock items only</span>
                        </label>

                        <button type="submit" class="btn-primary-light w-full text-xs cursor-pointer">
                            Apply Filters
                        </button>
                    </form>

                    <!-- Category Quick Links -->
                    <div class="rounded-sm border border-graphite/12 bg-white p-5">
                        <h3 class="font-display text-xl uppercase tracking-wide text-graphite">All 12 Categories</h3>
                        <ul class="mt-3 space-y-1.5 text-xs">
                            @foreach ($categories as $cat)
                                <li>
                                    <a
                                        href="{{ route('shop.index', ['category' => $cat->slug]) }}"
                                        class="flex items-center justify-between py-1 {{ request('category') === $cat->slug ? 'font-bold text-gold-dark' : 'text-graphite/80 hover:text-graphite' }}"
                                    >
                                        <span>{{ $cat->name }}</span>
                                        <span class="text-[10px] uppercase tracking-wider text-graphite/50">→</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>

                <!-- Product Grid -->
                <div class="lg:col-span-9 space-y-8">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-graphite/12 pb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('shop.index') }}" class="rounded-xs px-3 py-1.5 text-xs font-semibold uppercase tracking-wider {{ !request('collection') ? 'bg-ink text-gold' : 'bg-white border border-graphite/15 text-graphite' }}">
                                All ({{ $products->count() }})
                            </a>
                            <a href="{{ route('shop.index', ['collection' => 'bestsellers']) }}" class="rounded-xs px-3 py-1.5 text-xs font-semibold uppercase tracking-wider {{ request('collection') === 'bestsellers' ? 'bg-ink text-gold' : 'bg-white border border-graphite/15 text-graphite' }}">
                                Bestsellers
                            </a>
                            <a href="{{ route('shop.index', ['collection' => 'featured']) }}" class="rounded-xs px-3 py-1.5 text-xs font-semibold uppercase tracking-wider {{ request('collection') === 'featured' ? 'bg-ink text-gold' : 'bg-white border border-graphite/15 text-graphite' }}">
                                Featured
                            </a>
                        </div>
                        <p class="text-xs font-semibold text-graphite/70">
                            Showing {{ $products->count() }} product(s) · Promo code <code class="font-mono font-bold text-gold-dark">BMF10</code> (10% off PKR 5,000+)
                        </p>
                    </div>

                    @if ($products->isEmpty())
                        <div class="rounded-sm border border-graphite/15 bg-white p-10 text-center space-y-3">
                            <h2 class="font-display text-3xl uppercase text-graphite">No Matching Products Found</h2>
                            <p class="text-sm text-graphite/70">Try clearing your search term or widening your PKR price range.</p>
                            <a href="{{ route('shop.index') }}" class="inline-block btn-primary-light text-xs">Reset All Filters</a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($products as $product)
                                @php
                                    $variant = $product->defaultVariant ?? $product->variants->first();
                                    $avail = $variant ? $variant->availableStock() : 0;
                                    $isWishlisted = in_array($product->id, $wishlistedIds, true);
                                @endphp
                                <article class="flex flex-col justify-between rounded-sm border border-graphite/12 bg-white p-5 shadow-xs transition hover:border-graphite/30">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-3">
                                            <span class="rounded-xs bg-paper px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gold-dark">
                                                {{ $product->category?->name }}
                                            </span>
                                            <form method="POST" action="{{ route('wishlist.toggle') }}">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}" />
                                                @if ($variant)
                                                    <input type="hidden" name="product_variant_id" value="{{ $variant->id }}" />
                                                @endif
                                                <button
                                                    type="submit"
                                                    class="rounded-xs border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider cursor-pointer {{ $isWishlisted ? 'border-gold bg-gold/20 text-ink' : 'border-graphite/15 text-graphite/70 hover:border-graphite' }}"
                                                >
                                                    {{ $isWishlisted ? '★ Saved' : '☆ Wishlist' }}
                                                </button>
                                            </form>
                                        </div>

                                        <a href="{{ route('products.show', $product->slug) }}" class="block overflow-hidden rounded-xs bg-paper mb-4">
                                            <img
                                                src="{{ $product->primaryImage?->path ?? '/images/demo/product-whey-800.webp' }}"
                                                alt="{{ $product->name }}"
                                                class="h-48 w-full object-cover transition duration-300 hover:scale-102"
                                                loading="lazy"
                                            />
                                        </a>

                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-graphite/60">
                                            {{ $product->brand?->name ?? 'BIN MALIK FITNESS' }} · SKU: {{ $variant?->sku ?? $product->sku }}
                                        </p>
                                        <h2 class="mt-1 font-display text-2xl uppercase tracking-wide text-graphite leading-tight">
                                            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-gold-dark">
                                                {{ $product->name }}
                                            </a>
                                        </h2>
                                        <p class="mt-1.5 text-xs text-graphite/75 line-clamp-2">
                                            {{ $product->subtitle ?? $product->short_description }}
                                        </p>
                                    </div>

                                    <div class="mt-5 pt-4 border-t border-graphite/10 space-y-3">
                                        <div class="flex items-baseline justify-between">
                                            <div>
                                                <span class="font-display text-2xl text-graphite">
                                                    {{ $variant ? Money::format($variant->price) : 'PKR —' }}
                                                </span>
                                                @if ($variant && $variant->compare_at_price)
                                                    <span class="ml-1.5 text-xs text-graphite/45 line-through">
                                                        {{ Money::format($variant->compare_at_price) }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($avail > 0)
                                                <span class="text-[11px] font-bold uppercase text-success">
                                                    {{ $avail }} In Stock
                                                </span>
                                            @else
                                                <span class="text-[11px] font-bold uppercase text-danger">
                                                    Out of Stock
                                                </span>
                                            @endif
                                        </div>

                                        @if ($variant && $variant->sample_lot_number)
                                            <p class="text-[11px] text-graphite/60">
                                                Batch: <span class="font-mono font-semibold">{{ $variant->sample_lot_number }}</span>
                                                @if ($variant->sample_expiry_date)
                                                    · Exp: {{ $variant->sample_expiry_date->format('M Y') }}
                                                @endif
                                            </p>
                                        @endif

                                        <div class="grid grid-cols-2 gap-2">
                                            <a
                                                href="{{ route('products.show', $product->slug) }}"
                                                class="inline-flex items-center justify-center rounded-xs border border-graphite/25 px-3 py-2 text-xs font-bold uppercase tracking-wider text-graphite hover:bg-graphite/5"
                                            >
                                                Details
                                            </a>
                                            @if ($variant && $avail > 0)
                                                <form method="POST" action="{{ route('cart.items.store') }}">
                                                    @csrf
                                                    <input type="hidden" name="product_variant_id" value="{{ $variant->id }}" />
                                                    <input type="hidden" name="quantity" value="1" />
                                                    <button type="submit" class="btn-primary-light w-full py-2 text-xs cursor-pointer">
                                                        Add to Cart
                                                    </button>
                                                </form>
                                            @else
                                                <a
                                                    href="{{ route('products.show', $product->slug) }}#stock-alert"
                                                    class="inline-flex items-center justify-center rounded-xs bg-graphite/15 px-3 py-2 text-xs font-bold uppercase tracking-wider text-graphite"
                                                >
                                                    Restock Alert
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    @if ($recentlyViewed->isNotEmpty())
                        <div class="pt-8 border-t border-graphite/12">
                            <h2 class="font-display text-2xl uppercase tracking-wide text-graphite mb-4">Recently Viewed Products</h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                @foreach ($recentlyViewed as $recentItem)
                                    <a href="{{ route('products.show', $recentItem->slug) }}" class="flex items-center gap-3 rounded-sm border border-graphite/12 bg-white p-3 hover:border-graphite/30">
                                        <img src="{{ $recentItem->primaryImage?->path ?? '/images/demo/product-whey-800.webp' }}" alt="{{ $recentItem->name }}" class="h-14 w-14 rounded-xs object-cover" />
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-bold text-graphite">{{ $recentItem->name }}</p>
                                            <p class="text-xs font-semibold text-gold-dark">
                                                {{ $recentItem->defaultVariant ? Money::format($recentItem->defaultVariant->price) : '' }}
                                            </p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
