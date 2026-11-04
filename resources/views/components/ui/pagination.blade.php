{{-- Brand pagination used by all paginators (registered in AppServiceProvider) --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-2 pt-3 text-xs">
        <p class="text-graphite/65">
            @if (method_exists($paginator, 'total'))
                Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @else
                Page {{ $paginator->currentPage() }}
            @endif
        </p>

        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-xs border border-graphite/10 px-2.5 py-1 text-graphite/35" aria-disabled="true">‹ Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-xs border border-graphite/20 px-2.5 py-1 font-semibold text-graphite hover:border-gold-dark hover:text-gold-dark">‹ Prev</a>
            @endif

            @isset($elements)
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1.5 text-graphite/50">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="rounded-xs bg-ink px-2.5 py-1 font-bold text-gold">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="rounded-xs border border-graphite/20 px-2.5 py-1 text-graphite hover:border-gold-dark hover:text-gold-dark">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endisset

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-xs border border-graphite/20 px-2.5 py-1 font-semibold text-graphite hover:border-gold-dark hover:text-gold-dark">Next ›</a>
            @else
                <span class="rounded-xs border border-graphite/10 px-2.5 py-1 text-graphite/35" aria-disabled="true">Next ›</span>
            @endif
        </div>
    </nav>
@endif
