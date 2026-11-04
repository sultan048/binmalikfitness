{{-- Tab panel "gym" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
@php
    use App\Support\Money;
@endphp
<div id="admin-gym-governance" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="gym" x-show="tab === 'gym'" x-cloak>
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 10 · Admin Gym Package &amp; Pricing Control</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Gym Membership Packages &amp; Revenue</h2>
                <a href="{{ route('manage.exports.csv', 'gym-memberships') }}" class="text-[11px] font-bold uppercase text-gold-dark hover:underline">Memberships CSV</a> ·
                <a href="{{ route('manage.exports.csv', 'enquiries') }}" class="text-[11px] font-bold uppercase text-gold-dark hover:underline">Enquiries CSV</a>
            </div>
            <span class="rounded-xs bg-charcoal px-3 py-1 font-mono text-xs font-bold text-gold">
                Verified Gym Revenue: {{ \App\Support\Money::format($verifiedGymRevenuePaisa) }}
            </span>
        </div>

        <div class="grid grid-cols-3 gap-3 text-center text-xs">
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/70 block">Active Memberships</span>
                <strong class="font-display text-2xl text-graphite">{{ $activeGymMembershipsCount }}</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/70 block">Recorded Visits</span>
                <strong class="font-display text-2xl text-graphite">{{ $gymCheckIns->count() }}</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/70 block">Scheduled Classes</span>
                <strong class="font-display text-2xl text-graphite">{{ $gymClasses->count() }}</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('manage.gym.plans.store') }}" class="rounded-xs bg-paper p-4 space-y-2.5 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Configure New Gym Membership Package (Admin Only)</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <input type="text" name="name" placeholder="Package Name" class="field-input text-xs py-1.5 sm:col-span-2" required />
                <input type="number" name="duration_days" value="30" placeholder="Days" class="field-input text-xs py-1.5" required />
                <input type="number" name="price_pkr" placeholder="Price (PKR)" class="field-input text-xs py-1.5" required />
                <input type="number" name="registration_fee_pkr" value="0" placeholder="Reg Fee (PKR)" class="field-input text-xs py-1.5" />
                <input type="text" name="access_hours" value="All Operating Hours" class="field-input text-xs py-1.5" />
            </div>
            <input type="text" name="inclusions_csv" placeholder="Inclusions (comma-separated)..." class="field-input text-xs py-1.5" />
            <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs w-full">Save Gym Membership Package</button>
        </form>
    </section>

    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Facility Timings &amp; Holiday Closures</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Configure Opening Hours &amp; Holidays</h2>
            </div>
            <x-ui.badge tone="light">settings.manage</x-ui.badge>
        </div>

        @if ($openingHours->isNotEmpty())
            @php $firstHour = $openingHours->first(); @endphp
            <form method="POST" action="{{ route('manage.gym.opening-hours.update', $firstHour) }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                @csrf
                @method('PUT')
                <strong class="uppercase text-graphite block">Update {{ $firstHour->day_name }} Operating Window</strong>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <input type="time" name="opens_at" value="{{ substr((string) $firstHour->opens_at, 0, 5) }}" class="field-input text-xs py-1" />
                    <input type="time" name="closes_at" value="{{ substr((string) $firstHour->closes_at, 0, 5) }}" class="field-input text-xs py-1" />
                    <input type="text" name="peak_window" value="{{ $firstHour->peak_window }}" placeholder="Peak window" class="field-input text-xs py-1" />
                    <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 text-[11px] font-bold uppercase text-gold cursor-pointer">Save Hours</button>
                </div>
            </form>
        @endif

        <form method="POST" action="{{ route('manage.gym.holidays.store') }}" class="rounded-xs border border-graphite/15 p-3.5 space-y-2 text-xs">
            @csrf
            <strong class="uppercase text-graphite block">Add Holiday / Maintenance Schedule</strong>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <input type="text" name="title" placeholder="Holiday / Maintenance Title" class="field-input text-xs py-1.5 sm:col-span-2" required />
                <input type="date" name="holiday_date" value="{{ now()->addDays(14)->toDateString() }}" class="field-input text-xs py-1.5" required />
            </div>
            <div class="flex items-center justify-between gap-2">
                <label class="inline-flex items-center gap-1.5 text-xs">
                    <input type="checkbox" name="is_closed" value="1" checked /> Facility Closed All Day
                </label>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs">Save Holiday Notice</button>
            </div>
        </form>
    </section>
</div>
