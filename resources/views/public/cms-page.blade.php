<x-layouts.public
    :title="$seoMeta['title']"
    :description="$seoMeta['description']"
    :canonical="$seoMeta['canonical']"
    :og-image="$seoMeta['og_image']"
    :robots="$seoMeta['robots']"
    :json-ld-schemas="$jsonLdSchemas"
>
    <section class="border-b border-white/10 bg-ink py-14">
        <div class="container-site">
            <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center gap-2 text-xs text-silver">
                <a href="{{ route('home') }}" class="hover:text-ivory">Home</a>
                <span class="text-white/30">/</span>
                <span class="uppercase tracking-wider text-gold">{{ $page->section }}</span>
                <span class="text-white/30">/</span>
                <span class="text-ivory">{{ $page->title }}</span>
            </nav>

            <div class="flex flex-wrap items-center gap-2.5">
                <x-ui.badge tone="gold">{{ $page->section === 'editorial' ? 'EDITORIAL GUIDE' : strtoupper($page->section).' DOCUMENT' }}</x-ui.badge>
                @if ($page->published_at)
                    <span class="font-mono text-xs text-silver">
                        Last Verified: {{ $page->published_at->format('d M Y') }}
                    </span>
                @endif
                @if ($page->is_demo)
                    <x-ui.demo-flag label="Owner-Editable CMS Document" />
                @endif
            </div>

            <h1 class="mt-4 max-w-4xl font-display text-4xl tracking-wider text-ivory sm:text-5xl">
                {{ $page->title }}
            </h1>

            @if ($page->excerpt)
                <p class="mt-4 max-w-3xl text-base leading-relaxed text-silver">
                    {{ $page->excerpt }}
                </p>
            @endif
        </div>
    </section>

    <section class="bg-charcoal py-14">
        <div class="container-site grid grid-cols-1 gap-10 lg:grid-cols-12">
            <article class="lg:col-span-8 rounded-sm border border-white/10 bg-ink p-6 sm:p-10">
                <div class="prose prose-invert max-w-none space-y-5 text-sm leading-relaxed text-silver">
                    @foreach (preg_split('/\n\s*\n/', trim($page->body)) as $paragraph)
                        <p class="whitespace-pre-line text-ivory/90 leading-7">{{ trim($paragraph) }}</p>
                    @endforeach
                </div>

                <div class="mt-10 border-t border-white/10 pt-6 flex flex-wrap items-center justify-between gap-4 text-xs text-silver">
                    <div>
                        Published by <span class="font-semibold text-ivory">{{ $page->editor?->name ?? 'BIN MALIK FITNESS Compliance Desk' }}</span>
                    </div>
                    <div class="font-mono text-silver/80">
                        Slug: /pages/{{ $page->slug }}
                    </div>
                </div>
            </article>

            <aside class="lg:col-span-4 space-y-6">
                <div class="rounded-sm border border-white/10 bg-ink p-6">
                    <p class="eyebrow text-gold">Legal &amp; Editorial Index</p>
                    <h2 class="mt-1 font-display text-2xl tracking-wider text-ivory">OTHER PUBLISHED PAGES</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ($otherPages as $other)
                            <li class="border-b border-white/5 pb-3 last:border-b-0 last:pb-0">
                                <a href="{{ route('cms.pages.show', $other->slug) }}" class="group block">
                                    <span class="font-mono text-[10px] uppercase tracking-wider text-gold">{{ $other->section }}</span>
                                    <p class="mt-0.5 font-medium text-ivory group-hover:text-gold transition">{{ $other->title }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-sm border border-gold/30 bg-ink p-6">
                    <p class="eyebrow text-gold">Need Direct Assistance?</p>
                    <h2 class="mt-1 font-display text-2xl tracking-wider text-ivory">SUPPORT &amp; PRIVACY DESK</h2>
                    <p class="mt-2 text-xs leading-relaxed text-silver">
                        Questions regarding supplement batch authenticity, COD or bank transfer verification, or health intake data privacy can be raised directly from your Member Portal.
                    </p>
                    <div class="mt-5 flex flex-col gap-2.5">
                        <a href="{{ route('account.dashboard') }}#support-desk" class="btn-primary w-full justify-center text-xs">
                            Open Support / Privacy Desk
                        </a>
                        <a href="{{ route('coaching.index') }}" class="btn-secondary w-full justify-center text-xs">
                            Explore 1-on-1 Coaching
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
