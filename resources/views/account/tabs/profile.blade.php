{{-- Tab panel "profile" of account/dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="profile" x-show="tab === 'profile'" x-cloak>
    <section id="profile-form" class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">My Profile & Training Parameters</h2>
            <x-ui.badge tone="light">Persisted in MySQL</x-ui.badge>
        </div>

        <form method="POST" action="{{ route('account.profile.update') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div>
                <label for="prof-name" class="field-label text-xs">Full Name</label>
                <input id="prof-name" type="text" name="name" value="{{ old('name', $user->name) }}" class="field-input text-xs" required />
            </div>
            <div>
                <label for="prof-phone" class="field-label text-xs">Mobile Number (Pakistan)</label>
                <input id="prof-phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="field-input text-xs" required />
            </div>
            <div>
                <label for="prof-goal" class="field-label text-xs">Primary Fitness Goal</label>
                <input id="prof-goal" type="text" name="fitness_goal" value="{{ old('fitness_goal', $profile?->fitness_goal ?? 'Body Recomposition & Strength') }}" class="field-input text-xs" required />
            </div>
            <div>
                <label for="prof-equip" class="field-label text-xs">Equipment Access</label>
                <input id="prof-equip" type="text" name="equipment_access" value="{{ old('equipment_access', $profile?->equipment_access) }}" class="field-input text-xs" placeholder="Full Commercial Gym / Home Dumbbells" />
            </div>
            <div>
                <label for="prof-height" class="field-label text-xs">Height (cm)</label>
                <input id="prof-height" type="number" name="height_cm" value="{{ old('height_cm', $profile?->height_cm) }}" class="field-input text-xs" />
            </div>
            <div>
                <label for="prof-weight" class="field-label text-xs">Current Weight (kg)</label>
                <input id="prof-weight" type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $profile?->weight_kg) }}" class="field-input text-xs" />
            </div>
            <div class="sm:col-span-2">
                <x-ui.button type="submit" variant="graphite" size="sm">Save Profile & Fitness Metrics</x-ui.button>
            </div>
        </form>
    </section>

    {{-- Assigned Coaches & Private File Upload --}}
    <section id="private-uploads" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">Private Check-In & File Upload</h2>
            <x-ui.badge tone="gold">Private Disk</x-ui.badge>
        </div>
        <p class="text-xs text-graphite/75">
            Files are stored outside <code class="font-mono">/public</code> and can only be accessed by you, an Admin, or your assigned coach.
        </p>

        <form method="POST" action="{{ route('account.private-files.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label for="file-cat" class="field-label text-xs">Document / Photo Category</label>
                <select id="file-cat" name="category" class="field-input text-xs">
                    <option value="progress_photo">Private Check-In Progress Photo</option>
                    <option value="intake_document">Bloodwork / Intake Attachment (PDF/Image)</option>
                    <option value="payment_receipt">Bank Transfer Receipt</option>
                </select>
            </div>
            <div>
                <label for="file-input" class="field-label text-xs">Select File (JPG, PNG, WEBP, PDF · Max 5 MB)</label>
                <input id="file-input" type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="field-input text-xs" required />
            </div>
            <label class="flex items-start gap-2 text-xs text-graphite/80 cursor-pointer">
                <input type="checkbox" name="consent_granted" value="1" class="mt-0.5 accent-graphite" required />
                <span>I consent to storing this file privately for my coaching/order review. It will never be published publicly without a separate consent agreement.</span>
            </label>
            <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">
                Upload to Private Storage
            </x-ui.button>
        </form>

        @if ($user->privateFiles->isNotEmpty())
            <div class="border-t border-graphite/10 pt-3 space-y-2 text-xs">
                <h3 class="font-bold uppercase text-gold-dark">My Uploaded Private Files</h3>
                @foreach ($user->privateFiles as $pf)
                    <div class="flex items-center justify-between rounded-xs bg-paper px-3 py-2">
                        <span class="truncate pr-2">{{ $pf->original_name }} ({{ $pf->category }})</span>
                        <a href="{{ route('private-files.download', $pf) }}" class="font-semibold text-gold-dark hover:underline shrink-0">Download →</a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
