{{-- Tab panel "seo" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<div id="seo-settings" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="seo" x-show="tab === 'seo'" x-cloak>
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 16 · Technical SEO &amp; Canonical Metadata</span>
                <h2 class="font-display text-2xl uppercase text-graphite">SEO Metadata Configuration</h2>
            </div>
            <x-ui.badge tone="light">seo.manage</x-ui.badge>
        </div>

        <div class="space-y-2 text-xs">
            @foreach ($seoMetadataRecords as $seo)
                <div class="rounded-xs bg-paper p-2.5 border border-graphite/10">
                    <strong class="block text-graphite">{{ $seo->meta_title }}</strong>
                    <span class="text-[11px] text-graphite/70 block">{{ $seo->meta_description }}</span>
                    <span class="font-mono text-[10px] text-gold-dark">{{ $seo->canonical_url }} ({{ $seo->robots }})</span>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('manage.seo-metadata.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Save Entity SEO Metadata</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <select name="seoable_type" class="field-input py-1.5 text-xs">
                    <option value="App\Models\Product">Product</option>
                    <option value="App\Models\CoachingPackage">Coaching Package</option>
                    <option value="App\Models\DietPlan">Diet Plan</option>
                    <option value="App\Models\CmsPage">CMS Page</option>
                </select>
                <input type="number" name="seoable_id" value="1" min="1" class="field-input py-1.5 text-xs" required />
                <select name="robots" class="field-input py-1.5 text-xs">
                    <option value="index,follow">index,follow</option>
                    <option value="noindex,nofollow">noindex,nofollow</option>
                </select>
            </div>
            <input type="text" name="meta_title" placeholder="Meta Title (<= 160 chars)" class="field-input py-1.5 text-xs" required />
            <input type="text" name="meta_description" placeholder="Meta Description (<= 320 chars)" class="field-input py-1.5 text-xs" required />
            <input type="text" name="canonical_url" placeholder="https://binmalikfitness.com/..." class="field-input py-1.5 text-xs" />
            <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Save SEO Metadata</button>
        </form>
    </section>

    <section id="notification-templates" class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 18 · Transactional &amp; Operational Templates</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Notification Templates</h2>
            </div>
            <x-ui.badge tone="gold">{{ $notificationTemplates->count() }} Templates</x-ui.badge>
        </div>

        <div class="space-y-2 text-xs max-h-48 overflow-y-auto">
            @foreach ($notificationTemplates as $tpl)
                <div class="rounded-xs bg-paper p-2.5 border border-graphite/10">
                    <div class="flex items-center justify-between">
                        <strong class="font-mono text-gold-dark">{{ $tpl->key }}</strong>
                        <span class="uppercase text-[10px] font-bold text-graphite/70">{{ $tpl->channel }}</span>
                    </div>
                    <p class="font-semibold text-graphite">{{ $tpl->name }}</p>
                    <p class="text-[11px] text-graphite/70">{{ $tpl->body_template }}</p>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('manage.notification-templates.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Save Notification Template (notifications.manage)</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <input type="text" name="key" placeholder="event.key (e.g. order.shipped)" class="field-input py-1.5 text-xs" required />
                <input type="text" name="name" placeholder="Template Name" class="field-input py-1.5 text-xs" required />
                <select name="channel" class="field-input py-1.5 text-xs">
                    <option value="database">Database In-App</option>
                    <option value="email">Transactional Email</option>
                    <option value="whatsapp_consent">WhatsApp (Consent)</option>
                </select>
            </div>
            <input type="text" name="subject" placeholder="Email/Alert Subject..." class="field-input py-1.5 text-xs" />
            <textarea name="body_template" rows="2" placeholder="Message body with @{{placeholders}}..." class="field-input py-1.5 text-xs" required></textarea>
            <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Save Notification Template</button>
        </form>
    </section>
</div>
