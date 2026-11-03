<x-layouts.public
    :title="$video->title.' — BIN MALIK FITNESS Video Library'"
    :description="$video->summary"
>
    <section class="section-y bg-ink border-b border-white/10">
        <div class="container-site space-y-8">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-silver">
                <a href="{{ route('home') }}" class="hover:text-gold">Home</a>
                <span>/</span>
                <a href="{{ route('coaching.index') }}#video-library" class="hover:text-gold">Video Library</a>
                <span>/</span>
                <span class="text-ivory font-semibold">{{ $video->title }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <div class="lg:col-span-8 space-y-6">
                    {{-- Server-Side Authorized Stream Container --}}
                    <div class="overflow-hidden rounded-sm border border-gold/50 bg-charcoal">
                        <div class="relative aspect-video bg-ink flex flex-col items-center justify-center p-8 text-center space-y-4">
                            @if ($video->thumbnail_path)
                                <img src="{{ $video->thumbnail_path }}" alt="{{ $video->title }}" class="absolute inset-0 h-full w-full object-cover opacity-25" />
                            @endif
                            <div class="relative z-10 space-y-3 max-w-xl">
                                <x-ui.badge tone="gold">
                                    {{ $video->access === 'entitled' ? 'Entitled Stream Verified' : 'Public Technical Preview' }}
                                </x-ui.badge>
                                <h1 class="font-display text-3xl sm:text-4xl text-ivory">{{ $video->title }}</h1>
                                <p class="text-xs text-silver">{{ $video->coach_note }} · Duration: {{ $video->formattedDuration() }}</p>
                                <div class="rounded-xs border border-white/15 bg-charcoal/90 px-4 py-2.5 text-[11px] font-mono text-gold">
                                    Provider: {{ $playback['provider'] }} · Ref: {{ $playback['provider_ref'] }}<br />
                                    Signed Token: {{ substr($playback['signed_token'], 0, 24) }}... · Expires: {{ $playback['expires_at'] }}
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-3">
                            <h2 class="font-display text-2xl text-ivory">Coaching Execution Standards & Notes</h2>
                            <p class="text-sm text-silver leading-relaxed">{{ $video->summary }}</p>
                        </div>
                    </div>
                </div>

                <aside class="lg:col-span-4 space-y-4">
                    <div class="rounded-sm border border-white/12 bg-charcoal p-5 space-y-4">
                        <h2 class="font-display text-xl text-ivory">More Training Videos</h2>
                        <div class="space-y-3">
                            @foreach ($otherVideos as $other)
                                <div class="rounded-xs border border-white/10 bg-ink p-3.5 space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-gold font-bold uppercase">{{ $other->category }}</span>
                                        <span class="text-silver">{{ strtoupper($other->access) }} · {{ $other->formattedDuration() }}</span>
                                    </div>
                                    <p class="text-xs font-semibold text-ivory">{{ $other->title }}</p>
                                    <a href="{{ route('videos.watch', $other->slug) }}" class="inline-block text-[11px] font-bold text-gold hover:underline">
                                        Open Stream →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.public>
