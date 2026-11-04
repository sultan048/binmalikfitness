{{-- Tab panel "settings" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="settings" x-show="tab === 'settings'" x-cloak>
    <section id="platform-settings" class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">Platform Facility Settings</h2>
            <x-ui.badge tone="light">Admin Only</x-ui.badge>
        </div>
        <form method="POST" action="{{ route('manage.settings.update') }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="set-city" class="field-label text-xs">Confirmed Gym City</label>
                <input id="set-city" type="text" name="gym_city" value="{{ $gymCity }}" class="field-input text-xs" required />
            </div>
            <div>
                <label for="set-address" class="field-label text-xs">Facility Street Address</label>
                <input id="set-address" type="text" name="gym_address" value="{{ $gymAddress }}" class="field-input text-xs" required />
            </div>
            <div>
                <label for="set-phone" class="field-label text-xs">Official Contact Phone</label>
                <input id="set-phone" type="text" name="gym_phone" value="{{ $gymPhone }}" class="field-input text-xs" required />
            </div>
            <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">
                Save Platform Settings (Audited)
            </x-ui.button>
        </form>
    </section>
</div>
