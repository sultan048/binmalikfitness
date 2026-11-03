<x-layouts.dashboard
    :title="'Confidential Client File: '.$client->name"
    role-label="Authorized Coaching & Health Review"
    role-badge="Audited Access"
    :navigation="[
        ['label' => '← Back to Workspace', 'href' => auth()->user()->hasRole('admin') ? route('manage.admin') : route('manage.manager'), 'badge' => 'Return'],
    ]"
>
    <section class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-4">
            <div>
                <span class="eyebrow text-gold-dark">Access Logged in Audit Trail</span>
                <h2 class="font-display text-3xl uppercase text-graphite mt-1">{{ $client->name }} ({{ $client->email }})</h2>
            </div>
            <x-ui.badge tone="success">Policy Verified: UserPolicy::viewConfidential</x-ui.badge>
        </div>

        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4 text-xs">
            <div class="rounded-xs bg-paper p-3.5">
                <dt class="text-graphite/65">Primary Fitness Goal</dt>
                <dd class="mt-1 font-bold text-graphite">{{ $client->customerProfile?->fitness_goal ?? 'Not set' }}</dd>
            </div>
            <div class="rounded-xs bg-paper p-3.5">
                <dt class="text-graphite/65">Height / Weight</dt>
                <dd class="mt-1 font-bold text-graphite">
                    {{ $client->customerProfile?->height_cm ? $client->customerProfile->height_cm.' cm' : '—' }}
                    /
                    {{ $client->customerProfile?->weight_kg ? $client->customerProfile->weight_kg.' kg' : '—' }}
                </dd>
            </div>
            <div class="rounded-xs bg-paper p-3.5">
                <dt class="text-graphite/65">Experience Level</dt>
                <dd class="mt-1 font-bold text-graphite">{{ $client->customerProfile?->experience_level ?? '—' }}</dd>
            </div>
            <div class="rounded-xs bg-paper p-3.5">
                <dt class="text-graphite/65">Equipment Access</dt>
                <dd class="mt-1 font-bold text-graphite">{{ $client->customerProfile?->equipment_access ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h3 class="font-display text-2xl uppercase text-graphite">Client Private Files ({{ $client->privateFiles->count() }})</h3>
        @if ($client->privateFiles->isEmpty())
            <p class="text-xs text-graphite/70">No private intake documents or check-in photos uploaded yet.</p>
        @else
            <div class="divide-y divide-graphite/10 text-xs">
                @foreach ($client->privateFiles as $file)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <span class="font-bold text-graphite">{{ $file->original_name }}</span>
                            <span class="ml-2 rounded-xs bg-paper px-2 py-0.5 text-[10px] uppercase">{{ $file->category }}</span>
                            <span class="block text-[11px] text-graphite/60">Uploaded {{ $file->created_at?->format('d M Y H:i') }} · {{ number_format($file->size_bytes / 1024, 1) }} KB</span>
                        </div>
                        <x-ui.button :href="route('private-files.download', $file)" variant="graphite" size="sm">
                            Stream Private File
                        </x-ui.button>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.dashboard>
