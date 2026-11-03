@php
    use App\Support\Money;
    $variantsJson = $product->variants->map(fn ($v) => [
        'id' => $v->id,
        'sku' => $v->sku,
        'size' => $v->size,
        'flavor' => $v->flavor,
        'servings' => $v->servings,
        'price_formatted' => Money::format($v->price),
        'compare_formatted' => $v->compare_at_price ? Money::format($v->compare_at_price) : null,
        'available' => $v->availableStock(),
        'weight_grams' => $v->weight_grams,
        'lot' => $v->sample_lot_number,
        'expiry' => $v->sample_expiry_date?->format('M Y'),
    ])->values();
    $defaultVariant = $product->defaultVariant ?? $product->variants->first();
    $isWishlisted = in_array($product->id, $wishlistedIds, true);
    $primaryImg = $product->primaryImage?->path ?? '/images/demo/product-whey-800.webp';
    $seoService = app(\App\Domain\Platform\SeoAndStructuredDataService::class);
    $seoMeta = $seoService->resolveMetaForModel(
        model: $product,
        defaultTitle: ($product->seo_title ?: $product->name).' — BIN MALIK FITNESS',
        defaultDescription: (string) ($product->seo_description ?: ($product->short_description ?: $product->subtitle ?: $product->name)),
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
    <section
        class="surface-shop bg-paper text-graphite py-12"
        x-data="{
            variants: {{ Js::from($variantsJson) }},
            selectedId: {{ $defaultVariant?->id ?? 'null' }},
            qty: 1,
            get current() {
                return this.variants.find(v => v.id === Number(this.selectedId)) || this.variants[0] || {};
            }
        }"
    >
        <div class="container-site space-y-12">
            <!-- Breadcrumb -->
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wider text-graphite/60">
                <a href="{{ route('home') }}" class="hover:text-graphite">Home</a>
                <span>/</span>
                <a href="{{ route('shop.index') }}" class="hover:text-graphite">Supplement Shop</a>
                @if ($product->category)
                    <span>/</span>
                    <a href="{{ route('shop.index', ['category' => $product->category->slug]) }}" class="hover:text-graphite">{{ $product->category->name }}</a>
                @endif
                <span>/</span>
                <span class="text-gold-dark">{{ $product->name }}</span>
            </nav>

            @if (session('status'))
                <div role="status" class="rounded-xs border border-success/40 bg-success/10 px-4 py-3 text-xs font-semibold text-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xs border border-danger/40 bg-danger/10 px-4 py-3 text-xs text-danger">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Main Product Hero -->
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <!-- Gallery -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="overflow-hidden rounded-sm border border-graphite/12 bg-white p-6">
                        <img
                            src="{{ $product->primaryImage?->path ?? '/images/demo/product-whey-800.webp' }}"
                            alt="{{ $product->name }}"
                            class="w-full rounded-xs object-cover"
                        />
                    </div>
                    <div class="rounded-sm border border-graphite/12 bg-white p-4 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold uppercase tracking-wider text-graphite">Regulatory & Batch Status</span>
                            <span class="rounded-xs bg-paper px-2 py-0.5 text-[10px] font-bold uppercase text-gold-dark">
                                {{ strtoupper(str_replace('_', ' ', $product->regulatory_status)) }}
                            </span>
                        </div>
                        <p class="text-graphite/75">{{ $product->authenticity_note ?? 'Lot and importer invoice tracked in inventory.' }}</p>
                        <p class="text-graphite/65">
                            <strong>Manufacturer:</strong> {{ $product->manufacturer ?? 'Pending Confirmation' }} ·
                            <strong>Importer:</strong> {{ $product->importer ?? 'Pending Confirmation' }}
                        </p>
                    </div>
                </div>

                <!-- Variant Selection & Purchase Box -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="rounded-sm border border-graphite/12 bg-white p-6 sm:p-8 space-y-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="rounded-xs bg-paper px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-gold-dark">
                                {{ $product->category?->name }} · {{ $product->brand?->name ?? 'BIN MALIK FITNESS' }}
                            </span>
                            <span class="font-mono text-xs text-graphite/60">
                                Variant SKU: <strong x-text="current.sku">{{ $defaultVariant?->sku }}</strong>
                            </span>
                        </div>

                        <div>
                            <h1 class="font-display text-4xl uppercase tracking-wide text-graphite sm:text-5xl leading-none">
                                {{ $product->name }}
                            </h1>
                            @if ($product->subtitle)
                                <p class="mt-2 text-sm font-medium text-graphite/75">{{ $product->subtitle }}</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-baseline justify-between gap-4 border-y border-graphite/10 py-4">
                            <div class="flex items-baseline gap-3">
                                <span class="font-display text-4xl text-graphite" x-text="current.price_formatted">
                                    {{ $defaultVariant ? Money::format($defaultVariant->price) : 'PKR 0' }}
                                </span>
                                <template x-if="current.compare_formatted">
                                    <span class="text-sm text-graphite/45 line-through" x-text="current.compare_formatted"></span>
                                </template>
                            </div>
                            <div>
                                <template x-if="current.available > 0">
                                    <span class="rounded-xs bg-success/10 px-2.5 py-1 text-xs font-bold uppercase text-success">
                                        In Stock (<span x-text="current.available"></span> Available)
                                    </span>
                                </template>
                                <template x-if="current.available <= 0">
                                    <span class="rounded-xs bg-danger/10 px-2.5 py-1 text-xs font-bold uppercase text-danger">
                                        Out of Stock
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Variant Selector -->
                        <form method="POST" action="{{ route('cart.items.store') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label for="variant-select" class="field-label">Select Size / Flavor Variant</label>
                                <select
                                    id="variant-select"
                                    name="product_variant_id"
                                    x-model="selectedId"
                                    class="field-input"
                                >
                                    @foreach ($product->variants as $v)
                                        <option value="{{ $v->id }}">
                                            {{ $v->label() }} — {{ Money::format($v->price) }} ({{ $v->availableStock() }} in stock)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-3 gap-3 rounded-xs bg-paper p-3 text-xs">
                                <div>
                                    <span class="block text-[10px] uppercase text-graphite/60">Sample Batch / Lot</span>
                                    <strong class="font-mono text-graphite" x-text="current.lot || 'N/A'">{{ $defaultVariant?->sample_lot_number ?? 'N/A' }}</strong>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase text-graphite/60">Tracked Expiry</span>
                                    <strong class="text-graphite" x-text="current.expiry || 'N/A'">{{ $defaultVariant?->sample_expiry_date?->format('M Y') ?? 'N/A' }}</strong>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase text-graphite/60">Shipping Weight</span>
                                    <strong class="text-graphite"><span x-text="current.weight_grams">{{ $defaultVariant?->weight_grams ?? 500 }}</span> g</strong>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-end gap-3">
                                <div class="w-28">
                                    <label for="purchase-qty" class="field-label">Quantity</label>
                                    <input
                                        id="purchase-qty"
                                        type="number"
                                        name="quantity"
                                        x-model="qty"
                                        min="1"
                                        :max="Math.max(1, current.available)"
                                        class="field-input"
                                    />
                                </div>
                                <div class="flex-1 min-w-[200px]">
                                    <button
                                        type="submit"
                                        :disabled="current.available <= 0"
                                        class="btn-primary-light w-full py-3 text-xs cursor-pointer disabled:opacity-50"
                                    >
                                        Add Selected Variant to Cart
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Secondary Actions: Wishlist & Stock Alert -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-graphite/10">
                            <form method="POST" action="{{ route('wishlist.toggle') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}" />
                                <input type="hidden" name="product_variant_id" :value="selectedId" />
                                <button type="submit" class="text-xs font-bold uppercase tracking-wider text-gold-dark hover:underline cursor-pointer">
                                    {{ $isWishlisted ? '★ Saved in Your Wishlist (Click to Remove)' : '☆ Save Product to Wishlist' }}
                                </button>
                            </form>
                            <span class="text-xs text-graphite/65">COD & Manual Bank Transfer Supported Across Pakistan</span>
                        </div>
                    </div>

                    <!-- Stock / Restock Alert Form -->
                    <div id="stock-alert" class="rounded-sm border border-graphite/12 bg-white p-5">
                        <h2 class="font-display text-xl uppercase tracking-wide text-graphite">Batch & Restock Alert</h2>
                        <p class="mt-1 text-xs text-graphite/70">
                            Want to be notified when a new batch is received or restocked for this variant? Register your email below.
                        </p>
                        <form method="POST" action="{{ route('stock-alerts.store') }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @csrf
                            <input type="hidden" name="product_variant_id" :value="selectedId" />
                            <input
                                type="email"
                                name="email"
                                value="{{ auth()->user()?->email }}"
                                required
                                placeholder="you@example.com"
                                class="field-input"
                            />
                            <input
                                type="text"
                                name="phone"
                                value="{{ auth()->user()?->phone }}"
                                placeholder="03XX-XXXXXXX (Optional)"
                                class="field-input"
                            />
                            <button type="submit" class="rounded-xs border border-graphite bg-paper px-4 py-2 text-xs font-bold uppercase tracking-wider text-graphite hover:bg-graphite hover:text-gold cursor-pointer">
                                Notify Me on Restock
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Nutrition Facts, Ingredients, Batches & Regulatory Documentation -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <div class="lg:col-span-7 space-y-6">
                    <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                        <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">Product Overview & Approved Labeling</h2>
                        <p class="text-sm text-graphite/80 leading-relaxed">{{ $product->description }}</p>

                        @if ($product->usage_instructions)
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-graphite">Directions for Use</h3>
                                <p class="mt-1 text-xs text-graphite/75">{{ $product->usage_instructions }}</p>
                            </div>
                        @endif

                        @if ($product->warnings || $product->allergens)
                            <div class="rounded-xs border border-danger/30 bg-danger/5 p-4 text-xs space-y-1.5">
                                @if ($product->warnings)
                                    <p class="text-danger"><strong>Warnings:</strong> {{ $product->warnings }}</p>
                                @endif
                                @if ($product->allergens)
                                    <p class="text-graphite"><strong>Allergen Disclosure:</strong> {{ $product->allergens }}</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Active Inventory Batches & Authenticity Docs -->
                    <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                        <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">Tracked Batches & Regulatory Records</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-graphite/15 text-[11px] uppercase text-graphite/60">
                                        <th class="py-2 pr-3">Variant SKU</th>
                                        <th class="py-2 pr-3">Batch / Lot #</th>
                                        <th class="py-2 pr-3">Expiry Date</th>
                                        <th class="py-2">Remaining Units</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-graphite/10">
                                    @foreach ($product->variants as $v)
                                        @foreach ($v->batches as $batch)
                                            <tr>
                                                <td class="py-2 pr-3 font-mono font-semibold">{{ $v->sku }}</td>
                                                <td class="py-2 pr-3 font-mono">{{ $batch->batch_number }}</td>
                                                <td class="py-2 pr-3">{{ $batch->expires_at?->format('d M Y') ?? 'Exempt' }}</td>
                                                <td class="py-2 font-semibold">{{ $batch->quantity_remaining }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($product->documents->isNotEmpty())
                            <div class="pt-3 border-t border-graphite/10">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-graphite mb-2">Supporting Documentation Status</h3>
                                <ul class="space-y-1.5 text-xs">
                                    @foreach ($product->documents as $doc)
                                        <li class="flex items-center justify-between rounded-xs bg-paper px-3 py-2">
                                            <span>{{ $doc->title }} ({{ $doc->reference_number }})</span>
                                            <span class="font-bold uppercase text-gold-dark">{{ $doc->status }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Nutrition Panel & Ingredients -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="rounded-sm border-2 border-ink bg-white p-6 space-y-4">
                        <h2 class="font-display text-3xl uppercase tracking-wide text-ink border-b-4 border-ink pb-2">
                            Supplement / Nutrition Facts
                        </h2>
                        @if (! empty($product->nutrition_facts))
                            <dl class="divide-y divide-graphite/15 text-xs">
                                @foreach ($product->nutrition_facts as $key => $val)
                                    <div class="flex items-center justify-between py-2">
                                        <dt class="font-semibold uppercase text-graphite/75">{{ str_replace('_', ' ', $key) }}</dt>
                                        <dd class="font-bold text-ink">{{ $val }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-xs text-graphite/70">Non-consumable gym accessory — nutrition panel not applicable.</p>
                        @endif

                        @if (! empty($product->ingredients))
                            <div class="pt-3 border-t-2 border-ink text-xs">
                                <h3 class="font-bold uppercase text-ink">Ingredients</h3>
                                <p class="mt-1 text-graphite/80">
                                    {{ is_array($product->ingredients) ? implode(', ', $product->ingredients) : $product->ingredients }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Verified Purchase Reviews -->
                    <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">
                                Verified-Purchase Reviews ({{ $product->approvedReviews->count() }})
                            </h2>
                            <span class="rounded-xs bg-paper px-2 py-0.5 text-[10px] font-bold uppercase text-gold-dark">
                                Never Fabricated
                            </span>
                        </div>

                        @if ($product->approvedReviews->isEmpty())
                            <p class="text-xs text-graphite/70">
                                No verified-purchase reviews published yet. Reviews can only be submitted by customers with a delivered order of this product and must pass staff moderation.
                            </p>
                        @else
                            <div class="space-y-3">
                                @foreach ($product->approvedReviews as $rev)
                                    <div class="rounded-xs border border-graphite/10 bg-paper p-3 text-xs space-y-1">
                                        <div class="flex items-center justify-between">
                                            <strong class="text-graphite">{{ $rev->title }} ({{ $rev->rating }}/5 ★)</strong>
                                            <span class="text-[10px] font-bold uppercase text-success">Verified Buyer · {{ $rev->user?->name }}</span>
                                        </div>
                                        <p class="text-graphite/80">{{ $rev->body }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($eligibleOrderForReview)
                            <form method="POST" action="{{ route('products.reviews.store', $product) }}" class="pt-4 border-t border-graphite/10 space-y-3">
                                @csrf
                                <p class="text-xs font-bold uppercase text-success">
                                    ✓ Eligible Verified Purchase (Order {{ $eligibleOrderForReview->order_number }})
                                </p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <label for="rev-rating" class="field-label">Rating</label>
                                        <select id="rev-rating" name="rating" class="field-input">
                                            <option value="5">5 ★ — Excellent</option>
                                            <option value="4">4 ★ — Good</option>
                                            <option value="3">3 ★ — Average</option>
                                            <option value="2">2 ★ — Below Average</option>
                                            <option value="1">1 ★ — Poor</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="rev-title" class="field-label">Review Headline</label>
                                        <input id="rev-title" type="text" name="title" required placeholder="Mixability, taste & batch quality" class="field-input" />
                                    </div>
                                </div>
                                <div>
                                    <label for="rev-body" class="field-label">Your Honest Review</label>
                                    <textarea id="rev-body" name="body" rows="2" required class="field-input" placeholder="Share your experience with this supplement..."></textarea>
                                </div>
                                <button type="submit" class="btn-primary-light text-xs cursor-pointer">
                                    Submit Verified Review
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            @if ($relatedProducts->isNotEmpty())
                <div class="pt-8 border-t border-graphite/12">
                    <h2 class="font-display text-3xl uppercase tracking-wide text-graphite mb-6">Related Supplements & Gear</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        @foreach ($relatedProducts as $rel)
                            <article class="rounded-sm border border-graphite/12 bg-white p-5 flex flex-col justify-between">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gold-dark">{{ $rel->category?->name }}</span>
                                    <h3 class="mt-1 font-display text-2xl uppercase text-graphite">
                                        <a href="{{ route('products.show', $rel->slug) }}" class="hover:text-gold-dark">{{ $rel->name }}</a>
                                    </h3>
                                </div>
                                <div class="mt-4 flex items-center justify-between pt-3 border-t border-graphite/10">
                                    <span class="font-display text-2xl text-graphite">
                                        {{ $rel->defaultVariant ? Money::format($rel->defaultVariant->price) : '' }}
                                    </span>
                                    <a href="{{ route('products.show', $rel->slug) }}" class="text-xs font-bold uppercase tracking-wider text-gold-dark hover:underline">
                                        View Product →
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
