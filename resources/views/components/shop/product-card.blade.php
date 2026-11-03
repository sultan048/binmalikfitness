@props(['product'])

@php
    $variant = $product->defaultVariant ?? $product->variants->first();
    $img = $product->primaryImage?->path ?? '/images/demo/product-whey-400.webp';
@endphp

<article class="group flex flex-col justify-between rounded-sm border border-graphite/12 bg-white p-4 sm:p-5 transition hover:border-graphite/35">
    <div>
        <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-square overflow-hidden rounded-xs bg-paper">
            <img
                src="{{ $img }}"
                alt="{{ $product->name }}"
                loading="lazy"
                width="400"
                height="400"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
            />
            <div class="absolute top-2.5 left-2.5 flex flex-wrap gap-1.5">
                @if ($product->is_demo)
                    <x-ui.demo-flag label="Demo SKU" />
                @endif
                @if ($variant && $variant->compare_at_price && $variant->compare_at_price > $variant->price)
                    <x-ui.badge tone="gold">Value Pack</x-ui.badge>
                @endif
            </div>
        </a>

        <div class="mt-4">
            <div class="flex items-center justify-between gap-2 text-xs text-graphite/65">
                <span class="font-semibold uppercase tracking-wider text-gold-dark">{{ $product->category?->name ?? 'Supplements' }}</span>
                @if ($variant)
                    <span>{{ $variant->size }}</span>
                @endif
            </div>

            <h3 class="mt-1.5 font-display text-2xl leading-tight tracking-wide text-graphite">
                <a href="{{ route('products.show', $product->slug) }}" class="hover:underline">
                    {{ $product->name }}
                </a>
            </h3>

            @if ($product->subtitle)
                <p class="mt-1 text-xs leading-relaxed text-graphite/75 line-clamp-2">{{ $product->subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="mt-5 border-t border-graphite/10 pt-3.5">
        <div class="flex items-center justify-between gap-2">
            @if ($variant)
                <x-ui.price :paisa="$variant->price" :compare-at-paisa="$variant->compare_at_price" surface="light" size="md" />
            @endif
            <span class="text-[11px] font-medium text-success">
                {{ ($variant && $variant->availableStock() > 0) ? 'In Stock ('.$variant->availableStock().')' : 'Out of Stock' }}
            </span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2">
            <x-ui.button :href="route('products.show', $product->slug)" variant="outline-dark" size="sm">
                View Specs
            </x-ui.button>
            <x-ui.button :href="route('cart.index', ['add' => $product->slug])" variant="graphite" size="sm">
                Add to Cart
            </x-ui.button>
        </div>
    </div>
</article>
