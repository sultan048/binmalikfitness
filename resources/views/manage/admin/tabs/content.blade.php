{{-- Tab panel "content" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<div id="cms-media-library" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="content" x-show="tab === 'content'" x-cloak>
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 14 · CMS Content &amp; Policy Pages</span>
                <h2 class="font-display text-2xl uppercase text-graphite">CMS Pages &amp; Media Library</h2>
            </div>
            <x-ui.badge tone="gold">{{ $cmsPages->count() }} Pages · {{ $mediaAssets->count() }} Assets</x-ui.badge>
        </div>

        <div class="space-y-2 text-xs max-h-48 overflow-y-auto">
            @foreach ($cmsPages as $page)
                <div class="flex items-center justify-between rounded-xs border border-graphite/10 bg-paper p-2.5">
                    <div>
                        <strong class="text-graphite">{{ $page->title }}</strong>
                        <span class="block font-mono text-[11px] text-graphite/65">/{{ $page->slug }} · {{ strtoupper($page->section) }}</span>
                    </div>
                    <x-ui.badge :tone="$page->status === 'published' ? 'success' : 'light'">{{ $page->status }}</x-ui.badge>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('manage.cms-pages.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Publish or Update CMS Page (content.manage)</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <input type="text" name="title" placeholder="Page Title" class="field-input py-1.5 text-xs sm:col-span-2" required />
                <select name="section" class="field-input py-1.5 text-xs">
                    <option value="policy">Policy</option>
                    <option value="editorial">Editorial Guide</option>
                    <option value="faq">FAQ</option>
                    <option value="landing">Landing Section</option>
                </select>
            </div>
            <input type="text" name="excerpt" placeholder="Short summary..." class="field-input py-1.5 text-xs" />
            <textarea name="body" rows="2" placeholder="Page markdown or editorial content..." class="field-input py-1.5 text-xs" required></textarea>
            <div class="flex items-center justify-between">
                <select name="status" class="field-input py-1 text-xs w-36">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs">Save CMS Page</button>
            </div>
        </form>

        <form method="POST" action="{{ route('manage.media-assets.store') }}" class="rounded-xs border border-graphite/15 p-3.5 space-y-2 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Register Brand Media Library Asset (media.manage)</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <input type="text" name="title" placeholder="Asset Title" class="field-input py-1.5 text-xs" required />
                <select name="collection" class="field-input py-1.5 text-xs">
                    <option value="hero">Hero</option>
                    <option value="coaching">Coaching</option>
                    <option value="shop">Supplement Shop</option>
                    <option value="gym">Physical Gym</option>
                </select>
                <input type="text" name="path" placeholder="/images/demo/..." class="field-input py-1.5 text-xs" required />
            </div>
            <div class="flex items-center gap-2">
                <input type="text" name="alt_text" placeholder="WCAG 2.2 AA Accessible Alt Text..." class="field-input py-1.5 text-xs flex-1" required />
                <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 font-bold uppercase text-gold cursor-pointer">Save Asset</button>
            </div>
        </form>
    </section>

    <section id="testimonials-governance" class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 15 · Consent-Backed Testimonials &amp; Privacy Requests</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Testimonials &amp; Privacy Governance</h2>
            </div>
            <x-ui.badge tone="gold">{{ $testimonials->count() }} Testimonials</x-ui.badge>
        </div>

        <div class="space-y-2 text-xs">
            @foreach ($testimonials as $testi)
                <div class="rounded-xs border border-graphite/10 bg-paper p-3">
                    <div class="flex items-center justify-between">
                        <strong>{{ $testi->author_label }} · {{ $testi->program_context }}</strong>
                        <x-ui.badge :tone="$testi->status === 'published' ? 'success' : 'light'">
                            {{ $testi->status }} (Consent #{{ $testi->consent_record_id ?? 'None' }})
                        </x-ui.badge>
                    </div>
                    <p class="mt-1 text-graphite/80 italic">"{{ $testi->quote }}"</p>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('manage.testimonials.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Add Consent-Verified Testimonial (testimonials.manage)</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <input type="text" name="author_label" placeholder="Client Label (e.g. Verified Coaching Client)" class="field-input py-1.5 text-xs" required />
                <input type="text" name="program_context" placeholder="Program (e.g. 12-Week Transformation)" class="field-input py-1.5 text-xs" required />
            </div>
            <textarea name="quote" rows="2" placeholder="Verified client quote..." class="field-input py-1.5 text-xs" required></textarea>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                <select name="consent_record_id" class="field-input py-1 text-xs sm:col-span-2">
                    <option value="">Select Active Consent Record...</option>
                    @foreach ($consentRecords as $cr)
                        <option value="{{ $cr->id }}">Consent #{{ $cr->id }} — {{ $cr->user?->name }} ({{ $cr->consent_type }})</option>
                    @endforeach
                </select>
                <input type="number" name="rating" value="5" min="1" max="5" class="field-input py-1 text-xs" required />
                <select name="status" class="field-input py-1 text-xs">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Save Testimonial</button>
        </form>

        {{-- Customer Privacy & Data Export Requests --}}
        <div class="border-t border-graphite/10 pt-3 space-y-2 text-xs">
            <strong class="uppercase text-graphite block">Customer Privacy &amp; Data Export Requests ({{ $privacyRequests->count() }})</strong>
            @foreach ($privacyRequests as $prReq)
                <div class="rounded-xs border border-graphite/12 p-2.5 flex flex-wrap items-center justify-between gap-2">
                    <form method="POST" action="{{ route('manage.privacy-requests.resolve', $prReq) }}" class="flex flex-1 flex-wrap items-center justify-between gap-2">
                        @csrf
                        @method('PUT')
                        <div>
                            <strong>{{ $prReq->user?->name }}</strong> · <span class="font-mono text-gold-dark">{{ $prReq->request_type }}</span>
                            <span class="block text-[11px] text-graphite/70">{{ $prReq->customer_notes }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <select name="status" class="field-input py-1 text-xs">
                                @foreach (['pending', 'in_review', 'completed', 'rejected'] as $pst)
                                    <option value="{{ $pst }}" @selected($prReq->status === $pst)>{{ strtoupper($pst) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="resolution_notes" value="{{ $prReq->resolution_notes ?? 'Processed per data governance policy.' }}" class="field-input py-1 text-xs w-44" required />
                            <button type="submit" class="rounded-xs bg-charcoal px-2.5 py-1 text-[11px] font-bold uppercase text-gold cursor-pointer">Resolve</button>
                        </div>
                    </form>
                    @if (in_array($prReq->request_type, [\App\Models\PrivacyRequest::TYPE_ACCOUNT_DELETION_ENQUIRY, 'account_deletion'], true) && $prReq->status !== 'completed')
                        <form method="POST" action="{{ route('manage.privacy-requests.anonymise', $prReq) }}">
                            @csrf
                            <button type="submit" class="rounded-xs bg-danger px-2.5 py-1 text-[11px] font-bold uppercase text-ivory cursor-pointer">
                                Anonymise PII
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</div>
