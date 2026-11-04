{{-- Tab panel "addresses" of account/dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="addresses" x-show="tab === 'addresses'" x-cloak>
    <section id="addresses-section" class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Saved Delivery Addresses (Pakistan)</h2>

        <div class="space-y-2 text-xs">
            @foreach ($user->addresses as $addr)
                <div class="flex items-center justify-between gap-3 rounded-xs border border-graphite/12 bg-paper p-3.5">
                    <div>
                        <strong class="uppercase text-gold-dark">{{ $addr->label }}</strong> · <span class="font-semibold">{{ $addr->recipient_name }} ({{ $addr->phone }})</span>
                        <p class="text-graphite/75">{{ $addr->line1 }}, {{ $addr->area ? $addr->area.', ' : '' }}{{ $addr->city }}, {{ $addr->province }}</p>
                    </div>
                    <form method="POST" action="{{ route('account.addresses.destroy', $addr) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-danger font-semibold hover:underline cursor-pointer">Delete</button>
                    </form>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('account.addresses.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3 border-t border-graphite/10 pt-4">
            @csrf
            <div>
                <label for="addr-label" class="field-label text-xs">Label</label>
                <input id="addr-label" type="text" name="label" value="Home" class="field-input text-xs" required />
            </div>
            <div>
                <label for="addr-rec" class="field-label text-xs">Recipient Name</label>
                <input id="addr-rec" type="text" name="recipient_name" value="{{ $user->name }}" class="field-input text-xs" required />
            </div>
            <div>
                <label for="addr-phone" class="field-label text-xs">Phone</label>
                <input id="addr-phone" type="tel" name="phone" value="{{ $user->phone ?? '0300-1234567' }}" class="field-input text-xs" required />
            </div>
            <div class="sm:col-span-2">
                <label for="addr-line1" class="field-label text-xs">Street Address / House / Sector</label>
                <input id="addr-line1" type="text" name="line1" class="field-input text-xs" placeholder="House 10, Street 4, DHA Phase 5" required />
            </div>
            <div>
                <label for="addr-city" class="field-label text-xs">City</label>
                <input id="addr-city" type="text" name="city" value="Lahore" class="field-input text-xs" required />
            </div>
            <div>
                <label for="addr-prov" class="field-label text-xs">Province</label>
                <select id="addr-prov" name="province" class="field-input text-xs">
                    <option>Punjab</option>
                    <option>Sindh</option>
                    <option>Khyber Pakhtunkhwa</option>
                    <option>Islamabad Capital Territory</option>
                    <option>Balochistan</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex items-end">
                <x-ui.button type="submit" variant="graphite" size="sm">Add Delivery Address</x-ui.button>
            </div>
        </form>
    </section>

    <section id="security-settings" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Change Account Password</h2>
        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="cur-pass" class="field-label text-xs">Current Password</label>
                <input id="cur-pass" type="password" name="current_password" class="field-input text-xs" required />
            </div>
            <div>
                <label for="new-pass" class="field-label text-xs">New Password (Min 8 chars)</label>
                <input id="new-pass" type="password" name="password" class="field-input text-xs" required />
            </div>
            <div>
                <label for="new-pass-conf" class="field-label text-xs">Confirm New Password</label>
                <input id="new-pass-conf" type="password" name="password_confirmation" class="field-input text-xs" required />
            </div>
            <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">Update Password</x-ui.button>
        </form>
    </section>
</div>
